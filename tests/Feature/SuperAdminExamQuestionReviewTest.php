<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminExamQuestionReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_admin_cannot_access_review_pages_or_actions(): void
    {
        $exam = $this->exam();
        $question = $this->question($exam);

        foreach (['student', 'admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('super.admin.exam.questions.exams'))
                ->assertForbidden();

            $this->actingAs($this->user($role))
                ->post(route('super.admin.exam.questions.approve', $question))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_view_exam_question_review(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get(route('super.admin.exam.questions.exams'))
            ->assertOk();
    }

    public function test_review_exam_list_shows_all_status_counts_from_database(): void
    {
        $exam = $this->exam();
        $owner = $this->user('admin');
        foreach (['draft', 'pending', 'pending', 'approved', 'rejected'] as $status) {
            $this->question($exam, $owner, ['status' => $status]);
        }

        $this->actingAs($this->user('super_admin'))
            ->get(route('super.admin.exam.questions.exams'))
            ->assertOk()
            ->assertSee($exam->title)
            ->assertSee('5')
            ->assertSee('2');
    }

    public function test_pending_question_review_page_shows_content_owner_and_submission_date(): void
    {
        $exam = $this->exam();
        $owner = $this->user('admin');
        $question = $this->question($exam, $owner, ['submitted_at' => now()->subMinutes(5)]);

        $this->actingAs($this->user('super_admin'))
            ->get(route('super.admin.exam.questions.index', $exam))
            ->assertOk()
            ->assertSee($question->question)
            ->assertSee($question->option_a)
            ->assertSee($question->option_b)
            ->assertSee($question->option_c)
            ->assertSee($question->option_d)
            ->assertSee($owner->email)
            ->assertSee('Pending');
    }

    public function test_super_admin_can_approve_pending_question_and_notification_goes_to_owner(): void
    {
        $reviewer = $this->user('super_admin');
        $owner = $this->user('admin');
        $exam = $this->exam();
        $question = $this->question($exam, $owner, ['rejection_reason' => 'stale reason']);

        $this->actingAs($reviewer)
            ->post(route('super.admin.exam.questions.approve', $question))
            ->assertRedirect(route('super.admin.exam.questions.index', $exam));

        $question->refresh();
        $this->assertSame('approved', $question->status);
        $this->assertSame($reviewer->id, $question->reviewed_by);
        $this->assertNotNull($question->reviewed_at);
        $this->assertNull($question->rejection_reason);
        $this->assertSame('What does this phrase mean?', $question->question);
        $this->assertSame($owner->id, $question->admin_id);
        $this->assertSame(1, $owner->notifications()->count());
        $this->assertSame(0, $reviewer->notifications()->count());

        $notification = $owner->notifications()->firstOrFail();
        $this->assertSame('exam_question_approved', $notification->type);
        $this->assertSame('exam_question_approved', $notification->data['type']);
        $this->assertSame($exam->id, $notification->data['exam_id']);
        $this->assertSame($exam->title, $notification->data['exam_title']);
        $this->assertSame($question->id, $notification->data['question_id']);
        $this->assertStringContainsString($exam->title, $notification->data['message']);
    }

    public function test_approved_question_cannot_be_reviewed_again_or_rejected(): void
    {
        $reviewer = $this->user('super_admin');
        $owner = $this->user('admin');
        $question = $this->question($this->exam(), $owner);
        $this->actingAs($reviewer)->post(route('super.admin.exam.questions.approve', $question));

        $this->post(route('super.admin.exam.questions.approve', $question))->assertSessionHasErrors('question');
        $this->post(route('super.admin.exam.questions.reject', $question), ['rejection_reason' => 'No'])->assertSessionHasErrors('question');

        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_rejection_requires_a_reason_and_does_not_change_pending_question(): void
    {
        $question = $this->question($this->exam());

        $this->actingAs($this->user('super_admin'))
            ->post(route('super.admin.exam.questions.reject', $question), ['rejection_reason' => ''])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'pending']);
        $this->assertSame(0, $question->owner->notifications()->count());
    }

    public function test_super_admin_can_reject_pending_question_and_notification_contains_reason(): void
    {
        $reviewer = $this->user('super_admin');
        $owner = $this->user('admin');
        $exam = $this->exam();
        $question = $this->question($exam, $owner);
        $reason = 'Please correct the meaning of option C.';

        $this->actingAs($reviewer)
            ->post(route('super.admin.exam.questions.reject', $question), ['rejection_reason' => $reason])
            ->assertRedirect(route('super.admin.exam.questions.index', $exam));

        $question->refresh();
        $this->assertSame('rejected', $question->status);
        $this->assertSame($reason, $question->rejection_reason);
        $this->assertSame($reviewer->id, $question->reviewed_by);
        $this->assertNotNull($question->reviewed_at);
        $this->assertSame('What does this phrase mean?', $question->question);
        $this->assertSame($owner->id, $question->admin_id);
        $this->assertSame(1, $owner->notifications()->count());

        $notification = $owner->notifications()->firstOrFail();
        $this->assertSame('exam_question_rejected', $notification->type);
        $this->assertSame('exam_question_rejected', $notification->data['type']);
        $this->assertSame($exam->id, $notification->data['exam_id']);
        $this->assertSame($exam->title, $notification->data['exam_title']);
        $this->assertSame($question->id, $notification->data['question_id']);
        $this->assertSame($reason, $notification->data['rejection_reason']);
        $this->assertStringContainsString($exam->title, $notification->data['message']);
        $this->assertSame(0, $reviewer->notifications()->count());
    }

    public function test_rejected_question_cannot_be_rejected_again_or_approved(): void
    {
        $reviewer = $this->user('super_admin');
        $owner = $this->user('admin');
        $question = $this->question($this->exam(), $owner);
        $this->actingAs($reviewer)->post(route('super.admin.exam.questions.reject', $question), ['rejection_reason' => 'Fix it.']);

        $this->post(route('super.admin.exam.questions.reject', $question), ['rejection_reason' => 'Another reason.'])->assertSessionHasErrors('question');
        $this->post(route('super.admin.exam.questions.approve', $question))->assertSessionHasErrors('question');

        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_pending_questions_on_published_or_closed_exams_can_still_be_reviewed(): void
    {
        $reviewer = $this->user('super_admin');
        $owner = $this->user('admin');

        foreach (['published', 'closed'] as $status) {
            $exam = $this->exam($status);
            $question = $this->question($exam, $owner);

            $this->actingAs($reviewer)
                ->get(route('super.admin.exam.questions.index', $exam))
                ->assertOk()
                ->assertSee($question->question);
            $this->post(route('super.admin.exam.questions.approve', $question))->assertRedirect();
        }

        $this->assertSame(2, $owner->notifications()->count());
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function exam(string $status = 'draft'): Exam
    {
        return Exam::create([
            'title' => ucfirst($status).' Exam',
            'duration_minutes' => 30,
            'status' => $status,
            'created_by' => $this->user('super_admin')->id,
        ]);
    }

    private function question(Exam $exam, ?User $owner = null, array $attributes = []): ExamQuestion
    {
        $owner ??= $this->user('admin');

        return ExamQuestion::create([
            'exam_id' => $exam->id,
            'admin_id' => $owner->id,
            'question' => 'What does this phrase mean?',
            'option_a' => 'First answer',
            'option_b' => 'Second answer',
            'option_c' => 'Third answer',
            'option_d' => 'Fourth answer',
            'correct_option' => 'b',
            'status' => 'pending',
            'submitted_at' => now()->subHour(),
            ...$attributes,
        ]);
    }
}
