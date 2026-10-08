<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Notifications\ExamQuestionApprovedNotification;
use App\Notifications\ExamQuestionRejectedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamQuestionReviewController extends Controller
{
    public function exams(): View
    {
        $this->authorizeSuperAdmin();

        $exams = Exam::query()
            ->withCount([
                'questions as total_questions_count',
                'questions as draft_questions_count' => fn (Builder $query) => $query->where('status', 'draft'),
                'questions as pending_questions_count' => fn (Builder $query) => $query->where('status', 'pending'),
                'questions as approved_questions_count' => fn (Builder $query) => $query->where('status', 'approved'),
                'questions as rejected_questions_count' => fn (Builder $query) => $query->where('status', 'rejected'),
            ])
            ->latest()
            ->paginate(15);

        return view('admin.exam-question-review.exams', compact('exams'));
    }

    public function index(Exam $exam): View
    {
        $this->authorizeSuperAdmin();

        $exam->loadCount([
            'questions as total_questions_count',
            'questions as draft_questions_count' => fn (Builder $query) => $query->where('status', 'draft'),
            'questions as pending_questions_count' => fn (Builder $query) => $query->where('status', 'pending'),
            'questions as approved_questions_count' => fn (Builder $query) => $query->where('status', 'approved'),
            'questions as rejected_questions_count' => fn (Builder $query) => $query->where('status', 'rejected'),
        ]);

        $questions = $exam->questions()
            ->with('owner:id,name,email')
            ->where('status', 'pending')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        return view('admin.exam-question-review.index', compact('exam', 'questions'));
    }

    public function approve(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        DB::transaction(function () use ($question): void {
            $pendingQuestion = $this->lockPendingQuestion($question);
            $pendingQuestion->status = 'approved';
            $pendingQuestion->reviewed_at = now();
            $pendingQuestion->reviewed_by = auth()->id();
            $pendingQuestion->rejection_reason = null;
            $pendingQuestion->save();

            $pendingQuestion->owner->notify(new ExamQuestionApprovedNotification(
                $pendingQuestion->exam_id,
                $pendingQuestion->exam->title,
                $pendingQuestion->id,
            ));
        });

        return redirect()->route('super.admin.exam.questions.index', $question->exam_id)
            ->with('success', 'Question approved.');
    }

    public function reject(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($question, $validated): void {
            $pendingQuestion = $this->lockPendingQuestion($question);
            $pendingQuestion->status = 'rejected';
            $pendingQuestion->reviewed_at = now();
            $pendingQuestion->reviewed_by = auth()->id();
            $pendingQuestion->rejection_reason = $validated['rejection_reason'];
            $pendingQuestion->save();

            $pendingQuestion->owner->notify(new ExamQuestionRejectedNotification(
                $pendingQuestion->exam_id,
                $pendingQuestion->exam->title,
                $pendingQuestion->id,
                $pendingQuestion->rejection_reason,
            ));
        });

        return redirect()->route('super.admin.exam.questions.index', $question->exam_id)
            ->with('success', 'Question rejected.');
    }

    private function lockPendingQuestion(ExamQuestion $routeQuestion): ExamQuestion
    {
        $question = ExamQuestion::query()
            ->with(['owner', 'exam'])
            ->whereKey($routeQuestion->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($question->status !== 'pending') {
            throw ValidationException::withMessages([
                'question' => 'Only pending questions can be reviewed.',
            ]);
        }

        return $question;
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }
}
