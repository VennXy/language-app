<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamQuestion;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the Training prerequisite gate for Exams and the tightened Question Set
 * rotation (a "Question Set" = the current exam's approved question bank):
 *  - Exams for a level unlock only after that level's Training is complete;
 *  - the gate is enforced server-side (direct URL / POST cannot bypass it);
 *  - retries draw a different unused set from the bank while unused questions
 *    remain, then begin a new cycle when the bank is exhausted;
 *  - in-progress attempts keep their snapshot;
 *  - scoring / legacy attempts stay compatible.
 *
 * Isolated test data only (RefreshDatabase + sqlite :memory: via phpunit.xml).
 */
class StudentExamTrainingRotationTest extends TestCase
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

    private function category(int $levelId): int
    {
        return DB::table('categories')->insertGetId([
            'level_id' => $levelId,
            'title' => 'General',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function lesson(Level $level, string $title): Lesson
    {
        return Lesson::create([
            'level_id' => $level->id,
            'category_id' => $this->category($level->id),
            'title' => $title,
            'type' => 'training',
            'score' => 0,
            'sort_order' => 0,
        ]);
    }

    private function completeLesson(User $student, Lesson $lesson): void
    {
        DB::table('user_progress')->insert([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
            'progress_percent' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function createQuestions(Exam $exam, int $approved): Collection
    {
        $created = collect();
        for ($i = 1; $i <= $approved; $i++) {
            $created->push(ExamQuestion::create([
                'exam_id' => $exam->id,
                'admin_id' => $this->user('admin')->id,
                'question' => "Question {$i}",
                'option_a' => "Option A {$i}",
                'option_b' => "Option B {$i}",
                'option_c' => "Option C {$i}",
                'option_d' => "Option D {$i}",
                'correct_option' => 'b',
                'status' => 'approved',
            ]));
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

    private function startAttempt(User $student, Exam $exam): ExamAttempt
    {
        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();

        return ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    private function snapshotIds(ExamAttempt $attempt): array
    {
        return $attempt->questions()->pluck('exam_question_id')->sort()->values()->all();
    }

    // §2/§9.1: Exams become available once the level's Training is complete.
    public function test_exams_become_available_after_training_completion(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $lesson = $this->lesson($a1, 'A1 Lesson 1');
        $exam = $this->exam('published', 'A1 Exam 01', $a1);
        $this->createQuestions($exam, 3);

        // Before completing training, starting the exam is refused (403).
        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertForbidden();
        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $exam->id)->count());

        // Complete the required training.
        $this->completeLesson($student, $lesson);

        // Now the exam is startable.
        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
    }

    // §7/§9.2: incomplete Training keeps the level's exams locked (UI + server).
    public function test_incomplete_training_keeps_exams_locked_in_ui_and_server(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $this->lesson($a1, 'A1 Lesson 1'); // required, not completed
        $exam = $this->exam('published', 'A1 Exam 01', $a1);
        $this->createQuestions($exam, 3);

        // UI: the index warns that training is required and offers no Start form.
        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertSee('Complete this level’s Training to unlock its Exams.')
            ->assertDontSee(route('student.exams.start', $exam));

        // Server: direct start is refused even though the exam is published.
        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertForbidden();
    }

    // §2/§9.3 + §7/§9.14: level selection locked state and server enforcement.
    public function test_level_selection_locked_state_and_courses_server_enforcement(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $a2 = $this->level('A2');
        $this->lesson($a1, 'A1 Lesson 1');
        $a2Exam = $this->exam('published', 'A2 Exam 01', $a2);
        $this->createQuestions($a2Exam, 3);

        // The level selection page marks the not-yet-accessible level Locked.
        $this->actingAs($student)
            ->get(route('select.level'))
            ->assertOk()
            ->assertSee('Locked');

        // A2 courses are not accessible while A1 is unfinished (server 403).
        $this->actingAs($student)->get(route('levels.courses', $a2->id))->assertForbidden();
    }

    // §2/§7: a "Take the Exams" invitation appears on the training page once complete.
    public function test_training_page_invites_learner_to_exams_after_completion(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $lesson = $this->lesson($a1, 'A1 Lesson 1');

        // Before completion: no invitation.
        $this->actingAs($student)
            ->get(route('levels.courses', $a1->id))
            ->assertOk()
            ->assertDontSee('Take the Exams');

        $this->completeLesson($student, $lesson);

        // After completion: invitation with a link to the exam page.
        $this->actingAs($student)
            ->get(route('levels.courses', $a1->id))
            ->assertOk()
            ->assertSee('Take the Exams')
            ->assertSee(route('student.exams.index'));
    }

    // §7: a level with no Training content does not block Exams (nothing required).
    public function test_level_without_lessons_does_not_block_exams(): void
    {
        $student = $this->user('student');
        $a1 = $this->level('A1');
        $exam = $this->exam('published', 'A1 Exam 01', $a1);
        $this->createQuestions($exam, 3);

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
    }

    // §4/§9.6+8: each retry uses a different unused set; used sets are not
    // repeated before the cycle is exhausted.
    public function test_retries_use_different_unused_sets_until_bank_exhausted(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, 6); // perAttempt = 3

        // Attempt 1 draws 3 of 6.
        $first = $this->startAttempt($student, $exam);
        $firstIds = $this->snapshotIds($first);
        $this->assertCount(3, $firstIds);

        // Finalize attempt 1 as failed so the next start is a retry.
        $first->update(['status' => 'submitted', 'correct_count' => 0, 'score_percent' => 0, 'passed' => false, 'submitted_at' => now()]);

        // Attempt 2 (retry) must avoid all previously used questions.
        $second = $this->startAttempt($student, $exam);
        $secondIds = $this->snapshotIds($second);
        $this->assertSame([], array_values(array_intersect($firstIds, $secondIds)), 'Retry must avoid questions used earlier in the cycle.');

        $second->update(['status' => 'submitted', 'correct_count' => 0, 'score_percent' => 0, 'passed' => false, 'submitted_at' => now()]);

        // Attempt 3: the bank is now exhausted (6 used across two attempts), so a
        // new cycle begins. It must still produce a valid, non-empty snapshot and
        // should differ from the immediately previous attempt.
        $third = $this->startAttempt($student, $exam);
        $thirdIds = $this->snapshotIds($third);
        $this->assertNotEmpty($thirdIds);
        $this->assertLessThanOrEqual(3, count($thirdIds));
        $this->assertSame([], array_values(array_intersect($secondIds, $thirdIds)), 'New cycle should prefer a different set from the previous attempt.');

        // All three attempts reference the same exam (slot identity stable).
        $this->assertSame($exam->id, $third->exam_id);
    }

    // §5/§9.7: refreshing an in-progress attempt reuses it and keeps its snapshot.
    public function test_refreshing_in_progress_attempt_keeps_same_question_set(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, 10);

        $attempt = $this->startAttempt($student, $exam);
        $original = $this->snapshotIds($attempt);

        // Re-open and re-start repeatedly; no new attempt, no snapshot change.
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($student)->get(route('student.exams.attempts.show', $attempt))->assertOk();
            $reused = $this->startAttempt($student, $exam);
            $this->assertSame($attempt->id, $reused->id);
        }

        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $student->id)->count());
        $this->assertSame($original, $this->snapshotIds($attempt->fresh()));
    }

    // §4.8/§9.10: a pool smaller than perAttempt is handled safely.
    public function test_smaller_than_target_bank_is_handled_safely(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, 2); // fewer than the 3-question target

        $attempt = $this->startAttempt($student, $exam);

        $this->assertSame(2, $attempt->questions()->count());
    }

    // §4.9: no eligible questions -> clear message, no broken attempt created.
    public function test_no_eligible_questions_shows_actionable_error_and_creates_no_attempt(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, 0);

        $this->actingAs($student)
            ->post(route('student.exams.start', $exam))
            ->assertSessionHasErrors('exam');

        $this->assertSame(0, ExamAttempt::query()->where('exam_id', $exam->id)->count());
    }

    // §6/§9.11: the next exam stays locked after a failed attempt (no advance).
    public function test_next_exam_stays_locked_after_a_failed_attempt(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 Exam 01', $level);
        $second = $this->exam('published', 'A1 Exam 02', $level);
        $this->createQuestions($first, 3);
        $this->createQuestions($second, 3);

        // Fail the first exam.
        $this->attemptWithSnapshots($first, $student, $first->questions()->where('status', 'approved')->take(3)->get(), passed: false);

        // The second exam remains locked on the server.
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertForbidden();
        $this->actingAs($student)->get(route('student.exams.show', $second))->assertForbidden();
    }

    // §6/§9.12: passing an exam unlocks only the next required exam.
    public function test_passing_an_exam_unlocks_only_the_next_exam(): void
    {
        $student = $this->user('student');
        $level = $this->level('A1');
        $first = $this->exam('published', 'A1 Exam 01', $level);
        $second = $this->exam('published', 'A1 Exam 02', $level);
        $third = $this->exam('published', 'A1 Exam 03', $level);
        foreach ([$first, $second, $third] as $exam) {
            $this->createQuestions($exam, 3);
        }

        // Pass the first exam.
        $this->attemptWithSnapshots($first, $student, $first->questions()->where('status', 'approved')->take(3)->get(), passed: true);

        // Second unlocks; third remains locked (its immediate predecessor, second, is unpassed).
        $this->actingAs($student)->post(route('student.exams.start', $second))->assertRedirect();
        $this->actingAs($student)->post(route('student.exams.start', $third))->assertForbidden();
    }

    // §6/§9.15: two learners have independent progression and set usage.
    public function test_two_learners_have_independent_question_set_usage(): void
    {
        $level = $this->level('A1');
        $exam = $this->exam('published', 'A1 Exam 01', $level);
        $this->createQuestions($exam, 6);

        $studentA = $this->user('student');
        $studentB = $this->user('student');

        $a = $this->startAttempt($studentA, $exam);
        $aIds = $this->snapshotIds($a);
        $a->update(['status' => 'submitted', 'correct_count' => 0, 'score_percent' => 0, 'passed' => false, 'submitted_at' => now()]);

        // Student B's very first attempt is unaffected by A's used set.
        $b = $this->startAttempt($studentB, $exam);
        $this->assertSame($exam->id, $b->exam_id);

        // A's retry avoids A's own prior set.
        $aRetry = $this->startAttempt($studentA, $exam);
        $this->assertSame([], array_values(array_intersect($aIds, $this->snapshotIds($aRetry))));

        $this->assertSame(1, ExamAttempt::query()->where('exam_id', $exam->id)->where('user_id', $studentA->id)->whereIn('status', ['submitted', 'expired'])->count());
    }

    // §5/§9.17: submitted attempts keep their original score/snapshot.
    public function test_submitted_attempt_score_and_snapshot_are_not_overwritten(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));
        $this->createQuestions($exam, 3);

        $attempt = $this->startAttempt($student, $exam);
        foreach ($attempt->questions()->orderBy('question_order')->get() as $question) {
            $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
                'question_id' => $question->id,
                'selected_option' => 'b',
            ])->assertRedirect();
        }
        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(3, $attempt->correct_count);
        $this->assertSame('100.00', $attempt->score_percent);
        $this->assertTrue($attempt->passed);
        $submittedAt = $attempt->submitted_at->toDateTimeString();
        $snapshot = $this->snapshotIds($attempt);

        // Re-submitting is idempotent: no overwrite of score or snapshot.
        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();
        $this->assertSame($submittedAt, $attempt->fresh()->submitted_at->toDateTimeString());
        $this->assertSame(3, $attempt->fresh()->correct_count);
        $this->assertSame($snapshot, $this->snapshotIds($attempt->fresh()));
    }

    // §5/§9.18: legacy attempts (no exam_question_id link) still score correctly.
    public function test_legacy_attempt_scoring_remains_compatible(): void
    {
        $student = $this->user('student');
        $exam = $this->exam('published', 'A1 Exam 01', $this->level('A1'));

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
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
                'question' => "Legacy {$order}",
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_option' => 'b',
            ]);
        }
        $attempt->questions()->where('question_order', '<=', 2)->update(['selected_option' => 'b']);

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();
        $attempt->refresh();

        $this->assertSame('submitted', $attempt->status);
        $this->assertSame(2, $attempt->correct_count);
        $this->assertSame('66.67', $attempt->score_percent);
        $this->assertTrue($attempt->passed); // 2/3 >= 60%
    }
}