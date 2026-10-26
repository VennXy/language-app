<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_student_exam_listing(): void
    {
        $this->get(route('student.exams.index'))->assertRedirect(route('login'));
    }

    public function test_admin_and_super_admin_cannot_access_student_exam_workflow(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('student.exams.index'))
                ->assertForbidden();
        }
    }

    public function test_student_sees_published_exams_only(): void
    {
        $student = $this->user('student');
        $published = $this->exam('published', 'Published JLPT Exam');
        $this->createQuestions($published, ['approved' => 25]);
        $this->exam('draft', 'Draft JLPT Exam');
        $this->exam('closed', 'Closed JLPT Exam');

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertSee('Start Exam')
            ->assertDontSee('Draft JLPT Exam')
            ->assertDontSee('Closed JLPT Exam');
    }

    public function test_student_dashboard_has_the_exam_link_without_a_duplicate_navbar_link(): void
    {
        $response = $this->actingAs($this->user('student'))->get(route('select.level'));

        $response->assertOk()->assertSee('Exams', false);

        // The shared student shell sidebar legitimately links to Exams (student navigation rail).
        // Strip it and verify the page content itself still has exactly one Exams link —
        // i.e. no duplicate chrome/content link beyond the sidebar.
        $htmlWithoutSidebar = preg_replace('/<aside class="sk-side".*?<\/aside>/s', '', $response->getContent());
        $this->assertSame(1, substr_count($htmlWithoutSidebar, 'href="'.route('student.exams.index').'"'));
    }

    public function test_student_cannot_view_or_start_draft_or_closed_exam_by_direct_url(): void
    {
        $student = $this->user('student');

        foreach (['draft', 'closed'] as $status) {
            $exam = $this->exam($status);
            $this->actingAs($student)->get(route('student.exams.show', $exam))->assertNotFound();
            $this->post(route('student.exams.start', $exam))->assertNotFound();
        }
    }

    public function test_student_cannot_start_published_exam_with_zero_approved_questions(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 0, 'pending' => 2, 'draft' => 2, 'rejected' => 2]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertSame(0, ExamAttempt::query()->count());
        $this->assertSame(0, ExamAttemptQuestion::query()->count());
    }

    public function test_student_can_start_with_fewer_than_25_approved_questions_in_testing_mode(): void
    {
        config()->set('gamification.exam_testing_mode', true);
        config()->set('gamification.exam_minimum_approved_questions', 1);

        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 5, 'pending' => 2, 'draft' => 2, 'rejected' => 2]);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertRedirect();

        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->first();
        $this->assertNotNull($attempt);
        // Testing mode allows starting with fewer than the production 25;
        // the attempt snapshots up to the per-attempt target (3) of the 5 approved.
        $this->assertSame(3, ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->count());
    }

    public function test_start_creates_attempt_and_exactly_three_question_snapshots(): void
    {
        $student = $this->user('student');
        $otherUser = $this->user('student');
        $exam = $this->exam('published', 'N5 Fundamentals', 45);
        $this->createQuestions($exam, ['approved' => 27, 'pending' => 3, 'draft' => 2, 'rejected' => 2]);
        $startedAt = now()->startOfSecond();
        $this->travelTo($startedAt);

        $response = $this->actingAs($student)->post(route('student.exams.start', $exam), [
            'user_id' => $otherUser->id,
            'started_at' => '2001-01-01 00:00:00',
            'expires_at' => '2001-01-01 00:01:00',
            'status' => 'submitted',
        ]);

        $attempt = ExamAttempt::query()->firstOrFail();
        $response->assertRedirect(route('student.exams.attempts.show', $attempt));
        $this->assertSame($student->id, $attempt->user_id);
        $this->assertNotSame($otherUser->id, $attempt->user_id);
        $this->assertSame('in_progress', $attempt->status);
        $this->assertTrue($startedAt->equalTo($attempt->started_at));
        $this->assertTrue($startedAt->copy()->addMinutes(45)->equalTo($attempt->expires_at));

        $snapshots = $attempt->questions()->orderBy('question_order')->get();
        $this->assertCount(3, $snapshots);
        $this->assertSame(range(1, 3), $snapshots->pluck('question_order')->all());

        $sourceQuestions = ExamQuestion::query()->whereIn('id', $snapshots->pluck('exam_question_id'))->get()->keyBy('id');
        foreach ($snapshots as $snapshot) {
            $source = $sourceQuestions->get($snapshot->exam_question_id);
            $this->assertNotNull($source);
            $this->assertSame('approved', $source->status);
            $this->assertSame($source->question, $snapshot->question);
            $this->assertSame($source->option_a, $snapshot->option_a);
            $this->assertSame($source->option_b, $snapshot->option_b);
            $this->assertSame($source->option_c, $snapshot->option_c);
            $this->assertSame($source->option_d, $snapshot->option_d);
            $this->assertSame($source->correct_option, $snapshot->correct_option);
            $this->assertNull($snapshot->selected_option);
            $this->assertNull($snapshot->is_correct);
        }

        $firstSnapshot = $snapshots->first();
        $originalSnapshotText = $firstSnapshot->question;
        ExamQuestion::query()->whereKey($firstSnapshot->exam_question_id)->update(['question' => 'Changed after attempt']);
        $this->assertSame($originalSnapshotText, $firstSnapshot->fresh()->question);
    }

    public function test_published_exam_with_25_approved_questions_can_be_started(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 25]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_second_start_returns_existing_in_progress_attempt_without_duplicate_snapshots(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 25]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()->firstOrFail();
        $this->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.attempts.show', $attempt));

        $this->assertSame(1, ExamAttempt::query()->count());
        $this->assertSame(3, ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->count());
    }

    public function test_submitted_or_expired_attempt_can_be_retaken_with_a_new_attempt(): void
    {
        $student = $this->user('student');
        foreach (['submitted', 'expired'] as $status) {
            $exam = $this->exam('published', ucfirst($status).' Exam');
            $this->createQuestions($exam, ['approved' => 25]);
            $this->attempt($exam, $student, $status);

            $this->actingAs($student)->post(route('student.exams.start', $exam))
                ->assertRedirect();

            // Repeat attempts are allowed: a finished attempt yields a brand-new one.
            $this->assertSame(2, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
        }
    }

    public function test_active_in_progress_attempt_is_reused_not_duplicated(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 25]);
        $active = $this->attempt($exam, $student, 'in_progress');

        $this->actingAs($student)->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.attempts.show', $active));

        // The active attempt is reused; no duplicate is created.
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
    }

    public function test_student_can_access_only_their_own_attempt_page(): void
    {
        $exam = $this->exam('published');
        $attempt = $this->attempt($exam, $this->user('student'), 'in_progress');

        $this->actingAs($this->user('student'))
            ->get(route('student.exams.attempts.show', $attempt))
            ->assertNotFound();
    }

    public function test_listing_displays_existing_attempt_state_without_a_start_button(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published');
        $this->createQuestions($exam, ['approved' => 25]);
        $attempt = $this->attempt($exam, $student, 'submitted');

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee(route('student.exams.attempts.show', $attempt))
            ->assertDontSee(route('student.exams.show', $exam));
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function exam(string $status, ?string $title = null, int $duration = 30): Exam
    {
        return Exam::create([
            'title' => $title ?? ucfirst($status).' Exam',
            'duration_minutes' => $duration,
            'status' => $status,
            'created_by' => $this->user('super_admin')->id,
        ]);
    }

    /** @param array<string, int> $statuses */
    private function createQuestions(Exam $exam, array $statuses): void
    {
        $number = 1;
        foreach ($statuses as $status => $count) {
            // Guard: range(1, 0) returns [1, 0] in PHP, so a zero count must be skipped explicitly.
            for ($i = 0; $i < $count; $i++) {
                ExamQuestion::create([
                    'exam_id' => $exam->id,
                    'admin_id' => $this->user('admin')->id,
                    'question' => "Question {$number}",
                    'option_a' => "Option A {$number}",
                    'option_b' => "Option B {$number}",
                    'option_c' => "Option C {$number}",
                    'option_d' => "Option D {$number}",
                    'correct_option' => 'b',
                    'status' => $status,
                    'submitted_at' => $status === 'pending' ? now() : null,
                ]);
                $number++;
            }
        }
    }

    private function attempt(Exam $exam, User $student, string $status): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'status' => $status,
        ]);
    }
}
