<?php

namespace App\Http\Controllers;

use App\Models\Level;
use Illuminate\Http\Request;
use App\Services\LevelProgressionService;
use Illuminate\Support\Facades\DB;

class LevelController extends Controller
{
    public function selectLevel(LevelProgressionService $progression)
    {
        $user = auth()->user();

        $levels = $progression->levelsInOrder();
        $accessibleLevelIds = $progression->accessibleLevelIds($user);
        $completedLevelIds = $progression->completedLevelIds($user);

        return view('levels.select', compact(
            'levels',
            'accessibleLevelIds',
            'completedLevelIds'
        ));
    }

    public function courses(
        Request $request,
        $id,
        LevelProgressionService $progression
    ) {
        $user = auth()->user();

        // The selected level must still be accessible.
        $selectedLevel = Level::findOrFail($id);

        abort_unless(
            $progression->canAccessLevel($user, $selectedLevel),
            403
        );

        // Load all levels in A1 -> C2 order.
        $levels = $progression->levelsInOrder();

        // Only allow the existing supported modes.
        $type = $request->query('type', 'training');

        if (!in_array($type, ['training', 'exam'], true)) {
            $type = 'training';
        }

        $accessibleLevelIds = $progression->accessibleLevelIds($user);
        $completedLevelIds = $progression->completedLevelIds($user);

        // Get all lessons from all levels in curriculum order.
        $allLessons = $levels->flatMap(function ($level) {
            return $level->lessons()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        });

        $allLessonIds = $allLessons->pluck('id');

        // Fetch the learner's progress once for the entire path.
        $progressByLesson = $allLessonIds->isNotEmpty()
            ? DB::table('user_progress')
                ->where('user_id', $user->id)
                ->whereIn('lesson_id', $allLessonIds)
                ->get([
                    'lesson_id',
                    'status',
                    'progress_percent'
                ])
                ->keyBy('lesson_id')
            : collect();

        // Build one Learning Path section for every level.
        $pathLevels = $levels->map(function ($level) use (
            $user,
            $progression,
            $progressByLesson
        ) {
            $lessons = $level->lessons()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $isLevelUnlocked = $progression->canAccessLevel(
                $user,
                $level
            );

            $accessibleLessonIds = $isLevelUnlocked
                ? $progression->accessibleLessonIds($user, $level)
                : [];

            $completedLessonIds = $progression->completedLessonIds(
                $user,
                $level
            );

            $completedCount = $lessons->filter(function ($lesson) use (
                $completedLessonIds
            ) {
                return in_array(
                    (int) $lesson->id,
                    $completedLessonIds,
                    true
                );
            })->count();

            $totalLessons = $lessons->count();

            $overallProgress = $totalLessons > 0
                ? (int) round(
                    ($completedCount / $totalLessons) * 100
                )
                : 0;

            return [
                'level' => $level,
                'lessons' => $lessons,
                'isLevelUnlocked' => $isLevelUnlocked,
                'isLevelCompleted' => in_array(
                    (int) $level->id,
                    $progression->completedLevelIds($user),
                    true
                ),
                'accessibleLessonIds' => $accessibleLessonIds,
                'completedLessonIds' => $completedLessonIds,
                'completedCount' => $completedCount,
                'totalLessons' => $totalLessons,
                'overallProgress' => $overallProgress,
                'trainingComplete' => $progression->isLevelTrainingComplete(
                    $user,
                    $level
                ),
                'progressByLesson' => $progressByLesson,
            ];
        });

        $totalLessons = $allLessons->count();

        $completedCount = $allLessons->filter(function ($lesson) use (
            $progressByLesson
        ) {
            return $progressByLesson->get($lesson->id)?->status === 'completed';
        })->count();

        $overallProgress = $totalLessons > 0
            ? (int) round(($completedCount / $totalLessons) * 100)
            : 0;

        $firePoints = $user->role === 'student'
            ? (int) $user->streak_count
            : null;

        return view('levels.courses', compact(
            'selectedLevel',
            'pathLevels',
            'accessibleLevelIds',
            'completedLevelIds',
            'progressByLesson',
            'totalLessons',
            'completedCount',
            'overallProgress',
            'firePoints',
            'type'
        ));
    }
}
