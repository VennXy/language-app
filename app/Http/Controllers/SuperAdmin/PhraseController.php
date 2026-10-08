<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Phrase;
use Illuminate\Http\Request;

class PhraseController extends Controller
{
    public function store(Request $request, Lesson $lesson)
    {
        $request->validate([
            'phrase' => 'required|string',
            'meaning' => 'required|string',
            'romaji' => 'nullable|string|max:191', 
            'audio_text' => 'nullable|string|max:191',
        ]);

        $lesson->phrases()->create([
            'phrase' => $request->phrase,
            'meaning' => $request->meaning,
            'romaji' => $request->romaji,
            'audio_text' => $request->audio_text,
        ]);

        return redirect()->route('super.admin.lessons.show', $lesson->id)
                         ->with('success', 'Phrase added successfully!');
    }

    public function destroy(Phrase $phrase)
    {
        $lessonId = $phrase->lesson_id;
        $phrase->delete();

        return redirect()->route('super.admin.lessons.show', $lessonId)
                         ->with('success', 'Phrase deleted successfully!');
    }
}