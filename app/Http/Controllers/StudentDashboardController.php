<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Level;
use App\Services\HeartService;
use App\Services\LevelProgressionService;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    private const RECENT_ACTIVITY_LIMIT = 5;

    public function index(LevelProgressionService $progression)
    {
        abort_unless(auth()->user()?->role === 'student', 403);

        $user = auth()->user();

        $levels = $progression->levelsInOrder();
        $accessibleLevelIds = $progression->accessibleLevelIds($user);
        $completedLevelIds = $progression->completedLevelIds($user);

        $userLevelStatuses = DB::table('user_levels')
            ->where('user_id', $user->id)
            ->pluck('status', 'level_id');

        $currentLevel = $levels->first(
            fn (Level $level) => in_array((int) $level->id, $accessibleLevelIds, true)
                && ($userLevelStatuses[(int) $level->id] ?? null) === 'active'
        )
            ?? $levels->first(fn (Level $level) => in_array((int) $level->id, $accessibleLevelIds, true))
            ?? $levels->first();

        $lessonsByLevel = Lesson::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'level_id', 'title']);

        $completedLessonIds = DB::table('user_progress')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->pluck('lesson_id')
            ->map(fn ($lessonId) => (int) $lessonId)
            ->all();

        $progressByLesson = DB::table('user_progress')
            ->where('user_id', $user->id)
            ->get(['lesson_id', 'status', 'progress_percent'])
            ->keyBy('lesson_id');

        $levelSummaries = $levels->map(function (Level $level) use ($lessonsByLevel, $completedLessonIds, $accessibleLevelIds, $completedLevelIds): array {
            $lessonIds = $lessonsByLevel->where('level_id', $level->id)
                ->pluck('id')
                ->map(fn ($lessonId) => (int) $lessonId)
                ->all();

            $total = count($lessonIds);
            $completed = count(array_intersect($lessonIds, $completedLessonIds));

            return [
                'level' => $level,
                'is_accessible' => in_array((int) $level->id, $accessibleLevelIds, true),
                'is_completed' => in_array((int) $level->id, $completedLevelIds, true),
                'total_lessons' => $total,
                'completed_lessons' => $completed,
                'percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            ];
        })->values();

        $accessibleLessonIds = [];
        foreach ($levels as $level) {
            if (! in_array((int) $level->id, $accessibleLevelIds, true)) {
                continue;
            }

            foreach ($progression->accessibleLessonIds($user, $level) as $lessonId) {
                $accessibleLessonIds[] = (int) $lessonId;
            }
        }

        $totalLessonCount = count($accessibleLessonIds);
        $completedLessonCount = count(array_intersect($accessibleLessonIds, $completedLessonIds));
        $overallPercent = $totalLessonCount > 0
            ? (int) round($completedLessonCount / $totalLessonCount * 100)
            : 0;

        $currentLevelLessons = $currentLevel
            ? $lessonsByLevel->where('level_id', $currentLevel->id)->values()
            : collect();

        $currentLevelAccessibleLessonIds = $currentLevel
            ? $progression->accessibleLessonIds($user, $currentLevel)
            : [];

        $currentLevelCompletedLessonIds = $currentLevel
            ? $progression->completedLessonIds($user, $currentLevel)
            : [];

        $nextLessonId = collect($currentLevelAccessibleLessonIds)
            ->first(fn ($lessonId) => ! in_array($lessonId, $currentLevelCompletedLessonIds, true));

        $nextLesson = $nextLessonId ? $currentLevelLessons->firstWhere('id', $nextLessonId) : null;

        $recentActivity = DB::table('user_progress')
            ->join('lessons', 'lessons.id', '=', 'user_progress.lesson_id')
            ->leftJoin('levels', 'levels.id', '=', 'lessons.level_id')
            ->where('user_progress.user_id', $user->id)
            ->orderByDesc('user_progress.updated_at')
            ->orderByDesc('user_progress.id')
            ->limit(self::RECENT_ACTIVITY_LIMIT)
            ->get([
                'user_progress.status',
                'user_progress.progress_percent',
                'user_progress.completed_at',
                'user_progress.updated_at',
                'lessons.id as lesson_id',
                'lessons.title as lesson_title',
                'levels.name as level_name',
            ]);

        return view('user.dashboard', [
            'currentLevel' => $currentLevel,
            'levelSummaries' => $levelSummaries,
            'overallPercent' => $overallPercent,
            'completedLessonCount' => $completedLessonCount,
            'totalLessonCount' => $totalLessonCount,
            'currentLevelLessons' => $currentLevelLessons,
            'currentLevelAccessibleLessonIds' => $currentLevelAccessibleLessonIds,
            'currentLevelCompletedLessonIds' => $currentLevelCompletedLessonIds,
            'progressByLesson' => $progressByLesson,
            'nextLesson' => $nextLesson,
            'recentActivity' => $recentActivity,
            'firePoints' => (int) $user->streak_count,
            'gems' => max(0, (int) $user->gems),
            'hearts' => max(0, min(HeartService::MAX_HEARTS, (int) $user->hearts)),
            'maxHearts' => HeartService::MAX_HEARTS,
            'dailyStreak' => (int) $user->daily_streak,
            'streakCompletedToday' => $user->last_learning_activity_date !== null
                && $user->last_learning_activity_date->toDateString() === now()->toDateString(),
        ]);
    }
}
