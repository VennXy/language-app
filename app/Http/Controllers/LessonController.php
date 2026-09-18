<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;

class LessonController extends Controller
{
    public function index(Request $request, $id)
{
   $type = $request->get('type', 'training'); 

        $lessons = Lesson::where('level_id', $id)
                         ->where('type', $type)
                         ->get();

        $levelId = $id; 

        return view('levels.courses', compact('lessons', 'type', 'levelId'));
}
}