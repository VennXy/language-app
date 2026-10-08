<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudentLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_are_ranked_by_fire_points_descending(): void
    {
        $this->student('Alpha Learner', 10);
        $this->student('Bravo Learner', 30);
        $this->student('Charlie Learner', 20);
        $me = $this->student('Viewer Learner', 5);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSeeInOrder(['Bravo Learner', 'Charlie Learner', 'Alpha Learner', 'Viewer Learner'])
            ->assertSee('🔥 30')
            ->assertSee('🔥 20')
            ->assertSee('🔥 10')
            ->assertSee('🔥 5');
    }

    public function test_admin_and_super_admin_are_excluded_even_with_higher_fire_points(): void
    {
        User::factory()->create(['name' => 'Hidden Admin', 'role' => 'admin', 'streak_count' => 999]);
        User::factory()->create(['name' => 'Hidden Root', 'role' => 'super_admin', 'streak_count' => 999]);
        $this->student('Alpha Learner', 10);
        $me = $this->student('Viewer Learner', 10);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertDontSee('Hidden Admin')
            ->assertDontSee('Hidden Root')
            ->assertDontSee('🔥 999')
            ->assertSee('#1');
    }

    public function test_current_student_rank_is_calculated_correctly(): void
    {
        $this->student('Alpha Learner', 30);
        $this->student('Bravo Learner', 20);
        $me = $this->student('Viewer Learner', 10);
        $this->student('Delta Learner', 5);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSee('#3')
            ->assertSee('🔥 10 Fire Points');
    }

    public function test_tied_students_share_the_same_rank_and_the_next_rank_skips(): void
    {
        $this->student('Top Learner', 40);
        $this->student('Tie Learner A', 20);
        $this->student('Tie Learner B', 20);
        $me = $this->student('Viewer Learner', 10);

        $response = $this->actingAs($me)->get(route('student.leaderboard.index'));

        $response->assertOk()->assertSee('#4');

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, '🥇'), 'Only the top student should hold rank one.');
        $this->assertSame(2, substr_count($html, '🥈'), 'Both tied students should share rank two.');
        $this->assertSame(0, substr_count($html, '🥉'), 'Rank three must be skipped by the tie.');
    }

    public function test_zero_fire_point_students_appear_on_the_leaderboard(): void
    {
        $this->student('Alpha Learner', 10);
        $me = $this->student('Viewer Learner', 0);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSee('Viewer Learner')
            ->assertSee('🔥 0')
            ->assertSee('#2');
    }

    public function test_student_outside_the_top_twenty_still_sees_own_rank_and_points(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->student('Rank Learner ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 100 - $i);
        }
        $me = $this->student('Viewer Learner', 1);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSee('Rank Learner 01')
            ->assertSee('Rank Learner 20')
            ->assertDontSee('Rank Learner 21')
            ->assertSee('#22')
            ->assertSee('🔥 1 Fire Points');
    }

    public function test_leaderboard_request_does_not_mutate_fire_points_or_daily_streak(): void
    {
        $this->student('Alpha Learner', 50);
        $me = $this->student('Viewer Learner', 30);
        DB::table('users')->where('id', $me->id)->update([
            'daily_streak' => 4,
            'last_learning_activity_date' => '2026-09-29',
        ]);

        $this->actingAs($me->fresh())
            ->get(route('student.leaderboard.index'))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $me->id,
            'streak_count' => 30,
            'daily_streak' => 4,
            'last_learning_activity_date' => '2026-09-29',
        ]);
    }

    public function test_leaderboard_request_does_not_mutate_hearts_or_gems(): void
    {
        $this->student('Alpha Learner', 50);
        $me = $this->student('Viewer Learner', 30);
        DB::table('users')->where('id', $me->id)->update([
            'hearts' => 5,
            'gems' => 150,
            'heart_regeneration_started_at' => null,
        ]);

        $this->actingAs($me->fresh())
            ->get(route('student.leaderboard.index'))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $me->id,
            'hearts' => 5,
            'gems' => 150,
            'heart_regeneration_started_at' => null,
        ]);
    }

    public function test_admin_cannot_open_the_leaderboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('student.leaderboard.index'))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_open_the_leaderboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('student.leaderboard.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('student.leaderboard.index'))->assertRedirect(route('login'));
    }

    public function test_leaderboard_does_not_expose_private_student_fields(): void
    {
        $other = $this->student('Visible Student', 40, ['email' => 'secret-student@example.test']);
        DB::table('users')->where('id', $other->id)->update([
            'date_of_birth' => '1990-03-15',
            'profile_photo' => 'profiles/secret-photo.jpg',
            'last_active_at' => now(),
        ]);
        $me = $this->student('Viewer Learner', 10);

        $this->actingAs($me)
            ->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSee('Visible Student')
            ->assertSee('🔥 40')
            ->assertDontSee('secret-student@example.test')
            ->assertDontSee($me->email)
            ->assertDontSee('1990-03-15')
            ->assertDontSee('profiles/secret-photo.jpg')
            ->assertDontSee('$2y$');
    }

    public function test_leaderboard_has_no_mutating_route(): void
    {
        $route = Route::getRoutes()->getByName('student.leaderboard.index');

        $this->assertNotNull($route, 'The leaderboard route must exist.');
        $this->assertContains('GET', $route->methods());

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertNotContains($method, $route->methods());
        }

        $mutating = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'leaderboard'))
            ->filter(fn ($route) => (bool) array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']));

        $this->assertTrue($mutating->isEmpty(), 'The leaderboard must not expose write routes.');
    }

    public function test_lesson_completion_fire_points_appear_on_the_leaderboard(): void
    {
        [, $lesson] = $this->createLesson();
        $questionId = $this->createQuestion($lesson, 'yes');

        $this->student('Higher Learner', 100);
        $me = $this->student('Completing Learner', 0);
        DB::table('users')->where('id', $me->id)->update(['gems' => 0]);

        $this->actingAs($me->fresh())->get(route('user.training', $lesson->id))->assertOk();

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
        ])->assertOk()
            ->assertJsonPath('progression.fire_awarded', 10)
            ->assertJsonPath('progression.gems_awarded', 50);

        $this->assertDatabaseHas('users', [
            'id' => $me->id,
            'streak_count' => 10,
            'gems' => 50,
        ]);

        // A real HTTP request re-loads the authenticated user from the database before the route runs.
        $this->actingAs($me->fresh());

        $this->get(route('student.leaderboard.index'))
            ->assertOk()
            ->assertSee('Completing Learner')
            ->assertSee('🔥 10')
            ->assertSee('#2');
    }

    private function student(string $name, int $firePoints, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => $name,
            'role' => 'student',
            'streak_count' => $firePoints,
        ], $attributes));
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
