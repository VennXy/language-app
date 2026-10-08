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
        $levels = $progression->levelsInOrder();
        $accessibleLevelIds = $progression->accessibleLevelIds(auth()->user());
        $completedLevelIds = $progression->completedLevelIds(auth()->user());

        return view('levels.select', compact('levels', 'accessibleLevelIds', 'completedLevelIds'));
    }

    public function courses(Request $request, $id, LevelProgressionService $progression)
    {
        $level = Level::findOrFail($id);
        abort_unless($progression->canAccessLevel(auth()->user(), $level), 403);

        $lessons = $level->lessons()->orderBy('sort_order')->orderBy('id')->get();
        $accessibleLessonIds = $progression->accessibleLessonIds(auth()->user(), $level);
        $completedLessonIds = $progression->completedLessonIds(auth()->user(), $level);
        $progressByLesson = DB::table('user_progress')
            ->where('user_id', auth()->id())
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get(['lesson_id', 'status', 'progress_percent'])
            ->keyBy('lesson_id');
        $firePoints = auth()->user()->role === 'student'
            ? (int) auth()->user()->streak_count
            : null;
        $type = $request->query('type', 'training');

        return view('levels.courses', compact(
            'level',
            'lessons',
            'accessibleLessonIds',
            'completedLessonIds',
            'progressByLesson',
            'firePoints',
            'type'
        ));
    }
}
