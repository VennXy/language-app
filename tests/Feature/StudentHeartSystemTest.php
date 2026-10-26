<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StudentHeartSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_start_with_five_hearts_and_only_students_see_the_navbar_balance(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('navbar-fire-count')
            ->assertSee('daily-streak-count')
            ->assertSee('❤️')
            ->assertSee('5/5')
            ->assertSee('💎')
            ->assertSee('200')
            ->assertSee('heart-popover');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 200]);

        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('home'))
                ->assertOk()
                ->assertDontSee('navbar-heart-count')
                ->assertDontSee('navbar-gem-total');
        }
    }

    public function test_registration_grants_gems_once_and_login_or_refresh_does_not_grant_more(): void
    {
        $email = 'gem-student@example.test';

        $this->post(route('register'), [
            'first_name' => 'Gem',
            'last_name' => 'Student',
            'date_of_birth' => '2000-01-01',
            'email' => $email,
            'password' => 'ValidPassword123!',
            'password_confirmation' => 'ValidPassword123!',
        ])->assertRedirect('/');

        $student = User::where('email', $email)->firstOrFail();
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'hearts' => 5,
            'gems' => 200,
            'streak_count' => 0,
        ]);
        $this->get(route('home'))->assertSee('200');
        $this->get(route('home'))->assertSee('200');
        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login'), ['email' => $email, 'password' => 'ValidPassword123!'])->assertRedirect();
        $this->get(route('home'))->assertSee('200');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'gems' => 200]);
    }

    public function test_one_heart_regenerates_after_five_minutes(): void
    {
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 3,
            'gems' => 123,
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($student)->get(route('home'))->assertSee('4/5');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 4, 'gems' => 123, 'streak_count' => 37]);
    }

    public function test_three_hearts_regenerate_to_four_then_five_without_changing_currency(): void
    {
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 3,
            'gems' => 81,
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]); 

        $this->actingAs($student)->get(route('student.hearts.status'))
            ->assertOk()->assertJsonPath('hearts', 4);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 4, 'gems' => 81, 'streak_count' => 37]);

        DB::table('users')->where('id', $student->id)->update([
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);
        $this->get(route('student.hearts.status'))
            ->assertOk()->assertJsonPath('hearts', 5);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 81, 'streak_count' => 37]);
    }

    public function test_heart_popover_renders_server_countdown_and_compact_progress(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 3,
            'heart_regeneration_started_at' => now()->subSeconds(43),
        ]);

        $this->actingAs($student)->get(route('home'))
            ->assertOk()
            ->assertSee('data-heart-seconds="257"', false)
            ->assertSee('04:17')
            ->assertSee('heart-progress-track')
            ->assertSee('aria-valuenow="14"', false)
            ->assertSee('Next Heart');

        $this->get(route('student.hearts.status'))
            ->assertOk()
            ->assertJsonPath('hearts', 3)
            ->assertJsonPath('seconds_to_next_heart', 257)
            ->assertJsonPath('seconds_to_full_refill', 557);
    }

    public function test_status_endpoint_recalculates_hearts_after_countdown_expires(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 3,
            'heart_regeneration_started_at' => now()->subSeconds(299),
        ]);

        $this->actingAs($student)->get(route('student.hearts.status'))
            ->assertOk()
            ->assertJsonPath('hearts', 3)
            ->assertJsonPath('seconds_to_next_heart', 1);

        DB::table('users')->where('id', $student->id)->update([
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);
        $this->get(route('student.hearts.status'))
            ->assertOk()
            ->assertJsonPath('hearts', 4);
    }

    public function test_regeneration_caps_at_five_and_clears_the_timer(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 4,
            'heart_regeneration_started_at' => now()->subMinutes(20),
        ]);

        $this->actingAs($student)->get(route('home'))->assertSee('5/5');
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'hearts' => 5,
            'heart_regeneration_started_at' => null,
        ]);
    }

    public function test_regeneration_persists_across_page_reload_and_logout_login(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 2,
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($student)->get(route('home'))->assertSee('3/5');
        $this->get(route('home'))->assertSee('3/5');
        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login'), ['email' => $student->email, 'password' => 'password'])->assertRedirect();
        $this->get(route('home'))->assertSee('3/5');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 3]);
    }

    public function test_returning_to_courses_with_zero_hearts_does_not_refill_them(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 23]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 0, 'gems' => 67]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->get(route('levels.courses', $levelId))->assertOk();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 0, 'gems' => 67, 'streak_count' => 23]);
    }

    public function test_wrong_answer_heart_loss_persists_after_leaving_and_reopening_lesson(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update(['gems' => 91]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 4]);

        $this->get(route('levels.courses', $levelId))->assertOk();
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 4);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 4, 'gems' => 91, 'streak_count' => 37]);
    }

    public function test_skip_heart_loss_persists_after_leaving_and_reopening_lesson(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update(['gems' => 91]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $firstQuestionId])
            ->assertOk()->assertJson(['hearts' => 4]);

        $this->get(route('levels.courses', $levelId))->assertOk();
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 4
                && $state['next_index'] === 1);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 4, 'gems' => 91, 'streak_count' => 37]);
    }

    public function test_zero_heart_restart_does_not_refill_or_start_the_lesson(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 0]);
        $questionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)
            ->get(route('user.training', ['id' => $lesson->id, 'restart' => 1]))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 0 && $state['session_ended']);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
        ])->assertStatus(409)->assertJson(['hearts' => 0, 'session_ended' => true]);
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questionId])
            ->assertStatus(409)->assertJson(['hearts' => 0, 'session_ended' => true]);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 0]);
    }

    public function test_regenerated_heart_resumes_saved_question_position(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 1]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');
        $learningItemId = DB::table('lesson_vocabulary_items')->insertGetId([
            'lesson_id' => $lesson->id,
            'japanese' => 'こんにちは', 'reading' => 'こんにちは', 'english' => 'hello',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('lesson_vocabulary_questions')->insert([
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $firstQuestionId,
                'stage' => 'usage', 'stage_order' => 0,
            ],
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $secondQuestionId,
                'stage' => 'review', 'stage_order' => 1,
            ],
        ]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))
            ->assertViewHas('isTeaching', true);
        $this->post(route('user.lesson.learning.complete', [$lesson, $learningItemId]))->assertRedirect();
        $this->get(route('user.training', $lesson->id))->assertViewHas('isTeaching', false);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $secondQuestionId,
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 0, 'session_ended' => true]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 37, 'gems' => 200]);

        DB::table('users')->where('id', $student->id)->update([
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 1
                // The wrong answer advanced past the second question and queued it for retry.
                && $state['next_index'] === 2
                && $state['retry_ids'] === [$secondQuestionId]
                && $state['answered_ids'] === [$firstQuestionId]
                && $state['learning_item_index'] === 1
                && $state['taught_learning_item_ids'] === [$learningItemId]
                && !$state['session_ended']);
            $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 1, 'streak_count' => 37, 'gems' => 200]);
    }

    public function test_restart_with_remaining_hearts_preserves_the_balance(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 2]);

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->get(route('user.training', ['id' => $lesson->id, 'restart' => 1]))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 2
                && $state['next_index'] === 0
                && !$state['session_ended']);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 2]);
    }

    public function test_gem_balance_is_separate_and_refill_cost_is_configurable(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 42]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 2,
            'gems' => 100,
            'heart_regeneration_started_at' => now(),
        ]);

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->post(route('student.hearts.refill'), [
            'hearts' => 0,
            'gems' => 0,
            'streak_count' => 9999,
        ])
            ->assertRedirect()
            ->assertSessionHas('gem_refill_success');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 50, 'streak_count' => 42]);
    }

    public function test_ui_uses_configured_refill_cost(): void
    {
        Config::set('gamification.heart_refill_gem_cost', 37);
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 4,
            'heart_regeneration_started_at' => now(),
        ]);

        $this->actingAs($student)->get(route('home'))
            ->assertOk()
            ->assertSee('id="gem-heart-refill-button"', false)
            ->assertSee('37', false)
            ->assertSee('Refill Hearts');
    }

    public function test_insufficient_gem_refill_button_stays_clickable_and_server_rejects(): void
    {
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 0,
            'gems' => 30,
            'heart_regeneration_started_at' => now(),
        ]);

        $this->actingAs($student);
        $response = $this->get(route('home'))->assertOk();
        $this->assertStringNotContainsString(
            'id="gem-heart-refill-button" disabled',
            $response->getContent()
        );
        $this->assertStringContainsString('💎 50', $response->getContent());

        $this->post(route('student.hearts.refill'), [
            'gems' => 5000,
            'hearts' => 5,
            'streak_count' => 0,
            'user_id' => 99999,
        ])->assertRedirect()
            ->assertSessionHas('gem_refill_error', 'Not enough Gems. You need 50 Gems to refill your hearts.');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'hearts' => 0,
            'gems' => 30,
            'streak_count' => 37,
        ]);
    }

    public function test_practice_courses_show_gem_completion_reward_and_keep_score(): void
    {
        [$levelId] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('levels.courses', $levelId))
            ->assertOk()
            ->assertSee('Complete this learning to earn +50 Gems')
            ->assertSee('Score:')
            ->assertDontSee('Streak:');
    }

    public function test_admin_and_super_admin_cannot_use_student_gem_refill_or_status(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $staff = User::factory()->create(['role' => $role]);

            $this->actingAs($staff)
                ->post(route('student.hearts.refill'))
                ->assertForbidden();
            $this->get(route('student.hearts.status'))
                ->assertForbidden();

            $this->assertDatabaseHas('users', ['id' => $staff->id, 'hearts' => 5, 'gems' => 200]);
        }
    }

    public function test_refill_preserves_fire_and_resumes_the_existing_lesson_position(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 27]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 2,
            'gems' => 50,
            'heart_regeneration_started_at' => now(),
        ]);
        $questionIds = [];
        for ($questionIndex = 0; $questionIndex < 5; $questionIndex++) {
            $questionIds[] = $this->createQuestion($lesson, 'yes');
        }
        $learningItemId = DB::table('lesson_vocabulary_items')->insertGetId([
            'lesson_id' => $lesson->id,
            'japanese' => 'こんにちは', 'reading' => 'こんにちは', 'english' => 'hello',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('lesson_vocabulary_questions')->insert([
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $questionIds[0],
                'stage' => 'usage', 'stage_order' => 0,
            ],
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $questionIds[1],
                'stage' => 'review', 'stage_order' => 1,
            ],
        ]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))
            ->assertViewHas('isTeaching', true);
        $this->post(route('user.lesson.learning.complete', [$lesson, $learningItemId]))->assertRedirect();
        $this->get(route('user.training', $lesson->id))->assertViewHas('isTeaching', false);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionIds[0],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true, 'lesson_completed' => false]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionIds[1],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true, 'lesson_completed' => false]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionIds[2],
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 1, 'session_ended' => false]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionIds[2],
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true, 'lesson_completed' => false]);
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questionIds[3]])
            ->assertOk()->assertJson(['accepted' => true, 'hearts' => 0, 'session_ended' => true]);

        $this->get(route('user.training', $lesson->id))
            ->assertViewHas('lessonState', fn (array $state): bool => $state['next_index'] === 4
                && $state['answered_ids'] === array_slice($questionIds, 0, 3)
                && $state['skipped_ids'] === [$questionIds[3]]
                && $state['learning_item_index'] === 1
                && $state['taught_learning_item_ids'] === [$learningItemId]
                && $state['session_ended']);
        $this->assertDatabaseHas('user_question_mistakes', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'vocabulary_id' => $questionIds[2],
        ]);

        $restartReferrer = route('user.training', ['id' => $lesson->id, 'restart' => 1]);
        $this->from($restartReferrer)
            ->post(route('student.hearts.refill'), ['lesson_id' => $lesson->id])
            ->assertRedirect(route('user.training', $lesson->id));

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 0, 'streak_count' => 27]);
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 5
                && $state['next_index'] === 4
                && $state['answered_ids'] === array_slice($questionIds, 0, 3)
                && $state['skipped_ids'] === [$questionIds[3]]
                && $state['learning_item_index'] === 1
                && $state['taught_learning_item_ids'] === [$learningItemId]
                && !$state['session_ended'])
            ->assertViewHas('lesson', fn (Lesson $viewLesson): bool => $viewLesson->vocabularies->pluck('id')->all() === $questionIds);
        $this->get(route('user.training', $lesson->id))
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 5
                && $state['next_index'] === 4
                && $state['answered_ids'] === array_slice($questionIds, 0, 3)
                && $state['skipped_ids'] === [$questionIds[3]]);
        $this->assertDatabaseHas('user_question_mistakes', ['vocabulary_id' => $questionIds[2]]);
    }

    public function test_refill_from_restart_url_preserves_existing_session_state(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 18]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 0,
            'gems' => 50,
            'heart_regeneration_started_at' => now(),
        ]);

        $questionIds = [];
        for ($questionIndex = 1; $questionIndex <= 5; $questionIndex++) {
            $questionIds[] = $this->createQuestion($lesson, 'yes');
        }

        $sessionKey = 'lesson_question_progress.' . $student->id . '.' . $lesson->id;
        $sessionState = [
            'next_index' => 4,
            'answered_ids' => array_slice($questionIds, 0, 3),
            'skipped_ids' => [$questionIds[3]],
            'lesson_completed' => false,
            'session_ended' => true,
            'end_reason' => 'hearts',
            'hearts' => 0,
        ];

        $this->actingAs($student)->withSession([$sessionKey => $sessionState]);
        $restartUrl = route('user.training', ['id' => $lesson->id, 'restart' => 1]);
        $this->get($restartUrl)
            ->assertOk()
            ->assertSee('name="lesson_id" value="' . $lesson->id . '"', false);
        $this->from($restartUrl)
            ->post(route('student.hearts.refill'), ['lesson_id' => $lesson->id])
            ->assertRedirect(route('user.training', $lesson->id));

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 0, 'streak_count' => 18]);
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 5
                && $state['next_index'] === 4
                && $state['answered_ids'] === array_slice($questionIds, 0, 3)
                && $state['skipped_ids'] === [$questionIds[3]]
                && !$state['session_ended'])
            ->assertViewHas('lesson', fn (Lesson $viewLesson): bool => $viewLesson->vocabularies->pluck('id')->all() === $questionIds);
    }

    public function test_training_resumes_saved_position_after_session_state_is_lost_while_hearts_refill(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 21]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 1]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');
        $learningItemId = DB::table('lesson_vocabulary_items')->insertGetId([
            'lesson_id' => $lesson->id,
            'japanese' => 'ありがとう', 'reading' => 'ありがとう', 'english' => 'thank you',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('lesson_vocabulary_questions')->insert([
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $firstQuestionId,
                'stage' => 'usage', 'stage_order' => 0,
            ],
            [
                'lesson_vocabulary_item_id' => $learningItemId,
                'vocabulary_question_id' => $secondQuestionId,
                'stage' => 'review', 'stage_order' => 1,
            ],
        ]);

        $this->actingAs($student)->get(route('user.training', $lesson->id))
            ->assertViewHas('isTeaching', true);
        $this->post(route('user.lesson.learning.complete', [$lesson, $learningItemId]))->assertRedirect();
        $this->get(route('user.training', $lesson->id))->assertViewHas('isTeaching', false);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $secondQuestionId,
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 0, 'session_ended' => true]);

        // The learner leaves the lesson at 0 hearts. The session copy of the state is lost
        // (e.g. expiry) while the hearts regenerate; returning must resume, not restart.
        session()->forget('lesson_question_progress.' . $student->id . '.' . $lesson->id);
        DB::table('users')->where('id', $student->id)->update([
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('isTeaching', false)
            ->assertViewHas('lessonState', fn (array $state): bool => $state['hearts'] === 1
                // The wrong answer advanced past the second question and queued it for retry.
                && $state['next_index'] === 2
                && $state['retry_ids'] === [$secondQuestionId]
                && $state['answered_ids'] === [$firstQuestionId]
                && $state['learning_item_index'] === 1
                && $state['taught_learning_item_ids'] === [$learningItemId]
                && !$state['session_ended']);
    }

    public function test_insufficient_gems_do_not_change_gems_hearts_or_fire(): void
    {
        foreach ([49, 0] as $startingGems) {
            $student = User::factory()->create(['role' => 'student', 'streak_count' => 31]);
            DB::table('users')->where('id', $student->id)->update([
                'hearts' => 2,
                'gems' => $startingGems,
                'heart_regeneration_started_at' => now(),
            ]);

            $this->actingAs($student)->post(route('student.hearts.refill'))
                ->assertRedirect()
                ->assertSessionHas('gem_refill_error');

            $this->assertDatabaseHas('users', [
                'id' => $student->id,
                'hearts' => 2,
                'gems' => $startingGems,
                'streak_count' => 31,
            ]);
        }
    }

    public function test_full_hearts_cannot_spend_gems(): void
    {
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 9]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 5, 'gems' => 50]);

        $this->actingAs($student)->post(route('student.hearts.refill'))
            ->assertRedirect()
            ->assertSessionHas('gem_refill_error');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 50, 'streak_count' => 9]);
    }

    public function test_double_gem_refill_only_charges_once(): void
    {
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 12]);
        DB::table('users')->where('id', $student->id)->update([
            'hearts' => 0,
            'gems' => 50,
            'heart_regeneration_started_at' => now(),
        ]);

        $this->actingAs($student)->post(route('student.hearts.refill'))->assertRedirect();
        $this->post(route('student.hearts.refill'))
            ->assertRedirect()
            ->assertSessionHas('gem_refill_error');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 5, 'gems' => 0, 'streak_count' => 12]);
    }

    public function test_wrong_answer_consumes_one_persistent_heart(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 2, 'gems' => 81]);
        $questionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 1]);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 1, 'gems' => 81, 'streak_count' => 37]);
    }

    public function test_skip_consumes_one_persistent_heart(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 37]);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 2, 'gems' => 81]);
        $questionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->post(route('user.lesson.skip', $lesson), ['vocabulary_id' => $questionId])
            ->assertOk()
            ->assertJson(['accepted' => true, 'hearts' => 1]);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 1, 'gems' => 81, 'streak_count' => 37]);
    }

    public function test_lesson_completion_awards_ten_fire_points_exactly_once(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 0]);
        DB::table('users')->where('id', $student->id)->update(['gems' => 100]);
        $questionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'gems' => 100, 'streak_count' => 0]);
        $this->get(route('user.training', $lesson->id));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'gems' => 100, 'streak_count' => 0]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
            'gems' => 0,
            'gem_reward' => 50000,
            'user_id' => 99999,
        ])->assertOk()
            ->assertJsonPath('progression.fire_awarded', 10)
            ->assertJsonPath('progression.gems_awarded', 50)
            ->assertJsonPath('progression.gems_balance', 150);

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
        ])->assertOk()->assertJson(['already_accepted' => true]);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10, 'gems' => 150]);
        $this->get(route('levels.courses', $levelId))->assertOk();
        $this->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
        ])->assertOk()
            ->assertJsonPath('progression.fire_awarded', 0)
            ->assertJsonPath('progression.gems_awarded', 0)
            ->assertJsonPath('progression.gems_balance', 150);
        $this->get(route('home'))->assertOk();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10, 'gems' => 150]);
        $this->actingAs($student->fresh())->get(route('home'))
            ->assertDontSee('navbar-fire-count');
    }

    public function test_end_session_leave_returns_to_saved_position_after_session_loss(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'yes',
        ])->assertOk()->assertJson(['correct' => true]);

        $this->get(route('levels.courses', $lesson->level_id))->assertOk();
        session()->forget('lesson_question_progress.' . $student->id . '.' . $lesson->id);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('isTeaching', false)
            ->assertViewHas('lessonState', fn (array $state): bool => $state['next_index'] === 1
                && $state['answered_ids'] === [$firstQuestionId]
                && !$state['lesson_completed']);
    }

    public function test_zero_hearts_leave_returns_to_saved_position_after_refill(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);
        DB::table('users')->where('id', $student->id)->update(['hearts' => 1]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'no',
        ])->assertStatus(422)->assertJson(['hearts' => 0, 'session_ended' => true]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 0]);

        $stored = DB::table('user_progress')
            ->where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->value('lesson_state');
        $this->assertNotNull($stored, 'durable lesson_state must persist the 0-heart pause');
        $this->assertTrue((bool) json_decode($stored, true)['session_ended']);

        $this->get(route('levels.courses', $lesson->level_id))->assertOk();
        session()->forget('lesson_question_progress.' . $student->id . '.' . $lesson->id);
        DB::table('users')->where('id', $student->id)->update([
            'heart_regeneration_started_at' => now()->subMinutes(5),
        ]);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('isTeaching', false)
            ->assertViewHas('lessonState', fn (array $state): bool => $state['next_index'] === 1
                // The wrong answer advanced past the first question and queued it for retry.
                && $state['retry_ids'] === [$firstQuestionId]
                && $state['answered_ids'] === []
                && $state['hearts'] === 1
                && !$state['session_ended']);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'hearts' => 1]);
    }

    public function test_explicit_restart_resets_position_and_clears_durable_state(): void
    {
        [, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student']);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'yes',
        ])->assertOk();
        $this->assertNotNull(DB::table('user_progress')
            ->where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->value('lesson_state'));

        $this->get(route('user.training', ['id' => $lesson->id, 'restart' => 1]))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => $state['next_index'] === 0
                && $state['answered_ids'] === []);

        $this->assertNull(DB::table('user_progress')
            ->where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->value('lesson_state'));
    }

    public function test_completed_lesson_db_record_persists_and_view_state_does_not_resurrect_position(): void
    {
        [$levelId, $lesson] = $this->createLesson();
        $student = User::factory()->create(['role' => 'student', 'streak_count' => 0]);
        DB::table('users')->where('id', $student->id)->update(['gems' => 100]);
        $firstQuestionId = $this->createQuestion($lesson, 'yes');
        $secondQuestionId = $this->createQuestion($lesson, 'yes');

        $this->actingAs($student)->get(route('user.training', $lesson->id))->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $firstQuestionId,
            'answer' => 'yes',
        ])->assertOk();
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $secondQuestionId,
            'answer' => 'yes',
        ])->assertOk()->assertJson(['lesson_completed' => true]);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10, 'gems' => 150]);

        session()->forget('lesson_question_progress.' . $student->id . '.' . $lesson->id);

        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lessonState', fn (array $state): bool => ($state['lesson_completed'] ?? false) === false
                && $state['next_index'] === 0
                && $state['answered_ids'] === []);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10, 'gems' => 150]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
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
            'level_id' => $levelId, 'category_id' => $categoryId, 'title' => 'A1 Lesson',
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
