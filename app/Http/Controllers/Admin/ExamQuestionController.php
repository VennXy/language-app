<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamQuestionController extends Controller
{
    private const MAX_QUESTIONS_PER_EXAM = 5;

    public function index(Exam $exam): View
    {
        $this->authorizeAdmin();

        $questions = ExamQuestion::query()
            ->where('exam_id', $exam->id)
            ->where('admin_id', auth()->id())
            ->latest()
            ->get();
        $questionCount = $questions->count();

        return view('admin.exam-questions.index', compact('exam', 'questions', 'questionCount'));
    }

    public function create(Exam $exam): View|RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureDraftExam($exam);

        if ($this->questionCount($exam) >= self::MAX_QUESTIONS_PER_EXAM) {
            return redirect()->route('admin.exams.questions.index', $exam)
                ->with('error', 'You can add a maximum of 5 questions to this exam.');
        }

        return view('admin.exam-questions.form', compact('exam'));
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate($this->questionRules());

        DB::transaction(function () use ($exam, $validated, $request): void {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            $this->ensureDraftExam($lockedExam);

            if ($this->questionCount($lockedExam) >= self::MAX_QUESTIONS_PER_EXAM) {
                throw ValidationException::withMessages([
                    'question' => 'You can add a maximum of 5 questions to this exam.',
                ]);
            }

            $lockedExam->questions()->create([
                ...$validated,
                'admin_id' => $request->user()->id,
                'correct_option' => strtolower($validated['correct_option']),
                'status' => 'draft',
                'submitted_at' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'rejection_reason' => null,
            ]);
        });

        return redirect()->route('admin.exams.questions.index', $exam)->with('success', 'Question saved as draft.');
    }

    public function edit(ExamQuestion $question): View
    {
        $this->authorizeAdmin();
        $question = $this->ownedQuestion($question);
        $this->ensureDraftExam($question->exam);

        abort_unless(in_array($question->status, ['draft', 'rejected'], true), 403);

        $exam = $question->exam;

        return view('admin.exam-questions.form', compact('exam', 'question'));
    }

    public function update(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeAdmin();
        $question = $this->ownedQuestion($question);
        $this->ensureDraftExam($question->exam);
        abort_unless(in_array($question->status, ['draft', 'rejected'], true), 403);

        $validated = $request->validate($this->questionRules());
        $wasRejected = $question->status === 'rejected';
        $question->fill([
            ...$validated,
            'correct_option' => strtolower($validated['correct_option']),
        ]);

        if ($wasRejected) {
            $question->status = 'draft';
            $question->submitted_at = null;
            $question->reviewed_at = null;
            $question->reviewed_by = null;
            $question->rejection_reason = null;
        }

        $question->save();

        return redirect()->route('admin.exams.questions.index', $question->exam_id)->with('success', 'Question updated.');
    }

    public function destroy(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeAdmin();
        $question = $this->ownedQuestion($question);
        $this->ensureDraftExam($question->exam);
        abort_unless(in_array($question->status, ['draft', 'rejected'], true), 403);

        $examId = $question->exam_id;
        $question->delete();

        return redirect()->route('admin.exams.questions.index', $examId)->with('success', 'Question deleted.');
    }

    public function submit(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeAdmin();
        $question = $this->ownedQuestion($question);
        $this->ensureDraftExam($question->exam);
        abort_unless($question->status === 'draft', 403);

        $question->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('admin.exams.questions.index', $question->exam_id)->with('success', 'Question submitted for review.');
    }

    /** @return array<string, string> */
    private function questionRules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'option_a' => ['required', 'string', 'max:2000'],
            'option_b' => ['required', 'string', 'max:2000'],
            'option_c' => ['required', 'string', 'max:2000'],
            'option_d' => ['required', 'string', 'max:2000'],
            'correct_option' => ['required', 'in:A,B,C,D'],
        ];
    }

    private function questionCount(Exam $exam): int
    {
        return $exam->questions()->where('admin_id', auth()->id())->count();
    }

    private function ownedQuestion(ExamQuestion $question): ExamQuestion
    {
        return ExamQuestion::query()
            ->whereKey($question->id)
            ->where('admin_id', auth()->id())
            ->firstOrFail();
    }

    private function ensureDraftExam(Exam $exam): void
    {
        abort_unless($exam->status === 'draft', 403, 'Questions can only be managed for draft exams.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
