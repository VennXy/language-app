<?php

namespace App\Http\Controllers;

use App\Models\Vocabulary;
use App\Services\TrainingAnswerValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentLearningController extends Controller
{
    public function bookmarks()
    {
        $this->ensureStudent();

        $records = DB::table('bookmarks')
            ->where('user_id', auth()->id())
            ->where('bookmarkable_type', Vocabulary::class)
            ->orderByDesc('created_at')
            ->get();
        $ids = $records->pluck('bookmarkable_id')->map(fn ($id) => (int) $id);
        $vocabularies = Vocabulary::with('lesson')->whereIn('id', $ids)->get()->keyBy('id');
        $validIds = $vocabularies->keys()->map(fn ($id) => (int) $id)->all();

        $staleBookmarks = DB::table('bookmarks')
            ->where('user_id', auth()->id())
            ->where('bookmarkable_type', Vocabulary::class);
        if ($validIds) {
            $staleBookmarks->whereNotIn('bookmarkable_id', $validIds);
        }
        $staleBookmarks->delete();

        $items = $records->map(fn ($record) => [
            'vocabulary' => $vocabularies->get((int) $record->bookmarkable_id),
        ])->filter(fn ($item) => $item['vocabulary'] !== null)->values();

        return view('user.bookmarks', compact('items'));
    }

    public function toggleBookmark(Vocabulary $vocabulary)
    {
        $this->ensureStudent();

        $bookmark = DB::table('bookmarks')
            ->where('user_id', auth()->id())
            ->where('bookmarkable_type', Vocabulary::class)
            ->where('bookmarkable_id', $vocabulary->id);

        if ($bookmark->exists()) {
            $bookmark->delete();
            $message = 'Bookmark removed.';
        } else {
            DB::table('bookmarks')->insertOrIgnore([
                'user_id' => auth()->id(),
                'bookmarkable_type' => Vocabulary::class,
                'bookmarkable_id' => $vocabulary->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $message = 'Question bookmarked.';
        }

        return back()->with('status', $message);
    }

    public function mistakes()
    {
        $this->ensureStudent();

        $records = DB::table('user_question_mistakes as m')
            ->join('vocabularies as v', 'v.id', '=', 'm.vocabulary_id')
            ->where('m.user_id', auth()->id())
            ->orderBy('m.created_at')
            ->get(['m.id as mistake_id', 'v.id as vocabulary_id']);
        $vocabularies = Vocabulary::with('lesson')
            ->whereIn('id', $records->pluck('vocabulary_id'))
            ->get()
            ->keyBy('id');
        $mistakes = $records->map(fn ($record) => [
            'id' => (int) $record->mistake_id,
            'vocabulary' => $vocabularies->get((int) $record->vocabulary_id),
        ])->filter(fn ($item) => $item['vocabulary'] !== null)->values();

        return view('user.mistakes', compact('mistakes'));
    }

    public function submitMistakeReview(
        Request $request,
        Vocabulary $vocabulary,
        TrainingAnswerValidator $answerValidator
    ) {
        $this->ensureStudent();

        $validated = $request->validate(['answer' => ['required', 'string', 'max:2000']]);
        $mistake = DB::table('user_question_mistakes')
            ->where('user_id', auth()->id())
            ->where('vocabulary_id', $vocabulary->id);

        abort_unless($mistake->exists(), 404);

        $correct = $answerValidator->matches(
            $validated['answer'],
            $vocabulary->correct_answer ?? '',
            $vocabulary->question_type
        );

        if ($correct) {
            $mistake->delete();
        }

        return redirect()->route('student.mistakes.index')->with('review_feedback', [
            'vocabulary_id' => (int) $vocabulary->id,
            'correct' => $correct,
        ]);
    }

    private function ensureStudent(): void
    {
        abort_unless(auth()->user()?->role === 'student', 403);
    }
}
