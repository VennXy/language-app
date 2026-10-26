<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminExamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_and_admin_cannot_access_exam_management(): void
    {
        foreach (['student', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('super.admin.exams.index'))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_view_exam_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('super.admin.exams.index'))
            ->assertOk();
    }

    public function test_exam_creator_is_always_the_authenticated_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($superAdmin)->post(route('super.admin.exams.store'), [
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $otherUser->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exams', [
            'title' => 'A1 Checkpoint',
            'created_by' => $superAdmin->id,
        ]);
        $this->assertDatabaseMissing('exams', ['created_by' => $otherUser->id]);
    }

    public function test_exam_cannot_be_published_with_zero_approved_questions(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);
        $this->createApprovedQuestions($exam, $owner, 0);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exams.publish', $exam))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'draft']);
    }

    public function test_exam_can_be_published_with_fewer_than_25_approved_questions_in_testing_mode(): void
    {
        config()->set('gamification.exam_testing_mode', true);
        config()->set('gamification.exam_minimum_approved_questions', 1);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);
        $this->createApprovedQuestions($exam, $owner, 3);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exams.publish', $exam))
            ->assertRedirect(route('super.admin.exams.index'));

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'published']);
    }

    public function test_production_mode_still_requires_25_approved_questions(): void
    {
        config()->set('gamification.exam_testing_mode', false);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);
        $this->createApprovedQuestions($exam, $owner, 24);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exams.publish', $exam))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'draft']);
    }

    public function test_exam_can_be_published_with_25_approved_questions(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $owner = User::factory()->create(['role' => 'admin']);
        $exam = Exam::create([
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);
        $this->createApprovedQuestions($exam, $owner, 25);

        $this->actingAs($superAdmin)
            ->post(route('super.admin.exams.publish', $exam))
            ->assertRedirect(route('super.admin.exams.index'));

        $this->assertDatabaseHas('exams', ['id' => $exam->id, 'status' => 'published']);
    }

    public function test_exam_with_attempt_history_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = Exam::create([
            'title' => 'A1 Checkpoint',
            'duration_minutes' => 30,
            'status' => 'draft',
            'created_by' => $superAdmin->id,
        ]);
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('super.admin.exams.destroy', $exam))
            ->assertRedirect(route('super.admin.exams.index'));

        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
        $this->assertDatabaseHas('exam_attempts', ['exam_id' => $exam->id]);
    }

    private function createApprovedQuestions(Exam $exam, User $owner, int $count): void
    {
        for ($number = 1; $number <= $count; $number++) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'admin_id' => $owner->id,
                'question' => "Question {$number}",
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => 'C',
                'option_d' => 'D',
                'correct_option' => 'a',
                'status' => 'approved',
            ]);
        }
    }
}
