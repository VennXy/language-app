<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentPracticeTestRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_answer_advances_and_increments_the_initial_counter_once(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 3);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'correct' => true,
            'lesson_completed' => false,
            'session_ended' => false,
            'phase' => 'initial',
            'question_count' => 3,
            'answered_count' => 1,
            'retry_remaining' => 0,
            'next_question_index' => 1,
        ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 1
            && $state['answered_ids'] === [$questions[0]]
            && $state['retry_ids'] === []);
    }

    public function test_wrong_answer_is_queued_for_retry_and_advances_to_the_next_question(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 3);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson([
            'correct' => false,
            'session_ended' => false,
            'phase' => 'initial',
            'question_count' => 3,
            'answered_count' => 0,
            'retry_remaining' => 1,
            // The user continues through the set instead of staying on this question.
            'next_question_index' => 1,
        ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 1
            && $state['answered_ids'] === []
            && $state['retry_ids'] === [$questions[0]]
            && !$state['session_ended']);
    }

    public function test_skip_is_queued_for_retry_and_advances_to_the_next_question(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 3);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questions[0]])
            ->assertOk()->assertJson([
                'accepted' => true,
                'session_ended' => false,
                'phase' => 'initial',
                'question_count' => 3,
                'retry_remaining' => 1,
                'next_question_index' => 1,
            ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 1
            && $state['skipped_ids'] === [$questions[0]]
            && $state['retry_ids'] === [$questions[0]]);
    }

    public function test_repeated_failures_do_not_duplicate_retries_or_increment_the_counter_twice(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 2);
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 5]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['retry_remaining' => 1, 'next_question_index' => 1]);

        // A duplicate submission for the same question must not queue it again or move the counter.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson([
            'retry_remaining' => 1,
            'question_count' => 2,
            'next_question_index' => 1,
        ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 1
            && $state['retry_ids'] === [$questions[0]]);
    }

    public function test_initial_round_finishes_then_failed_questions_are_retried_at_the_end(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 3);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[1],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['phase' => 'initial', 'next_question_index' => 2]);

        // Finishing the initial round must not end the session: the retry round starts.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[2],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'correct' => true,
            'lesson_completed' => false,
            'session_ended' => false,
            'phase' => 'retry',
            'question_count' => 3,
            'answered_count' => 2,
            'retry_remaining' => 1,
            'next_question_index' => 0,
        ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 3
            && $state['answered_ids'] === [$questions[1], $questions[2]]
            && $state['retry_ids'] === [$questions[0]]
            && !$state['lesson_completed']);

        // The exercise is not marked completed while a retry question remains.
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'in_progress',
        ]);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('questionFlow', fn (array $flow): bool => $flow['phase'] === 'retry'
                && $flow['retry_remaining'] === 1
                && $flow['question_count'] === 3)
            ->assertViewHas('currentQuestionPosition', fn ($position): bool => $position === 0)
            ->assertSee('data-phase="retry"', false)
            ->assertSee('Retry · 1 left', false);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'correct' => true,
            'lesson_completed' => true,
            'phase' => 'completed',
            'retry_remaining' => 0,
            'answered_count' => 3,
        ]);

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10]);

        // Duplicate completion submissions stay idempotent.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['already_accepted' => true]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    }

    public function test_failed_retry_stays_queued_until_answered_correctly(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 2);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422);
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questions[1]])
            ->assertOk()->assertJson([
                'phase' => 'retry',
                'retry_remaining' => 2,
                'next_question_index' => 0,
                'session_ended' => false,
            ]);

        // Wrong retry answer: stays in the queue (no duplicate) and moves to the back so it
        // is asked again in a later retry cycle.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson([
            'lesson_completed' => false,
            'phase' => 'retry',
            'question_count' => 2,
            'retry_remaining' => 2,
            'next_question_index' => 1,
        ]);
        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['retry_ids'] === [$questions[1], $questions[0]]);

        // Correct retry answer: removed from the queue, but the lesson stays incomplete.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[1],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'lesson_completed' => false,
            'phase' => 'retry',
            'retry_remaining' => 1,
            'answered_count' => 1,
            'next_question_index' => 0,
        ]);

        // Skipping during the retry round keeps the question queued.
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questions[0]])
            ->assertOk()->assertJson([
                'accepted' => true,
                'lesson_completed' => false,
                'phase' => 'retry',
                'retry_remaining' => 1,
                'next_question_index' => 0,
            ]);
        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['retry_ids'] === [$questions[0]]
            && $state['skipped_ids'] === [$questions[1], $questions[0]]);

        // Only a correct answer clears it and completes the exercise.
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'lesson_completed' => true,
            'phase' => 'completed',
            'retry_remaining' => 0,
        ]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    }

    public function test_duplicate_correct_submission_does_not_change_counters_or_hearts(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 2);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true, 'next_question_index' => 1]);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'correct' => true,
            'already_accepted' => true,
            'answered_count' => 1,
            'retry_remaining' => 0,
            'next_question_index' => 1,
        ]);

        $this->assertLessonState($student, $lesson, fn (array $state): bool => $state['next_index'] === 1
            && $state['answered_ids'] === [$questions[0]]
            && $state['retry_ids'] === []);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5]);
    }

    public function test_running_out_of_hearts_keeps_the_retry_queue_and_resumes_after_refill(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 1);
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 1]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson([
            'hearts' => 0,
            'session_ended' => true,
            'retry_remaining' => 1,
        ]);

        $stored = json_decode(DB::table('user_progress')
            ->where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->value('lesson_state'), true);
        $this->assertSame([$questions[0]], $stored['retry_ids']);
        $this->assertFalse((bool) $stored['lesson_completed']);

        // Hearts refill: the learner resumes into the retry round instead of a dead end.
        DB::table('users')->where('id', $student->id)->update(['hearts' => 5]);
        session()->forget('lesson_question_progress.' . $student->id . '.' . $lesson->id);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => !$state['session_ended']
                && $state['retry_ids'] === [$questions[0]])
            ->assertViewHas('questionFlow', fn (array $flow): bool => $flow['phase'] === 'retry')
            ->assertViewHas('currentQuestionPosition', fn ($position): bool => $position === 0);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['lesson_completed' => true, 'phase' => 'completed']);
    }

    public function test_legacy_questions_finished_state_resumes_into_the_retry_round(): void
    {
        [, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 2);
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 3]);

        // A durable state written by the old behaviour: session ended when the initial set
        // ran out, with a skipped question that was never retried.
        $sessionKey = 'lesson_question_progress.' . $student->id . '.' . $lesson->id;
        $this->actingAs($student)->withSession([$sessionKey => [
            'next_index' => 2,
            'answered_ids' => [$questions[0]],
            'skipped_ids' => [$questions[1]],
            'lesson_completed' => false,
            'session_ended' => true,
            'end_reason' => 'questions_finished',
            'hearts' => 3,
        ]]);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => !$state['session_ended']
                && $state['retry_ids'] === [$questions[1]]
                && $state['next_index'] === 2)
            ->assertViewHas('questionFlow', fn (array $flow): bool => $flow['phase'] === 'retry'
                && $flow['retry_remaining'] === 1)
            ->assertViewHas('currentQuestionPosition', fn ($position): bool => $position === 1);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[1],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['lesson_completed' => true]);
    }

    public function test_exam_mode_shares_the_retry_flow_and_keeps_its_type(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $questions = $this->createQuestions($lesson, 2);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('user.training', ['id' => $lesson->id, 'type' => 'exam']))
            ->assertOk()
            ->assertViewHas('type', 'exam');

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson([
            'phase' => 'initial',
            'retry_remaining' => 1,
            'next_question_index' => 1,
        ]);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[1],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'lesson_completed' => false,
            'phase' => 'retry',
            'retry_remaining' => 1,
            'next_question_index' => 0,
        ]);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questions[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson([
            'lesson_completed' => true,
            'phase' => 'completed',
            'retry_remaining' => 0,
        ]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);

        // The exam-mode courses listing still resolves for the same level.
        $this->get(route('levels.courses', ['id' => $levelId, 'type' => 'exam']))->assertOk();
    }

    private function assertLessonState(User $student, Lesson $lesson, \Closure $callback): void
    {
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', $callback);
    }

    /** @return array<int> */
    private function createQuestions(Lesson $lesson, int $count): array
    {
        $questionIds = [];
        for ($index = 0; $index < $count; $index++) {
            $questionIds[] = $this->createQuestion($lesson, 'yes');
        }

        return $questionIds;
    }

    private function createLesson(): array
    {
        $now = now();
        $levelId = DB::table('levels')->insertGetId([
            'name' => 'A1', 'level' => 'A1', 'tier' => 'A1', 'description' => 'A1 level',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $levelId, 'title' => 'A1 Category', 'sort_order' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $lesson = Lesson::create([
            'level_id' => $levelId, 'category_id' => $categoryId, 'title' => 'Retry Lesson',
            'type' => 'training', 'score' => 0, 'sort_order' => 0,
        ]);

        return [$levelId, $lesson];
    }

    private function createQuestion(Lesson $lesson, string $answer): int
    {
        return DB::table('vocabularies')->insertGetId([
            'lesson_id' => $lesson->id,
            'word' => 'test',
            'reading' => 'test',
            'meaning' => 'test',
            'question_type' => 'word_selection',
            'question' => 'Choose the answer.',
            'options' => 'yes,no',
            'correct_answer' => $answer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
