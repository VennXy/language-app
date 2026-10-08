<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamAnsweringTest extends TestCase
{
    use RefreshDatabase;

    public function test_attempt_page_displays_snapshot_questions_without_correctness_data(): void
    {
        [$student, $attempt] = $this->attemptWithQuestions();

        $response = $this->actingAs($student)->get(route('student.exams.attempts.show', $attempt));

        $response->assertOk()->assertSee('Question 1 of 25')->assertSee('Question snapshot 1');
        $response->assertDontSee('correct_option')->assertDontSee('is_correct');
    }

    public function test_student_can_save_an_answer_for_own_snapshot_only(): void
    {
        [$student, $attempt] = $this->attemptWithQuestions();
        $question = $attempt->questions()->firstOrFail();

        $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'selected_option' => 'b',
            'is_correct' => true,
            'correct_count' => 25,
            'passed' => true,
        ])->assertRedirect(route('student.exams.attempts.show', ['attempt' => $attempt, 'question' => 1]));

        $this->assertDatabaseHas('exam_attempt_questions', [
            'id' => $question->id,
            'selected_option' => 'b',
            'is_correct' => null,
        ]);
        $this->assertDatabaseHas('exam_attempts', ['id' => $attempt->id, 'status' => 'in_progress', 'passed' => null]);
    }

    public function test_submission_scores_from_server_snapshots_and_is_idempotent(): void
    {
        [$student, $attempt] = $this->attemptWithQuestions();
        $attempt->questions()->where('question_order', '<=', 15)->update(['selected_option' => 'b']);
        $attempt->questions()->where('question_order', '>', 15)->update(['selected_option' => 'a']);

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))
            ->assertRedirect(route('student.exams.attempts.result', $attempt));
        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(15, $attempt->correct_count);
        $this->assertSame('60.00', $attempt->score_percent);
        $this->assertTrue($attempt->passed);
        $firstSubmittedAt = $attempt->submitted_at->toDateTimeString();

        $this->post(route('student.exams.attempts.submit', $attempt))->assertRedirect(route('student.exams.attempts.result', $attempt));
        $this->assertSame($firstSubmittedAt, $attempt->fresh()->submitted_at->toDateTimeString());
        $this->assertSame(15, $attempt->fresh()->correct_count);
        $this->assertSame(15, $attempt->questions()->where('is_correct', true)->count());

        $this->get(route('student.exams.attempts.result', $attempt))
            ->assertOk()->assertSee('60.00%')->assertSee('PASS')->assertSee('Correct answer: B — Option B 1');
    }

    public function test_less_than_sixty_percent_fails_and_unanswered_questions_are_wrong(): void
    {
        [$student, $attempt] = $this->attemptWithQuestions();
        $attempt->questions()->where('question_order', '<=', 14)->update(['selected_option' => 'b']);
        $attempt->questions()->where('question_order', '=', 15)->update(['selected_option' => null]);

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertSame(14, $attempt->correct_count);
        $this->assertFalse($attempt->passed);
        $this->assertSame(14, $attempt->questions()->where('is_correct', true)->count());
        $this->assertSame(11, $attempt->questions()->where('is_correct', false)->count());
    }

    public function test_expired_attempt_is_finalized_as_expired_and_rejects_late_answers(): void
    {
        [$student, $attempt] = $this->attemptWithQuestions();
        $question = $attempt->questions()->firstOrFail();
        $attempt->questions()->where('question_order', '<=', 15)->update(['selected_option' => 'b']);
        $question->update(['selected_option' => 'c']);
        $attempt->expires_at = now()->subSecond();
        $attempt->save();

        $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'selected_option' => 'a',
        ])->assertRedirect(route('student.exams.attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame('expired', $attempt->status);
        $this->assertSame(14, $attempt->correct_count);
        $this->assertSame('c', $question->fresh()->selected_option);
    }

    public function test_other_student_cannot_answer_or_view_an_attempt(): void
    {
        [, $attempt] = $this->attemptWithQuestions();
        $otherStudent = User::factory()->create(['role' => 'student']);

        $this->actingAs($otherStudent)->get(route('student.exams.attempts.show', $attempt))->assertNotFound();
        $this->post(route('student.exams.attempts.answer', $attempt), [
            'question_id' => $attempt->questions()->firstOrFail()->id,
            'selected_option' => 'a',
        ])->assertNotFound();
    }

    public function test_non_student_cannot_submit_student_attempt(): void
    {
        [, $attempt] = $this->attemptWithQuestions();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('student.exams.attempts.submit', $attempt))->assertForbidden();
    }

    /** @return array{User, ExamAttempt} */
    private function attemptWithQuestions(): array
    {
        $student = User::factory()->create(['role' => 'student']);
        $creator = User::factory()->create(['role' => 'super_admin']);
        $exam = Exam::create([
            'title' => 'Test Exam',
            'duration_minutes' => 30,
            'status' => 'published',
            'created_by' => $creator->id,
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'status' => 'in_progress',
        ]);

        foreach (range(1, 25) as $order) {
            ExamAttemptQuestion::create([
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => null,
                'question_order' => $order,
                'question' => "Question snapshot {$order}",
                'option_a' => "Option A {$order}",
                'option_b' => "Option B {$order}",
                'option_c' => "Option C {$order}",
                'option_d' => "Option D {$order}",
                'correct_option' => 'b',
            ]);
        }

        return [$student, $attempt];
    }
}
