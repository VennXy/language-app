<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamQuestion;
use App\Models\ExamSlot;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * End-to-end coverage for the stable exam-slot workflow:
 *  - slots stay stable across refreshes/navigation,
 *  - a failed exam stays in its slot until passed,
 *  - retries draw a fresh question set,
 *  - in-progress attempts keep their snapshot,
 *  - progression/eligibility are enforced on the server,
 *  - passing replaces only the passed slot,
 *  - exam-number labels are removed from the student UI,
 *  - existing attempts/scoring stay compatible.
 */
class StudentExamSlotStabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLevels();

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

    private function attemptWithSnapshots(Exam $exam, User $student, Collection $questions, bool $passed = false): ExamAttempt
    {
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinutes(5),
            'status' => 'submitted',
            'correct_count' => $passed ? $questions->count() : 0,
            'score_percent' => $passed ? 100 : 0,
            'passed' => $passed,
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

    /** @return list<int> the exam ids currently shown in the index slots. */
    private function indexExamIds(User $student): array
    {
        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();

        return $response->viewData('exams')->pluck('id')->all();
    }

    // 1. Refreshing the exam index repeatedly preserves the same 3 exam IDs.
    public function test_refreshing_the_index_repeatedly_preserves_the_same_three_exam_ids(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        for ($i = 0; $i < 5; $i++) {
            $this->createQuestions($this->exam('published', 'A1 Exam '.$i, $level), ['approved' => 3]);
        }

        $first = $this->indexExamIds($student);
        $this->assertCount(3, $first);

        // Many subsequent refreshes / navigation return the identical slot ids.
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $this->indexExamIds($student));
        }

        // The selection is persisted as exactly three slots.
        $this->assertSame(3, ExamSlot::query()->where('user_id', $student->id)->count());
    }

    // 2. Failing Exam A keeps Exam A in its slot.
    public function test_failing_exam_a_keeps_exam_a_in_its_slot(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam A', $level);
        $this->createQuestions($a, ['approved' => 6]);

        // Establish the initial slots, then fail Exam A.
        $before = $this->indexExamIds($student);
        $this->assertContains($a->id, $before);

        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get());

        $after = $this->indexExamIds($student);
        $this->assertContains($a->id, $after, 'The failed exam must remain in its slot.');
        $this->assertSame($before, $after, 'A failed exam must not be replaced by another exam.');
    }

    // 3. Retrying Exam A generates a different question set when alternatives exist.
    public function test_retrying_exam_a_generates_a_different_question_set_when_alternatives_exist(): void
    {
        $student = $this->user('student');
        $a = $this->exam('published', 'A1 Exam A', $this->level('A1'));
        $questions = $this->createQuestions($a, ['approved' => 6]);

        $first = $this->attemptWithSnapshots($a, $student, $questions->take(3));
        $firstIds = $first->questions()->pluck('exam_question_id')->sort()->values()->all();

        $this->actingAs($student)->post(route('student.exams.start', $a))->assertRedirect();

        $second = ExamAttempt::query()
            ->where('exam_id', $a->id)
            ->where('user_id', $student->id)
            ->where('id', '!=', $first->id)
            ->firstOrFail();
        $secondIds = $second->questions()->pluck('exam_question_id')->sort()->values()->all();

        $this->assertCount(3, $secondIds);
        $this->assertSame([], array_values(array_intersect($firstIds, $secondIds)), 'The retry must avoid the previous failed set.');
        // The retry still belongs to Exam A.
        $this->assertSame($a->id, $second->exam_id);
    }

    // 4. Refreshing an in-progress attempt preserves its question snapshot.
    public function test_refreshing_an_in_progress_attempt_preserves_its_question_snapshot(): void
    {
        $student = $this->user('student');
        $a = $this->exam('published', 'A1 Exam A', $this->level('A1'));
        $this->createQuestions($a, ['approved' => 10]);

        $this->actingAs($student)->post(route('student.exams.start', $a))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $a->id)->where('user_id', $student->id)->firstOrFail();
        $original = $attempt->questions()->pluck('exam_question_id')->sort()->values()->all();

        // Answer one, then reopen and also hit the index; nothing mutates.
        $question = $attempt->questions()->orderBy('question_order')->firstOrFail();
        $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
            'question_id' => $question->id,
            'selected_option' => 'a',
        ])->assertRedirect();

        $this->actingAs($student)->get(route('student.exams.attempts.show', $attempt))->assertOk();
        $this->indexExamIds($student);

        $attempt->refresh();
        $this->assertSame($original, $attempt->questions()->pluck('exam_question_id')->sort()->values()->all());
        $this->assertSame('in_progress', $attempt->status);
        $this->assertNull($attempt->score_percent);
        $this->assertNull($attempt->passed);
    }

    // 5. Exam B and Exam C cannot be started while Exam A is the required prerequisite.
    public function test_exam_b_and_c_cannot_be_started_while_exam_a_is_the_required_prerequisite(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam A', $level);
        $b = $this->exam('published', 'A1 Exam B', $level);
        $c = $this->exam('published', 'A1 Exam C', $level);
        foreach ([$a, $b, $c] as $exam) {
            $this->createQuestions($exam, ['approved' => 3]);
        }

        // All three are visible as slots, but B and C are progression-locked.
        $ids = $this->indexExamIds($student);
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
        $this->assertContains($c->id, $ids);

        // Direct URL access to start or view B/C is refused by the server.
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertForbidden();
        $this->actingAs($student)->get(route('student.exams.show', $b))->assertForbidden();
        $this->actingAs($student)->post(route('student.exams.start', $c))->assertForbidden();
        $this->actingAs($student)->get(route('student.exams.show', $c))->assertForbidden();

        $this->assertSame(0, ExamAttempt::query()->whereIn('exam_id', [$b->id, $c->id])->count());

        // Exam A is always startable.
        $this->actingAs($student)->post(route('student.exams.start', $a))->assertRedirect();
    }

    // 6 & 7. Passing Exam A replaces only its slot; the other two stay unchanged.
    public function test_passing_exam_a_replaces_only_its_slot_and_preserves_the_other_two(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        // Four eligible exams: three occupy the initial slots, the fourth waits.
        $a = $this->exam('published', 'A1 Exam A', $level);
        $b = $this->exam('published', 'A1 Exam B', $level);
        $c = $this->exam('published', 'A1 Exam C', $level);
        $d = $this->exam('published', 'A1 Exam D', $level);
        foreach ([$a, $b, $c, $d] as $exam) {
            $this->createQuestions($exam, ['approved' => 3]);
        }

        $before = $this->indexExamIds($student);
        $this->assertCount(3, $before);
        $this->assertContains($a->id, $before);
        // The waiting exam D is not in the initial three.
        $this->assertNotContains($d->id, $before);

        // Pass Exam A.
        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);

        $after = $this->indexExamIds($student);

        // Exam A is gone (moved to completed), D filled its freed slot.
        $this->assertNotContains($a->id, $after, 'A passed exam must leave the available slots.');
        $this->assertContains($d->id, $after, 'The freed slot must be filled with a new eligible exam.');

        // The other two slots are unchanged.
        $othersBefore = array_values(array_diff($before, [$a->id]));
        $othersAfter = array_values(array_diff($after, [$d->id]));
        sort($othersBefore);
        sort($othersAfter);
        $this->assertSame($othersBefore, $othersAfter, 'The other two slots must remain unchanged during replacement.');

        // The reconciled selection is still persisted as exactly three slots.
        $this->assertSame(3, ExamSlot::query()->where('user_id', $student->id)->count());
    }

    // 8. Passed exams do not reappear as eligible unfinished exams.
    public function test_passed_exams_do_not_reappear_as_eligible_unfinished_exams(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam A', $level);
        $this->createQuestions($a, ['approved' => 3]);

        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);

        // Repeated refreshes never surface the passed exam as available again.
        for ($i = 0; $i < 3; $i++) {
            $this->assertNotContains($a->id, $this->indexExamIds($student));
        }

        // It is surfaced under Completed instead, with no Start button.
        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();
        $response->assertViewHas('completedExams', fn ($completed): bool => $completed->pluck('id')->contains($a->id));
        $response->assertDontSee(route('student.exams.start', $a));
    }

    // 9. Direct URL access cannot bypass progression restrictions.
    public function test_direct_url_access_cannot_bypass_progression_restrictions(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam A', $level);
        $b = $this->exam('published', 'A1 Exam B', $level);
        $this->createQuestions($a, ['approved' => 3]);
        $this->createQuestions($b, ['approved' => 3]);

        // Even if a slot for B is somehow persisted, starting/viewing B directly is blocked.
        ExamSlot::create(['user_id' => $student->id, 'exam_id' => $b->id, 'position' => 1]);

        $this->actingAs($student)->post(route('student.exams.start', $b))->assertForbidden();
        $this->actingAs($student)->get(route('student.exams.show', $b))->assertForbidden();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $b->id)->count());

        // Wrong-level exams are rejected outright (404) even by direct URL.
        $b1 = $this->exam('published', 'B1 Other', $this->level('B1'));
        $this->createQuestions($b1, ['approved' => 3]);
        $this->actingAs($student)->post(route('student.exams.start', $b1))->assertNotFound();
        $this->actingAs($student)->get(route('student.exams.show', $b1))->assertNotFound();
    }

    // 10. Exam-number labels are removed from the intended student-facing UI.
    public function test_exam_number_labels_are_removed_from_student_facing_ui(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        // A title that carries a numeric exam-number suffix.
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $this->createQuestions($a, ['approved' => 3]);

        // Index: shows the generic label without the numeric suffix.
        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('A1 Exam')
            ->assertDontSee('A1 Exam 01');

        // Detail page.
        $this->actingAs($student)
            ->get(route('student.exams.show', $a))
            ->assertOk()
            ->assertSee('A1 Exam')
            ->assertDontSee('A1 Exam 01');

        // Attempt + result pages use the generic label too.
        $this->actingAs($student)->post(route('student.exams.start', $a))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $a->id)->where('user_id', $student->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('student.exams.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('A1 Exam')
            ->assertDontSee('A1 Exam 01');

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.exams.attempts.result', $attempt))
            ->assertOk()
            ->assertSee('A1 Exam')
            ->assertDontSee('A1 Exam 01');

        // The stored title is untouched (display-only transform).
        $this->assertSame('A1 Exam 01', $a->fresh()->title);
    }

    // 11. Existing attempts, results, scoring and legacy snapshots remain compatible.
    public function test_existing_attempts_scoring_and_legacy_snapshots_remain_compatible(): void
    {
        $student = $this->user('student');
        $a = $this->exam('published', 'A1 Exam A', $this->level('A1'));
        $this->createQuestions($a, ['approved' => 3]);

        // A legacy 3-question attempt with no exam_question_id link still scores.
        $attempt = ExamAttempt::create([
            'exam_id' => $a->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
            'status' => 'in_progress',
        ]);
        foreach (range(1, 3) as $order) {
            ExamAttemptQuestion::create([
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => null,
                'question_order' => $order,
                'question' => "Legacy snapshot {$order}",
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_option' => 'b',
            ]);
        }

        // Answer two of three correctly -> 66.67% (>= 60% threshold on 3 questions).
        $attempt->questions()->where('question_order', '<=', 2)->update(['selected_option' => 'b']);
        $attempt->questions()->where('question_order', 3)->update(['selected_option' => 'a']);

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();
        $attempt->refresh();

        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(2, $attempt->correct_count);
        $this->assertSame('66.67', $attempt->score_percent);
        $this->assertTrue($attempt->passed);

        // Submitting again is idempotent and does not mutate the finalized result.
        $submittedAt = $attempt->submitted_at->toDateTimeString();
        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();
        $this->assertSame($submittedAt, $attempt->fresh()->submitted_at->toDateTimeString());
        $this->assertSame(2, $attempt->fresh()->correct_count);
    }

    // §1/§8.7: slots stay in curriculum order; a replaced slot does not scramble order.
    public function test_slots_stay_in_curriculum_order_and_a_replaced_slot_does_not_scramble_the_order(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        $c = $this->exam('published', 'A1 Exam 03', $level);
        $d = $this->exam('published', 'A1 Exam 04', $level);
        foreach ([$a, $b, $c, $d] as $exam) {
            $this->createQuestions($exam, ['approved' => 3]);
        }

        // Initial three slots are the first three exams in curriculum (id) order.
        $before = $this->indexExamIds($student);
        $this->assertSame([$a->id, $b->id, $c->id], $before);

        // Pass the first exam; its slot is replaced by the next eligible exam.
        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);

        // The list must remain in curriculum order [B, C, D], never [D, B, C].
        $after = $this->indexExamIds($student);
        $this->assertSame([$b->id, $c->id, $d->id], $after);

        // And it stays stable on further refreshes.
        $this->assertSame([$b->id, $c->id, $d->id], $this->indexExamIds($student));
    }

    // §3/§8.1-2: later exams are locked in the UI and only the current exam is startable.
    public function test_index_shows_locked_state_for_later_exams_and_start_only_for_the_current_one(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        $c = $this->exam('published', 'A1 Exam 03', $level);
        foreach ([$a, $b, $c] as $exam) {
            $this->createQuestions($exam, ['approved' => 3]);
        }

        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();

        // Only the current (first) exam offers a Start form in the UI.
        $response->assertSee(route('student.exams.start', $a));
        // Locked exams render no Start/View form and are visibly marked Locked.
        $response->assertDontSee(route('student.exams.start', $b));
        $response->assertDontSee(route('student.exams.start', $c));
        $response->assertDontSee(route('student.exams.show', $b));
        $response->assertSee('Locked');

        // The server agrees with the UI.
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertForbidden();
        $this->actingAs($student)->post(route('student.exams.start', $c))->assertForbidden();

        // After passing the first exam, the second becomes startable in the UI.
        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);

        $response = $this->actingAs($student)->get(route('student.exams.index'))->assertOk();
        $response->assertSee(route('student.exams.start', $b));
        $response->assertDontSee(route('student.exams.start', $c)); // C still locked (B not passed)
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertRedirect();
    }

    // §2/§8.3: after unlocking Exam 02 and failing it, the learner must retry Exam 02 itself.
    public function test_failing_the_second_exam_requires_retrying_the_second_exam_itself(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        $c = $this->exam('published', 'A1 Exam 03', $level);
        foreach ([$a, $b, $c] as $exam) {
            $this->createQuestions($exam, ['approved' => 6]);
        }

        // Pass Exam 01 to unlock Exam 02, then fail Exam 02.
        $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);
        $this->attemptWithSnapshots($b, $student, $b->questions()->where('status', 'approved')->take(3)->get());

        // Exam 02 remains the required retry; Exam 03 stays locked.
        $this->assertContains($b->id, $this->indexExamIds($student));
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertRedirect();
        $this->actingAs($student)->post(route('student.exams.start', $c))->assertForbidden();

        // The retry attempt belongs to Exam 02, not Exam 03.
        $retry = ExamAttempt::query()->where('exam_id', $b->id)->where('user_id', $student->id)->orderByDesc('id')->first();
        $this->assertNotNull($retry);
        $this->assertSame('in_progress', $retry->status);
    }

    // §6/§8.9: one student's progression never affects another's.
    public function test_student_progression_and_slots_are_isolated_between_students(): void
    {
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        foreach ([$a, $b] as $exam) {
            $this->createQuestions($exam, ['approved' => 3]);
        }

        $studentA = $this->user('student');
        $studentB = $this->user('student');

        // Both students load the index (populates each student's own slots).
        $this->indexExamIds($studentA);
        $this->indexExamIds($studentB);

        // Student A passes the first exam, then reloads to reconcile their slots.
        $this->attemptWithSnapshots($a, $studentA, $a->questions()->where('status', 'approved')->take(3)->get(), passed: true);
        $this->indexExamIds($studentA);
        $this->indexExamIds($studentB);

        // Student A can start the second exam; Student B still cannot.
        $this->actingAs($studentA)->post(route('student.exams.start', $b))->assertRedirect();
        $this->actingAs($studentB)->post(route('student.exams.start', $b))->assertForbidden();

        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $b->id)->where('user_id', $studentB->id)->count());
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $b->id)->where('user_id', $studentA->id)->count());

        // Slots are independent per student.
        $this->assertSame(1, ExamSlot::query()->where('user_id', $studentA->id)->count()); // only B remains for A
        $this->assertSame(2, ExamSlot::query()->where('user_id', $studentB->id)->count()); // A and B for B
    }

    // §5/§8.12: a valid pass through the real submit flow unlocks the next exam (no false 403).
    public function test_valid_pass_through_the_real_submit_flow_unlocks_the_next_exam_without_a_false_403(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        $this->createQuestions($a, ['approved' => 3]);
        $this->createQuestions($b, ['approved' => 3]);

        // Before passing, the second exam is locked (403).
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertForbidden();

        // Start and fully pass the first exam through the real answer+submit flow.
        $this->actingAs($student)->post(route('student.exams.start', $a))->assertRedirect();
        $attempt = ExamAttempt::query()->where('exam_id', $a->id)->where('user_id', $student->id)->firstOrFail();
        foreach ($attempt->questions()->orderBy('question_order')->get() as $question) {
            $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
                'question_id' => $question->id,
                'selected_option' => 'b', // every seeded question's correct_option is 'b'
            ])->assertRedirect();
        }
        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertTrue($attempt->passed);

        // Now the second exam must NOT return 403.
        $this->actingAs($student)->post(route('student.exams.start', $b))->assertRedirect();
        $this->actingAs($student)->get(route('student.exams.show', $b))->assertOk();
    }

    // §5/§8.10: historical attempts and their snapshots are never modified by the index or progress.
    public function test_historical_attempts_and_snapshots_are_not_modified_by_index_or_progress(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $a = $this->exam('published', 'A1 Exam 01', $level);
        $b = $this->exam('published', 'A1 Exam 02', $level);
        $this->createQuestions($a, ['approved' => 3]);
        $this->createQuestions($b, ['approved' => 3]);

        // A finalized (failed) historical attempt on Exam A.
        $historical = $this->attemptWithSnapshots($a, $student, $a->questions()->where('status', 'approved')->take(3)->get());
        $snapshot = fn () => $historical->questions()
            ->orderBy('question_order')
            ->get(['id', 'exam_question_id', 'question', 'option_a', 'correct_option', 'selected_option', 'is_correct'])
            ->map(fn ($q) => $q->getAttributes())
            ->all();

        $snapshotBefore = $snapshot();

        // Hit the index several times.
        $this->indexExamIds($student);
        $this->indexExamIds($student);

        $historical->refresh();
        $this->assertSame($snapshotBefore, $snapshot(), 'Historical snapshots must not change.');
        $this->assertSame('submitted', $historical->status);
        $this->assertFalse($historical->passed);
    }
}

