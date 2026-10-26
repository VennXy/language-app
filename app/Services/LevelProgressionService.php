<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Level;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LevelProgressionService
{
    private const LEVEL_ORDER = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function accessibleLevelIds(User $user): array
    {
        $levels = $this->levelsInOrder();

        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return $levels->pluck('id')->all();
        }

        $this->initializeFirstLevel($user, $levels);

        $statuses = DB::table('user_levels')
            ->where('user_id', $user->id)
            ->pluck('status', 'level_id');

        $accessibleIds = [];

        foreach ($levels as $index => $level) {
            if (!in_array($statuses->get($level->id), ['active', 'completed'], true)) {
                break;
            }

            $accessibleIds[] = (int) $level->id;

            if ($index < $levels->count() - 1 && !$this->isLevelCompleted($user, $level)) {
                break;
            }
        }

        return $accessibleIds;
    }

    public function canAccessLevel(User $user, Level $level): bool
    {
        return in_array($level->id, $this->accessibleLevelIds($user), true);
    }

    public function completedLevelIds(User $user): array
    {
        return DB::table('user_levels')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('level_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function completedLessonIds(User $user, Level $level): array
    {
        $lessonIds = Lesson::where('level_id', $level->id)->pluck('id');

        if ($lessonIds->isEmpty()) {
            return [];
        }

        return DB::table('user_progress')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function accessibleLessonIds(User $user, Level $level): array
    {
        if (!$this->canAccessLevel($user, $level)) {
            return [];
        }

        $lessons = Lesson::where('level_id', $level->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return $lessons->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($lessons->isEmpty()) {
            return [];
        }

        $completedIds = $this->completedLessonIds($user, $level);
        $accessibleIds = [];

        foreach ($lessons as $index => $lesson) {
            if ($index > 0) {
                $previousIds = $lessons->take($index)->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count(array_intersect($previousIds, $completedIds)) !== count($previousIds)) {
                    break;
                }
            }

            $accessibleIds[] = (int) $lesson->id;
        }

        return $accessibleIds;
    }

    public function canAccessLesson(User $user, Lesson $lesson): bool
    {
        $lesson->loadMissing('level');

        if (!$lesson->level) {
            return false;
        }

        return in_array(
            (int) $lesson->id,
            $this->accessibleLessonIds($user, $lesson->level),
            true
        );
    }

    public function completeLesson(User $user, Lesson $lesson): array
    {
        return DB::transaction(function () use ($user, $lesson) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existingProgress = DB::table('user_progress')
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            $alreadyCompleted = $existingProgress?->status === 'completed';
            $now = now();
            $gemsAwarded = 0;

            if (!$alreadyCompleted) {
                DB::table('user_progress')->updateOrInsert(
                    ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                    [
                        'status' => 'completed',
                        'progress_percent' => 100,
                        'completed_at' => $now,
                        'created_at' => $existingProgress?->created_at ?? $now,
                        'updated_at' => $now,
                    ]
                );

                if ($user->role === 'student') {
                    DB::table('users')->where('id', $user->id)->increment('streak_count', 10);
                    $gemsAwarded = max(0, (int) config('gamification.learning_completion_gem_reward', 50));
                    if ($gemsAwarded > 0) {
                        DB::table('users')->where('id', $user->id)->increment('gems', $gemsAwarded);
                    }
                }
            }

            $level = Level::findOrFail($lesson->level_id);

            // Mark the level completed (and unlock the next level) only when the
            // learner satisfies the full completion rule — Training AND exams.
            // Delegated to syncLevelCompletion() so the lesson path and the exam
            // pass path share one implementation of the progression logic.
            $sync = $this->syncLevelCompletion($user, $level);

            $levelCompleted = $sync['level_completed'];
            $nextLevel = $sync['next_level'];

            return [
                'completed' => true,
                'already_completed' => $alreadyCompleted,
                'fire_awarded' => $user->role === 'student' && !$alreadyCompleted ? 10 : 0,
                'streak_count' => (int) DB::table('users')->where('id', $user->id)->value('streak_count'),
                'gems_awarded' => $gemsAwarded,
                'gems_balance' => (int) DB::table('users')->where('id', $user->id)->value('gems'),
                'level_completed' => $levelCompleted,
                'next_level_unlocked' => $nextLevel?->level,
            ];
        });
    }

    /**
     * Persist the authoritative level-completion outcome: a level is marked
     * 'completed' (and its next level activated) only once the learner satisfies
     * isLevelCompleted() — all required Training AND all required exams passed.
     * Returns the outcome so callers (e.g. completeLesson) can surface it.
     *
     * @return array{level_completed: bool, next_level: Level|null}
     */
    public function syncLevelCompletion(User $user, Level $level): array
    {
        return DB::transaction(function () use ($user, $level): array {
            $levelCompleted = $this->levelHasAllLessonsCompleted($user->id, $level)
                && $this->levelExamsPassed($user, $level);

            $nextLevel = null;

            if ($levelCompleted) {
                $now = now();

                DB::table('user_levels')
                    ->where('user_id', $user->id)
                    ->where('level_id', $level->id)
                    ->update(['status' => 'completed', 'updated_at' => $now]);

                $nextLevel = $this->nextLevel($level);

                if ($nextLevel) {
                    $nextAccess = DB::table('user_levels')
                        ->where('user_id', $user->id)
                        ->where('level_id', $nextLevel->id)
                        ->lockForUpdate()
                        ->first();

                    if ($nextAccess) {
                        if ($nextAccess->status !== 'completed') {
                            DB::table('user_levels')
                                ->where('id', $nextAccess->id)
                                ->update(['status' => 'active', 'updated_at' => $now]);
                        }
                    } else {
                        DB::table('user_levels')->insert([
                            'user_id' => $user->id,
                            'level_id' => $nextLevel->id,
                            'status' => 'active',
                            'score' => 0,
                            'certificate_issued' => false,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            return ['level_completed' => $levelCompleted, 'next_level' => $nextLevel];
        });
    }

    /**
     * The authoritative "is this level fully finished?" rule used to decide both
     * whether a level shows as Completed and whether the NEXT level unlocks.
     *
     * A level is complete only when ALL of its required Training (lessons) is
     * complete AND every published exam for the level has a passed attempt.
     * A level with no published exams completes on Training alone (spec §6); a
     * level with exams can never be completed by Training alone.
     */
    public function isLevelCompleted(User $user, Level $level): bool
    {
        return $this->levelHasAllLessonsCompleted($user->id, $level)
            && $this->levelExamsPassed($user, $level);
    }

    /**
     * Whether every published exam belonging to the level has a passed attempt
     * for the learner. A level with no published exams passes vacuously, so
     * Training-only levels complete exactly as they did before exams gated
     * completion.
     */
    public function levelExamsPassed(User $user, Level $level): bool
    {
        $publishedExamIds = Exam::query()
            ->where('level_id', $level->id)
            ->where('status', 'published')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($publishedExamIds === []) {
            return true;
        }

        $passedExamIds = ExamAttempt::query()
            ->where('user_id', $user->id)
            ->where('passed', true)
            ->whereIn('exam_id', $publishedExamIds)
            ->pluck('exam_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        return count(array_diff($publishedExamIds, $passedExamIds)) === 0;
    }

    /**
     * The next level in curriculum order after the given level, or null for the
     * last level (C2).
     */
    private function nextLevel(Level $level): ?Level
    {
        $levelIndex = array_search($level->level, self::LEVEL_ORDER, true);

        if ($levelIndex === false) {
            return null;
        }

        $nextCode = self::LEVEL_ORDER[$levelIndex + 1] ?? null;

        return $nextCode !== null ? Level::where('level', $nextCode)->first() : null;
    }

    public function recordLessonProgress(User $user, Lesson $lesson, int $percentage): void
    {
        $percentage = max(0, min(99, $percentage));

        DB::transaction(function () use ($user, $lesson, $percentage): void {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = DB::table('user_progress')
                ->where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === 'completed') {
                return;
            }

            $now = now();
            DB::table('user_progress')->updateOrInsert(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                [
                    'status' => 'in_progress',
                    'progress_percent' => max($percentage, (int) ($existing->progress_percent ?? 0)),
                    'completed_at' => null,
                    'created_at' => $existing?->created_at ?? $now,
                    'updated_at' => $now,
                ]
            );
        });
    }

    public function levelsInOrder()
    {
        return Level::all()->sortBy(function (Level $level) {
            $position = array_search($level->level, self::LEVEL_ORDER, true);

            return $position === false ? count(self::LEVEL_ORDER) : $position;
        })->values();
    }

    private function initializeFirstLevel(User $user, $levels): void
    {
        $firstLevel = $levels->firstWhere('level', 'A1');

        if (!$firstLevel) {
            return;
        }

        if (DB::table('user_levels')
            ->where('user_id', $user->id)
            ->where('level_id', $firstLevel->id)
            ->exists()) {
            return;
        }

        DB::transaction(function () use ($user, $firstLevel) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (!DB::table('user_levels')
                ->where('user_id', $user->id)
                ->where('level_id', $firstLevel->id)
                ->exists()) {
                $now = now();

                DB::table('user_levels')->insert([
                    'user_id' => $user->id,
                    'level_id' => $firstLevel->id,
                    'status' => 'active',
                    'score' => 0,
                    'certificate_issued' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function levelHasAllLessonsCompleted(int $userId, Level $level): bool
    {
        $lessonIds = Lesson::where('level_id', $level->id)->pluck('id');

        return $lessonIds->isNotEmpty()
            && DB::table('user_progress')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->whereIn('lesson_id', $lessonIds)
                ->distinct('lesson_id')
                ->count('lesson_id') === $lessonIds->count();
    }

    /**
     * Whether a learner has completed the required Training for a level, which
     * is the gate that unlocks that level's Exams.
     *
     * This reuses the established definition of level completion
     * (levelHasAllLessonsCompleted) rather than inventing a parallel system. A
     * level that defines no lessons at all is treated as complete so that a
     * level without authored Training content never permanently blocks Exams.
     */
    public function isLevelTrainingComplete(User $user, Level $level): bool
    {
        $lessonCount = Lesson::where('level_id', $level->id)->count();

        if ($lessonCount === 0) {
            return true;
        }

        return $this->levelHasAllLessonsCompleted($user->id, $level);
    }
}
