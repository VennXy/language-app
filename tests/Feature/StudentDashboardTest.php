<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_progress_current_level_and_continue_learning(): void
    {
        [, $lessons] = $this->createLevelWithLessons('A1', ['First Lesson', 'Second Lesson']);
        $student = $this->student('Learner One');
        $this->completeLessonFor($student, $lessons[0]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Your learning dashboard')
            ->assertSee('A1')
            ->assertSee('<span id="overall-progress-percent">50</span>', false)
            ->assertSee('<span id="lesson-count-summary">1 of 2 lessons completed</span>', false)
            ->assertSee('Continue Learning')
            ->assertSee('Next up: Second Lesson')
            ->assertSee(route('user.training', ['id' => $lessons[1]->id, 'type' => 'training']), false);
    }

    public function test_gamification_tiles_match_the_stored_values(): void
    {
        $this->createLevelWithLessons('A1', ['First Lesson']);
        $student = $this->student('Learner Two');
        DB::table('users')->where('id', $student->id)->update([
            'streak_count' => 120,
            'daily_streak' => 3,
            'gems' => 200,
            'hearts' => 5,
            'last_learning_activity_date' => now()->toDateString(),
        ]);

        $this->actingAs($student->fresh())
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<span id="stat-daily-streak">3</span>', false)
            ->assertSee('<span id="stat-gems">200</span>', false)
            ->assertSee('<span id="stat-hearts">5/5</span>', false)
            ->assertSee('✓ Completed today');
    }

    public function test_overall_progress_percentage_reflects_completed_lessons(): void
    {
        [, $lessons] = $this->createLevelWithLessons('A1', ['One', 'Two', 'Three']);
        $student = $this->student('Learner Three');
        $this->completeLessonFor($student, $lessons[0]);
        $this->completeLessonFor($student, $lessons[1]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<span id="overall-progress-percent">67</span>', false)
            ->assertSee('<span id="lesson-count-summary">2 of 3 lessons completed</span>', false);
    }

    public function test_new_student_starts_at_a1_with_zero_progress(): void
    {
        $this->createLevelWithLessons('A1', ['First Lesson', 'Second Lesson']);
        $student = $this->student('Fresh Learner');

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<span id="overall-progress-percent">0</span>', false)
            ->assertSee('<span id="lesson-count-summary">0 of 1 lessons completed</span>', false)
            ->assertSee('Continue Learning')
            ->assertSee('Next up: First Lesson');

        $this->assertDatabaseHas('user_levels', ['user_id' => $student->id, 'status' => 'active']);
    }

    public function test_recent_activity_lists_only_the_current_students_lesson_activity(): void
    {
        [, $a1Lessons] = $this->createLevelWithLessons('A1', ['Alpha Lesson']);
        [, $a2Lessons] = $this->createLevelWithLessons('A2', ['Hidden Beta Lesson']);
        $student = $this->student('Activity Owner');
        $other = $this->student('Other Learner');
        $this->completeLessonFor($student, $a1Lessons[0]);
        $this->completeLessonFor($other, $a2Lessons[0]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Alpha Lesson')
            ->assertDontSee('Hidden Beta Lesson');
    }

    public function test_admin_cannot_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_cannot_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_request_does_not_mutate_gamification_or_progress(): void
    {
        [, $lessons] = $this->createLevelWithLessons('A1', ['First Lesson', 'Second Lesson']);
        $student = $this->student('Safe Learner');
        DB::table('users')->where('id', $student->id)->update([
            'streak_count' => 40,
            'daily_streak' => 4,
            'last_learning_activity_date' => '2026-09-29',
            'hearts' => 5,
            'gems' => 150,
            'heart_regeneration_started_at' => null,
        ]);
        DB::table('user_progress')->insert([
            'user_id' => $student->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'in_progress',
            'progress_percent' => 40,
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'streak_count' => 40,
            'daily_streak' => 4,
            'last_learning_activity_date' => '2026-09-29',
            'hearts' => 5,
            'gems' => 150,
            'heart_regeneration_started_at' => null,
        ]);
        $this->assertDatabaseHas('user_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lessons[0]->id,
            'status' => 'in_progress',
            'progress_percent' => 40,
        ]);

        // accessibleLevelIds() bootstraps the A1 user_levels row once; the call is idempotent.
        $this->assertSame(1, DB::table('user_levels')->where('user_id', $student->id)->count());
        $this->get(route('student.dashboard'))->assertOk();
        $this->assertSame(1, DB::table('user_levels')->where('user_id', $student->id)->count());
    }

    public function test_lesson_completion_awards_points_and_the_dashboard_reflects_them(): void
    {
        [, $lessons] = $this->createLevelWithLessons('A1', ['Only Lesson']);
        $questionId = $this->createQuestion($lessons[0], 'yes');
        $student = $this->student('Completing Learner');
        DB::table('users')->where('id', $student->id)->update(['gems' => 0]);

        $this->actingAs($student->fresh())->get(route('user.training', $lessons[0]->id))->assertOk();

        $this->post(route('user.lesson.answer', $lessons[0]), [
            'vocabulary_id' => $questionId,
            'answer' => 'yes',
        ])->assertOk()
            ->assertJsonPath('progression.fire_awarded', 10)
            ->assertJsonPath('progression.gems_awarded', 50);

        $this->assertDatabaseHas('users', ['id' => $student->id, 'streak_count' => 10, 'gems' => 50]);

        // A real HTTP request reloads the authenticated user from the database before the route runs.
        $this->actingAs($student->fresh());

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('<span id="stat-gems">50</span>', false)
            ->assertSee('<span id="overall-progress-percent">100</span>', false)
            ->assertSee('Browse lessons');
    }

    public function test_student_pages_use_the_shell_without_the_large_top_navbar(): void
    {
        $this->createLevelWithLessons('A1', ['First Lesson']);
        $student = $this->student('Shell Watcher');

        $response = $this->actingAs($student)->get(route('student.dashboard'));
        $response
            ->assertOk()
            ->assertDontSee('navbar-brand', false)
            ->assertSee('<aside class="sk-side"', false)
            ->assertSee('My Dashboard')
            ->assertDontSee('Manage Access');
        $renderedHtml = $response->getContent();
        $this->assertSame(1, substr_count($renderedHtml, 'class="sk-utility"'), 'Exactly one slim utility strip must render.');
        $this->assertSame(1, substr_count($renderedHtml, 'id="student-currencies"'), 'Exactly one Hearts/Gems/Streak widget must render.');

        // The public home page keeps the top navbar for students, with the simplified account menu.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('student.dashboard'), false)
            ->assertSee('My Dashboard')
            ->assertDontSee('Manage Access');
    }

    public function test_student_dropdown_links_to_the_dashboard_and_other_roles_do_not(): void
    {
        $this->actingAs($this->student('Nav Learner'))
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('student.dashboard'), false)
            ->assertSee('Dashboard');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('student.dashboard'), false);
    }

    public function test_dashboard_route_accepts_get_only(): void
    {
        $route = Route::getRoutes()->getByName('student.dashboard');

        $this->assertNotNull($route, 'The student dashboard route must exist.');
        $this->assertContains('GET', $route->methods());

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertNotContains($method, $route->methods());
        }
    }

    private function student(string $name, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'name' => $name,
            'role' => 'student',
        ], $attributes));
    }

    private function createLevelWithLessons(string $code, array $lessonTitles): array
    {
        $now = now();
        $levelId = DB::table('levels')->insertGetId([
            'name' => $code, 'level' => $code, 'tier' => $code, 'description' => $code . ' level',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $levelId, 'title' => $code . ' Category', 'sort_order' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $lessons = [];
        foreach (array_values($lessonTitles) as $index => $title) {
            $lessons[] = Lesson::create([
                'level_id' => $levelId, 'category_id' => $categoryId, 'title' => $title,
                'type' => 'training', 'score' => 0, 'sort_order' => $index,
            ]);
        }

        return [$levelId, $lessons];
    }

    private function completeLessonFor(User $user, Lesson $lesson): void
    {
        DB::table('user_progress')->insert([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed',
            'progress_percent' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
