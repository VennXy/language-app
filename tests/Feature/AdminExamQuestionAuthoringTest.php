<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminExamQuestionAuthoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_admin_question_routes(): void
    {
        $this->actingAs($this->user('student'))
            ->get(route('admin.exams.index'))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_access_admin_question_routes(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get(route('admin.exams.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_only_draft_exams_for_question_contribution(): void
    {
        $admin = $this->user('admin');
        $draft = $this->exam($this->user('super_admin'), 'draft');
        $this->exam($this->user('super_admin'), 'published');
        $this->exam($this->user('super_admin'), 'closed');

        $response = $this->actingAs($admin)->get(route('admin.exams.index'));

        $response->assertOk()->assertSee($draft->title)->assertDontSee('Published Exam')->assertDontSee('Closed Exam');
    }

    public function test_admin_creates_own_draft_question_and_cannot_choose_owner(): void
    {
        $admin = $this->user('admin');
        $other = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));

        $this->actingAs($admin)->post(route('admin.exams.questions.store', $exam), [
            ...$this->questionPayload(),
            'admin_id' => $other->id,
            'status' => 'approved',
        ])->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'admin_id' => $admin->id,
            'correct_option' => 'b',
            'status' => 'draft',
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);
        $this->assertDatabaseMissing('exam_questions', ['admin_id' => $other->id]);
    }

    public function test_admin_can_create_more_than_five_questions(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));
        foreach (range(1, 5) as $number) {
            $this->question($exam, $admin, ['question' => "Existing {$number}"]);
        }

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.store', $exam), $this->questionPayload(['question' => 'Sixth question']))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertSame(6, $exam->questions()->where('admin_id', $admin->id)->count());
    }

    public function test_admin_question_rejects_invalid_content(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.store', $exam), [
                'question' => '',
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'Z',
            ])
            ->assertSessionHasErrors(['question', 'correct_option']);

        $this->assertSame(0, $exam->questions()->count());
    }

    public function test_admin_can_edit_own_draft_question(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));
        $question = $this->question($exam, $admin);

        $this->actingAs($admin)
            ->put(route('admin.exams.questions.update', $question), $this->questionPayload(['question' => 'Updated question']))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'question' => 'Updated question', 'status' => 'draft']);
    }

    public function test_admin_cannot_edit_another_admins_question(): void
    {
        $question = $this->question($this->exam($this->user('super_admin')), $this->user('admin'));

        $this->actingAs($this->user('admin'))
            ->get(route('admin.exams.questions.edit', $question))
            ->assertNotFound();
    }

    #[DataProvider('nonEditableQuestionStates')]
    public function test_admin_cannot_edit_pending_or_approved_question(string $status): void
    {
        $admin = $this->user('admin');
        $question = $this->question($this->exam($this->user('super_admin')), $admin, ['status' => $status]);

        $this->actingAs($admin)
            ->get(route('admin.exams.questions.edit', $question))
            ->assertForbidden();
    }

    public static function nonEditableQuestionStates(): array
    {
        return ['pending' => ['pending'], 'approved' => ['approved']];
    }

    public function test_admin_can_edit_rejected_question_and_review_metadata_is_cleared(): void
    {
        $admin = $this->user('admin');
        $reviewer = $this->user('super_admin');
        $exam = $this->exam($reviewer);
        $question = $this->question($exam, $admin, [
            'status' => 'rejected',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
            'rejection_reason' => 'Please correct the translation.',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.exams.questions.update', $question), $this->questionPayload(['question' => 'Corrected question']))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertDatabaseHas('exam_questions', [
            'id' => $question->id,
            'question' => 'Corrected question',
            'status' => 'draft',
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => null,
        ]);
    }

    public function test_admin_can_delete_own_draft_question(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));
        $question = $this->question($exam, $admin);

        $this->actingAs($admin)
            ->delete(route('admin.exams.questions.destroy', $question))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $this->assertDatabaseMissing('exam_questions', ['id' => $question->id]);
    }

    #[DataProvider('nonDeletableQuestionStates')]
    public function test_admin_cannot_delete_pending_or_approved_question(string $status): void
    {
        $admin = $this->user('admin');
        $question = $this->question($this->exam($this->user('super_admin')), $admin, ['status' => $status]);

        $this->actingAs($admin)
            ->delete(route('admin.exams.questions.destroy', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id]);
    }

    public static function nonDeletableQuestionStates(): array
    {
        return ['pending' => ['pending'], 'approved' => ['approved']];
    }

    public function test_admin_can_submit_own_draft_question_for_review(): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'));
        $question = $this->question($exam, $admin);

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.submit', $question))
            ->assertRedirect(route('admin.exams.questions.index', $exam));

        $question->refresh();
        $this->assertSame('pending', $question->status);
        $this->assertNotNull($question->submitted_at);
    }

    public function test_admin_cannot_submit_another_admins_question(): void
    {
        $question = $this->question($this->exam($this->user('super_admin')), $this->user('admin'));

        $this->actingAs($this->user('admin'))
            ->post(route('admin.exams.questions.submit', $question))
            ->assertNotFound();
    }

    public function test_admin_cannot_submit_rejected_question_until_it_is_edited(): void
    {
        $admin = $this->user('admin');
        $question = $this->question($this->exam($this->user('super_admin')), $admin, ['status' => 'rejected']);

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.submit', $question))
            ->assertForbidden();

        $this->assertDatabaseHas('exam_questions', ['id' => $question->id, 'status' => 'rejected']);
    }

    #[DataProvider('unavailableExamStates')]
    public function test_admin_cannot_add_questions_to_published_or_closed_exam(string $status): void
    {
        $admin = $this->user('admin');
        $exam = $this->exam($this->user('super_admin'), $status);

        $this->actingAs($admin)
            ->post(route('admin.exams.questions.store', $exam), $this->questionPayload())
            ->assertForbidden();

        $this->assertSame(0, $exam->questions()->count());
    }

    public static function unavailableExamStates(): array
    {
        return ['published' => ['published'], 'closed' => ['closed']];
    }

    #[DataProvider('unavailableExamStates')]
    public function test_admin_cannot_edit_questions_for_published_or_closed_exam(string $status): void
    {
        $admin = $this->user('admin');
        $question = $this->question($this->exam($this->user('super_admin'), $status), $admin);

        $this->actingAs($admin)
            ->get(route('admin.exams.questions.edit', $question))
            ->assertForbidden();
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

    private function question(Exam $exam, User $admin, array $attributes = []): ExamQuestion
    {
        return ExamQuestion::create([
            'exam_id' => $exam->id,
            'admin_id' => $admin->id,
            ...$this->questionPayload(),
            'correct_option' => 'b',
            'status' => 'draft',
            ...$attributes,
        ]);
    }

    private function questionPayload(array $overrides = []): array
    {
        return array_merge([
            'question' => 'What does this phrase mean?',
            'option_a' => 'First answer',
            'option_b' => 'Second answer',
            'option_c' => 'Third answer',
            'option_d' => 'Fourth answer',
            'correct_option' => 'B',
        ], $overrides);
    }
}
