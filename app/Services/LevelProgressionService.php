<?php

namespace App\Services;

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

            if ($index < $levels->count() - 1 && !$this->levelHasAllLessonsCompleted($user->id, $level)) {
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
            $levelCompleted = $this->levelHasAllLessonsCompleted($user->id, $level);

            $nextLevel = null;

            if ($levelCompleted) {
                DB::table('user_levels')
                    ->where('user_id', $user->id)
                    ->where('level_id', $level->id)
                    ->update(['status' => 'completed', 'updated_at' => $now]);

                $levelIndex = array_search($level->level, self::LEVEL_ORDER, true);
                $nextCode = $levelIndex === false ? null : (self::LEVEL_ORDER[$levelIndex + 1] ?? null);

                if ($nextCode !== null) {
                    $nextLevel = Level::where('level', $nextCode)->first();

                    if ($nextLevel) {
                        $nextAccess = DB::table('user_levels')
                            ->where('user_id', $user->id)
                            ->where('level_id', $nextLevel->id)
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
            }

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
}
