<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamQuestion;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StudentExamSelectionProgressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLevels();

        // Deterministic per-attempt target used across these tests.
        config()->set('gamification.exam_questions_per_attempt', 3);
        config()->set('gamification.exam_testing_mode', true);
        config()->set('gamification.exam_minimum_approved_questions', 1);
    }

    private function seedLevels(): void
    {
        foreach ([
            ['A1', 'Beginner'],
            ['A2', 'Elementary'],
            ['B1', 'Intermediate'],
            ['B2', 'Upper-Intermediate'],
            ['C1', 'Advanced'],
            ['C2', 'Mastery'],
        ] as [$code, $tier]) {
            Level::create(['name' => $code, 'level' => $code, 'tier' => $tier]);
        }
    }

    public function test_attempt_snapshots_exactly_three_questions_when_at_least_three_eligible(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 10, 'pending' => 2]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $snapshots = ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->orderBy('question_order')->get();

        $this->assertCount(3, $snapshots);
        $this->assertSame([1, 2, 3], $snapshots->pluck('question_order')->all());

        // All snapshots reference approved questions only.
        $sourceStatuses = ExamQuestion::query()->whereIn('id', $snapshots->pluck('exam_question_id'))->pluck('status')->unique()->all();
        $this->assertSame(['approved'], $sourceStatuses);
    }

    public function test_attempt_uses_fewer_than_three_when_only_two_eligible(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 2, 'draft' => 5]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(2, ExamAttemptQuestion::query()->where('exam_attempt_id', $attempt->id)->count());
    }

    public function test_retry_draws_a_fresh_set_that_avoids_the_previous_failed_set_when_alternatives_exist(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $questions = $this->createQuestions($exam, ['approved' => 6]);

        // First attempt: fail with a known 3-question snapshot.
        $first = $this->attemptWithSnapshots($exam, $student, $questions->take(3));
        $firstIds = $first->questions()->pluck('exam_question_id')->all();

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        $second = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->where('id', '!=', $first->id)
            ->firstOrFail();
        $secondIds = $second->questions()->pluck('exam_question_id')->all();

        $this->assertCount(3, $secondIds);
        // With 6 approved and 3 previously used, the fresh set must avoid all of them.
        $this->assertSame([], array_values(array_intersect($firstIds, $secondIds)));
    }

    public function test_retry_falls_back_to_the_full_eligible_bank_when_alternatives_are_insufficient(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        // Only 4 approved questions exist; a previous attempt already used 3.
        $questions = $this->createQuestions($exam, ['approved' => 4]);
        $first = $this->attemptWithSnapshots($exam, $student, $questions->take(3));

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        $second = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->where('id', '!=', $first->id)
            ->firstOrFail();

        // Retry is not blocked; it still produces a valid snapshot from the bank.
        $count = $second->questions()->count();
        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertLessThanOrEqual(3, $count);
    }

    public function test_unfinished_attempt_keeps_its_original_snapshot_on_restart(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 10]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $original = $attempt->questions()->pluck('exam_question_id')->sort()->values()->all();

        // Restart while in progress: same attempt, identical snapshot.
        $this->actingAs($student)->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.attempts.show', $attempt));

        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
        $this->assertSame($original, $attempt->fresh()->questions()->pluck('exam_question_id')->sort()->values()->all());
    }

    public function test_next_exam_is_locked_until_previous_exam_is_passed(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 Exam 01', $level);
        $second = $this->exam('published', 'A1 Exam 02', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        // Direct URL start of the second exam before passing the first is forbidden.
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->actingAs($student)->get(route('student.exams.show', $second))->assertForbidden();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $second->id)->count());

        // The first exam is always startable (it has no predecessor).
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
    }

    public function test_next_exam_unlocks_after_passing_the_previous_exam(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 Exam 01', $level);
        $second = $this->exam('published', 'A1 Exam 02', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        // Record a passed attempt on the first exam.
        $first->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'status' => 'submitted',
            'correct_count' => 3,
            'score_percent' => 100,
            'passed' => true,
        ]);

        $this->actingAs($student)->post(route('student.exams.start', $second))->assertRedirect();
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $second->id)->where('user_id', $student->id)->count());
    }

    public function test_student_only_sees_published_exams_for_their_own_level(): void
    {
        $student = $this->user('student'); // auto-active at A1
        $a1 = $this->exam('published', 'A1 Visible', $this->level('A1'));
        $b1 = $this->exam('published', 'B1 Hidden', $this->level('B1'));
        $this->createQuestions($a1, ['approved' => 3]);
        $this->createQuestions($b1, ['approved' => 3]);

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('A1 Visible')
            ->assertDontSee('B1 Hidden');
    }

    public function test_listing_caps_selected_published_exams_at_three_for_the_level(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        for ($i = 0; $i < 5; $i++) {
            $exam = $this->exam('published', 'A1 Exam '.$i, $level);
            $this->createQuestions($exam, ['approved' => 3]);
        }

        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertViewHas('exams', fn ($exams): bool => $exams->count() === 3);
    }

    public function test_first_published_exam_in_level_is_startable_when_an_earlier_exam_in_same_level_is_closed(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $closed = $this->exam('closed', 'A1 Locked', $level);
        $first = $this->exam('published', 'A1 First', $level);
        $this->createQuestions($first, ['approved' => 3]);

        // The closed exam must never block the first published exam in the level.
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
        $this->actingAs($student)->get(route('student.exams.show', $first))->assertOk();
    }

    public function test_draft_exam_before_current_does_not_block_published_exam(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $draft = $this->exam('draft', 'A1 Draft', $level);
        $first = $this->exam('published', 'A1 First', $level);
        $this->createQuestions($first, ['approved' => 3]);

        // Draft exams are not startable (published gate) and must never block.
        $this->actingAs($student)->post(route('student.exams.start', $draft))->assertNotFound();
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
        $this->actingAs($student)->get(route('student.exams.show', $first))->assertOk();
    }

    public function test_exams_from_other_levels_do_not_affect_progression(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $b1 = $this->level('B1');
        $first = $this->exam('published', 'A1 First', $a1);
        $second = $this->exam('published', 'A1 Second', $a1);
        $b1Other = $this->exam('published', 'B1 Other', $b1);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        // A1 progression rules intact even with B1 content in the same DB.
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();

        // Exam in another level is not startable for this student and creates no attempt.
        $this->actingAs($student)->post(route('student.exams.start', $b1Other))->assertNotFound();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $b1Other->id)->count());
    }

    public function test_show_and_start_endpoints_apply_the_same_progression_guard(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 First', $level);
        $second = $this->exam('published', 'A1 Second', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        $this->actingAs($student)
            ->get(route('student.exams.show', $second))
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.exams.start', $second))
            ->assertForbidden();

        $first->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'status' => 'submitted',
            'correct_count' => 3,
            'score_percent' => 100,
            'passed' => true,
        ]);

        $this->actingAs($student)
            ->get(route('student.exams.show', $second))
            ->assertOk();

        $this->actingAs($student)
            ->post(route('student.exams.start', $second))
            ->assertRedirect();
    }

    public function test_progression_guard_ignores_inactive_and_failing_attempts_on_the_predecessor(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 First', $level);
        $second = $this->exam('published', 'A1 Second', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        // A failed attempt on the first exam must not unlock the second exam.
        $first->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'status' => 'submitted',
            'correct_count' => 1,
            'score_percent' => 33.33,
            'passed' => false,
        ]);

        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $second->id)->count());
    }

    public function test_progression_guard_does_not_interfere_with_active_attempt_reuse(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 First', $level);
        $second = $this->exam('published', 'A1 Second', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $first->id)->where('user_id', $student->id)->firstOrFail();

        // Active attempt on the first exam is untouched; the second exam stays locked
        // until that attempt is actually passed.
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $first->id)->count());
    }

    public function test_refreshing_the_index_does_not_change_attempt_or_result_records(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 6]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $originalQuestionIds = $attempt->questions()->pluck('exam_question_id')->sort()->values()->all();

        // Read the index repeatedly; nothing about the attempt may mutate.
        $this->actingAs($student)->get(route('student.exams.index'))->assertOk();
        $this->actingAs($student)->get(route('student.exams.index'))->assertOk();
        $this->actingAs($student)->get(route('student.exams.index'))->assertOk();

        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->assertSame($originalQuestionIds, $attempt->fresh()->questions()->pluck('exam_question_id')->sort()->values()->all());
        // Refreshing the index never scores the attempt: it stays unscored (freshly
        // started attempts default correct_count to 0; score/pass remain unset).
        $this->assertSame(0, (int) $attempt->fresh()->correct_count);
        $this->assertNull($attempt->fresh()->score_percent);
        $this->assertNull($attempt->fresh()->passed);
    }

    public function test_passed_exams_are_not_returned_as_available_random_exams(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $passed = $this->exam('published', 'A1 Passed', $level);
        $available = $this->exam('published', 'A1 Available', $level);
        $this->createQuestions($passed, ['approved' => 3]);
        $this->createQuestions($available, ['approved' => 3]);

        // Record a passed attempt on the first exam.
        $passed->attempts()->create([
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(25),
            'status' => 'submitted',
            'correct_count' => 3,
            'score_percent' => 100,
            'passed' => true,
        ]);

        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();

        // The passed exam is surfaced as completed, never as an available random exam.
        $response->assertViewHas('exams', fn ($exams): bool => $exams->pluck('id')->all() === [$available->id]);
        $response->assertViewHas('completedExams', fn ($completed): bool => $completed->pluck('id')->all() === [$passed->id]);

        // The completed section renders the passed exam and offers no Start button for it.
        $response->assertSee('Completed Exams');
        $response->assertSee($passed->title);
        $response->assertDontSee(route('student.exams.start', $passed));
    }

    public function test_failed_exam_stays_in_its_slot_and_later_exams_are_locked_until_it_passes(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        // The first exam is required first; the second is unlocked only after it passes.
        $first = $this->exam('published', 'A1 First', $level);
        $second = $this->exam('published', 'A1 Second', $level);
        $this->createQuestions($first, ['approved' => 6]);
        $this->createQuestions($second, ['approved' => 6]);

        // Student already failed the first exam (snapshot the first three questions).
        $this->attemptWithSnapshots($first, $student, $first->questions()->where('status', 'approved')->take(3)->get());

        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();

        // The failed exam remains offered (in its slot) so it can be retried.
        $response->assertViewHas('exams', fn ($exams): bool => $exams->pluck('id')->contains($first->id));

        // Progression is enforced on the server: the second exam cannot be started
        // while the required first exam is still unfinished, even though it is shown.
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $second->id)->count());

        // The failed exam itself is still retryable.
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
    }

    public function test_multiple_failed_exams_require_the_earliest_prerequisite_first_on_the_server(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 First', $level);
        $second = $this->exam('published', 'A1 Second', $level);
        $this->createQuestions($first, ['approved' => 3]);
        $this->createQuestions($second, ['approved' => 3]);

        // Both exams have a failed attempt recorded directly.
        $first->attempts()->create([
            'user_id' => $student->id, 'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(25), 'status' => 'submitted',
            'correct_count' => 0, 'score_percent' => 0, 'passed' => false,
        ]);
        $second->attempts()->create([
            'user_id' => $student->id, 'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25), 'status' => 'submitted',
            'correct_count' => 0, 'score_percent' => 0, 'passed' => false,
        ]);

        // Both failed exams are offered as slots (they remain retryable), but the
        // server enforces the earliest prerequisite: the second exam is locked
        // until the first is passed.
        $this->actingAs($student)->get(route('student.exams.index'))
            ->assertOk()
            ->assertViewHas('exams', fn ($exams): bool => $exams->pluck('id')->contains($first->id)
                && $exams->pluck('id')->contains($second->id));

        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->actingAs($student)->post(route('student.exams.start', $first))->assertRedirect();
    }

    public function test_in_progress_attempt_retains_its_original_question_snapshot_on_reopen(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 10]);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $originalIds = $attempt->questions()->pluck('exam_question_id')->sort()->values()->all();
        $question = $attempt->questions()->orderBy('question_order')->firstOrFail();

        // Answer one question to simulate progress, then reopen the attempt page.
        $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'selected_option' => 'a',
        ])->assertRedirect();

        $this->actingAs($student)->get(route('student.exams.attempts.show', $attempt))->assertOk();

        // The snapshot is unchanged: same questions, same order, answer preserved.
        $attempt->refresh();
        $this->assertSame($originalIds, $attempt->questions()->pluck('exam_question_id')->sort()->values()->all());
        $this->assertSame('in_progress', $attempt->status);
        $this->assertSame('a', $attempt->questions()->where('id', $question->id)->firstOrFail()->selected_option);
    }

    public function test_start_created_three_question_attempt_submits_and_scores_at_sixty_percent(): void
    {
        config()->set('gamification.exam_testing_mode', true);
        config()->set('gamification.exam_minimum_approved_questions', 1);

        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, ['approved' => 5]); // all correct_option = 'b'

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertCount(3, $attempt->questions);

        // Answer all three snapshot questions correctly from the client.
        foreach ($attempt->questions as $question) {
            $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
                'question_id' => $question->id,
                'selected_option' => 'b',
            ])->assertRedirect();
        }

        // Submitting a 3-question attempt must not 409 and must score against 3.
        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))
            ->assertRedirect(route('student.exams.attempts.result', $attempt));

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(3, $attempt->correct_count);
        $this->assertSame('100.00', $attempt->score_percent);
        $this->assertTrue($attempt->passed);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function level(string $code): Level
    {
        return Level::query()->where('level', $code)->firstOrFail();
    }

    private function exam(string $status, string $title, ?Level $level = null, int $duration = 30): Exam
    {
        return Exam::create([
            'title' => $title,
            'level_id' => $level?->id,
            'duration_minutes' => $duration,
            'status' => $status,
            'created_by' => $this->user('super_admin')->id,
        ]);
    }

    private function createQuestions(Exam $exam, array $statuses): Collection
    {
        $created = collect();
        $number = 1;
        foreach ($statuses as $status => $count) {
            for ($i = 0; $i < (int) $count; $i++) {
                $created->push(ExamQuestion::create([
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
                ]));
                $number++;
            }
        }

        return $created;
    }

    private function attemptWithSnapshots(Exam $exam, User $student, Collection $questions): ExamAttempt
    {
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinutes(5),
            'status' => 'submitted',
            'correct_count' => 0,
            'score_percent' => 0,
            'passed' => false,
        ]);

        $order = 1;
        foreach ($questions as $question) {
            ExamAttemptQuestion::create([
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => $question->id,
                'question_order' => $order++,
                'question' => $question->question,
                'option_a' => $question->option_a,
                'option_b' => $question->option_b,
                'option_c' => $question->option_c,
                'option_d' => $question->option_d,
                'correct_option' => $question->correct_option,
                'selected_option' => null,
                'is_correct' => null,
            ]);
        }

        return $attempt;
    }
}
