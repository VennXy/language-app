<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $request->validate([
            'question' => 'required|string',
            'option_a' => 'required|string|max:191',
            'option_b' => 'required|string|max:191',
            'option_c' => 'required|string|max:191',
            'option_d' => 'nullable|string|max:191',
            'correct_option' => 'required|in:a,b,c,d',
        ]);

        $lesson->quizzes()->create([
            'admin_id' => Auth::id(), 
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'correct_option' => $request->correct_option,
        ]);

        return redirect()->route('super.admin.lessons.show', $lesson->id)
                         ->with('success', 'Quiz added successfully!');
    }

    public function destroy(Quiz $quiz)
    {
        $lessonId = $quiz->lesson_id;
        $quiz->delete();

        return redirect()->route('super.admin.lessons.show', $lessonId)
                         ->with('success', 'Quiz deleted successfully!');
    }
}