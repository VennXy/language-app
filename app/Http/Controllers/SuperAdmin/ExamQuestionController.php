<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\RedirectResponse;

class ExamQuestionController extends Controller
{
    /**
     * Allow a Super Admin to submit their OWN draft exam questions for review.
     *
     * This is the Super-Admin-authorised counterpart of the Admin submission
     * route (Admin\ExamQuestionController@submit). It enforces the exact same
     * eligibility rules and performs the identical status transition
     * (draft -> pending). It never approves, publishes, or reassigns anything,
     * and it can only ever touch questions owned by the acting Super Admin
     * (admin_id === auth id). Review/approval remains a separate, explicit step.
     */
    public function submit(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        // Strict ownership: a Super Admin may only submit questions they own.
        $question = $this->ownedQuestion($question);
        $this->ensureDraftExam($question->exam);
        abort_unless($question->status === 'draft', 403);

        $question->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('super.admin.exam.questions.index', $question->exam_id)
            ->with('success', 'Question submitted for review.');
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
        abort_unless($exam->status === 'draft', 403, 'Questions can only be submitted for draft exams.');
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }
}
