<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuperAdminExamQuestionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_submit_own_draft_question_for_review(): void
    {
        $superAdmin = $this->user('super_admin');
        $exam = $this->exam($superAdmin);
        $question = $this->question($exam, $superAdmin);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertRedirect(route('super.admin.exam.questions.index', $exam));

        $question->refresh();
        $this->assertSame('pending', $question->status);
        $this->assertNotNull($question->submitted_at);
    }

    public function test_super_admin_cannot_submit_a_question_owned_by_another_user(): void
    {
        $superAdmin = $this->user('super_admin');
        $admin = $this->user('admin');
        $exam = $this->exam($superAdmin);
        $question = $this->question($exam, $admin);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertNotFound();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'draft']);
    }

    #[DataProvider('nonDraftQuestionStates')]
    public function test_super_admin_cannot_submit_non_draft_question(string $status): void
    {
        $superAdmin = $this->user('super_admin');
        $exam = $this->exam($superAdmin);
        $question = $this->question($exam, $superAdmin, ['status' => $status]);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => $status]);
    }

    public static function nonDraftQuestionStates(): array
    {
        return ['pending' => ['pending'], 'approved' => ['approved'], 'rejected' => ['rejected']];
    }

    #[DataProvider('nonDraftExamStates')]
    public function test_super_admin_cannot_submit_question_for_non_draft_exam(string $status): void
    {
        $superAdmin = $this->user('super_admin');
        $exam = $this->exam($superAdmin, $status);
        $question = $this->question($exam, $superAdmin);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'draft']);
    }

    public static function nonDraftExamStates(): array
    {
        return ['published' => ['published'], 'closed' => ['closed']];
    }

    public function test_admin_cannot_use_the_super_admin_submission_route(): void
    {
        $superAdmin = $this->user('super_admin');
        $admin = $this->user('admin');
        $exam = $this->exam($superAdmin);
        $question = $this->question($exam, $admin);

        $this->actingAs($admin)
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'draft']);
    }

    public function test_student_cannot_use_the_super_admin_submission_route(): void
    {
        $superAdmin = $this->user('super_admin');
        $exam = $this->exam($superAdmin);
        $question = $this->question($exam, $superAdmin);

        $this->actingAs($this->user('student'))
            ->post(route('super.admin.exam.questions.submit', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'draft']);
    }

    public function test_admin_still_can_submit_own_draft_question_via_admin_route(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));
        $question = $this->question($exam, $admin);

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.submit', $question))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'pending']);
    }

    public function test_admin_cannot_submit_another_admins_question(): void
    {
        $exam = $this->exam($this->user('super_admin'));
        $owner = $this->user('admin');
        $question = $this->question($exam, $owner);

        $this->actingAs($this->user('admin'))
            ->post(route('admin.exams.questions.submit', $question))
            ->assertNotFound();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'draft']);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function exam(User $creator, string $status = 'draft'): Exam
    {
        return Exam::create([
            'title' => ucfirst($status).' Exam',
            'duration_minutes' => 30,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }

    private function question(Exam $exam, User $owner, array $attributes = []): ExamQuestion
    {
        return ExamQuestion::create([
            'exam_id' => $exam->id,
            'admin_id' => $owner->id,
            'question' => 'What does this phrase mean?',
            'option_a' => 'First answer',
            'option_b' => 'Second answer',
            'option_c' => 'Third answer',
            'option_d' => 'Fourth answer',
            'correct_option' => 'b',
            'status' => 'draft',
            ...$attributes,
        ]);
    }
}
