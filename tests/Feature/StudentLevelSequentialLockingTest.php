<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use App\Services\LevelProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers sequential EXAM level locking (spec §2/§3/§4/§7): a fresh student has
 * A1 as Current Level and A2-C2 Locked; a level is Completed only when Training
 * AND all its published exams are passed, and only then does the next level
 * become Current; Locked levels reject direct/crafted requests server-side; the
 * UI badges match server-side eligibility; two students stay independent.
 *
 * Isolated test data only (RefreshDatabase + sqlite :memory: via phpunit.xml).
 */
class StudentLevelSequentialLockingTest extends TestCase
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

    private function user(string $role = 'student'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function level(string $code): Level
    {
        return Level::query()->where('level', $code)->firstOrFail();
    }

    private function lesson(Level $level, string $title): Lesson
    {
        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $level->id,
            'title' => 'General',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Lesson::create([
            'level_id' => $level->id,
            'category_id' => $categoryId,
            'title' => $title,
            'type' => 'training',
            'score' => 0,
            'sort_order' => 0,
        ]);
    }

    private function exam(string $status, string $title, Level $level): Exam
    {
        return Exam::create([
            'title' => $title,
            'level_id' => $level->id,
            'duration_minutes' => 30,
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

    /** Give every listed level one lesson and one published exam (3 questions). */
    private function authorContent(array $codes): void
    {
        foreach ($codes as $code) {
            $level = $this->level($code);
            $this->lesson($level, "{$code} Lesson 1");
            $exam = $this->exam('published', "{$code} Exam 01", $level);
            $this->createQuestions($exam, 3);
        }
    }

    /** Mark every lesson of the level complete in user_progress (Training done). */
    private function completeTraining(User $student, Level $level): void
    {
        foreach (Lesson::where('level_id', $level->id)->pluck('id') as $lessonId) {
            DB::table('user_progress')->updateOrInsert(
                ['user_id' => $student->id, 'lesson_id' => $lessonId],
                ['status' => 'completed', 'progress_percent' => 100, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /** Insert a passed attempt for every published exam of the level. */
    private function passExams(User $student, Level $level): void
    {
        foreach (Exam::where('level_id', $level->id)->where('status', 'published')->get() as $exam) {
            ExamAttempt::create([
                'exam_id' => $exam->id,
                'user_id' => $student->id,
                'started_at' => now()->subMinutes(10),
                'expires_at' => now()->subMinutes(5),
                'status' => 'submitted',
                'correct_count' => 3,
                'score_percent' => 100,
                'passed' => true,
            ]);
        }
    }

    /**
     * Run the authoritative progression rule exactly as the controllers do:
     * bootstrap A1 (initializeFirstLevel) then evaluate the level's completion.
     */
    private function evaluate(User $student, Level $level): void
    {
        $service = app(LevelProgressionService::class);
        $service->accessibleLevelIds($student); // bootstraps the A1 user_levels row
        $service->syncLevelCompletion($student, $level);
    }

    /** Fully complete a level (Training + all published exams) and sync state. */
    private function completeLevel(User $student, string $code): Level
    {
        $level = $this->level($code);
        $this->completeTraining($student, $level);
        $this->passExams($student, $level);
        $this->evaluate($student, $level);

        return $level;
    }

    // §2.1/§2.4/§3: fresh student -> A1 Current, A2-C2 Locked (UI + server).
    public function test_initial_student_has_a1_as_current_and_remaining_levels_locked(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);

        $response = $this->actingAs($student)->get(route('select.level'))->assertOk();

        // A1 is the current level and is selectable.
        $response->assertSee('လက်ရှိ Level');
        $response->assertSee(route('levels.courses', $this->level('A1')->id), false);

        // A2..C2 are locked and not linked.
        foreach (['A2', 'B1', 'B2', 'C1', 'C2'] as $code) {
            $response->assertDontSee(route('levels.courses', $this->level($code)->id), false);
        }
        $response->assertSee('Locked');

        // Server agrees: current level reachable, next level forbidden.
        $this->actingAs($student)->get(route('levels.courses', $this->level('A1')->id))->assertOk();
        $this->actingAs($student)->get(route('levels.courses', $this->level('A2')->id))->assertForbidden();
    }

    // §2.2/§2.3: completing A1 (Training + exams) marks it Completed, unlocks A2.
    public function test_completing_a1_marks_it_completed_and_unlocks_a2(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2', 'B1']);

        $this->completeLevel($student, 'A1');

        $service = app(LevelProgressionService::class);
        $a1 = $this->level('A1')->id;
        $a2 = $this->level('A2')->id;

        $this->assertContains($a1, $service->completedLevelIds($student));
        $this->assertContains($a2, $service->accessibleLevelIds($student));

        // UI: A1 shows the Completed badge, A2 is selectable (Current).
        $response = $this->actingAs($student)->get(route('select.level'))->assertOk();
        $response->assertSee('ပြီးမြောက်');
        $response->assertSee('လက်ရှိ Level');
        $this->actingAs($student)->get(route('levels.courses', $a2))->assertOk();
    }

    // §2.2/§2.3: completing A2 -> A1 & A2 Completed, B1 becomes Current.
    public function test_completing_a2_marks_a1_a2_completed_and_unlocks_b1(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2', 'B1', 'B2']);

        $this->completeLevel($student, 'A1');
        $this->completeLevel($student, 'A2');

        $service = app(LevelProgressionService::class);
        $completed = $service->completedLevelIds($student);

        $this->assertContains($this->level('A1')->id, $completed);
        $this->assertContains($this->level('A2')->id, $completed);
        $this->assertContains($this->level('B1')->id, $service->accessibleLevelIds($student));
        $this->assertNotContains($this->level('B2')->id, $service->accessibleLevelIds($student));

        $this->actingAs($student)->get(route('levels.courses', $this->level('B1')->id))->assertOk();
        $this->actingAs($student)->get(route('levels.courses', $this->level('B2')->id))->assertForbidden();
    }

    // §2: progression continues through B2, C1 and C2 in curriculum order.
    public function test_progression_continues_through_all_levels_in_order(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);

        $service = app(LevelProgressionService::class);
        $sequence = ['A1' => 'A2', 'A2' => 'B1', 'B1' => 'B2', 'B2' => 'C1', 'C1' => 'C2', 'C2' => null];

        foreach ($sequence as $current => $next) {
            $this->completeLevel($student, $current);

            $this->assertContains($this->level($current)->id, $service->completedLevelIds($student));

            if ($next !== null) {
                $this->assertContains($this->level($next)->id, $service->accessibleLevelIds($student));
                $this->actingAs($student)->get(route('levels.courses', $this->level($next)->id))->assertOk();
            }
        }

        // All six levels are now Completed.
        $this->assertCount(6, $service->completedLevelIds($student));
    }

    // §2.2/§2.6: Training alone (with exams present) does NOT complete a level.
    public function test_training_alone_does_not_complete_a_level_that_has_exams(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2']);

        $a1 = $this->level('A1');
        $this->completeTraining($student, $a1);
        $this->evaluate($student, $a1); // Training done, exams untouched

        $service = app(LevelProgressionService::class);
        $this->assertNotContains($this->level('A2')->id, $service->accessibleLevelIds($student));
        $this->actingAs($student)->get(route('levels.courses', $this->level('A2')->id))->assertForbidden();
    }

    // §2.2/§2.6: passing exams alone (with incomplete Training) does NOT complete a level.
    public function test_passing_exams_alone_does_not_complete_a_level_with_incomplete_training(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2']);

        $a1 = $this->level('A1');
        $this->passExams($student, $a1); // exams passed, Training untouched
        $this->evaluate($student, $a1);

        $service = app(LevelProgressionService::class);
        $this->assertNotContains($this->level('A2')->id, $service->accessibleLevelIds($student));
        $this->actingAs($student)->get(route('levels.courses', $this->level('A2')->id))->assertForbidden();
    }

    // §2.5/§4: locked levels reject direct URLs and crafted requests server-side.
    public function test_locked_levels_reject_direct_urls_and_crafted_requests(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2']);

        $a2 = $this->level('A2');
        $a2Exam = Exam::query()->where('level_id', $a2->id)->firstOrFail();

        // Direct URL to the locked level's courses -> 403.
        $this->actingAs($student)->get(route('levels.courses', $a2->id))->assertForbidden();

        // Crafted POST attempting to start an exam in the locked level -> 404
        // (the exam does not belong to the student's current level).
        $this->actingAs($student)->post(route('student.exams.start', $a2Exam))->assertNotFound();

        // The locked level's exam is never offered on the exam listing.
        $this->actingAs($student)
            ->get(route('student.exams.index'))
            ->assertOk()
            ->assertDontSee('A2 Exam 01');
    }

    // §7: a real exam pass (through the HTTP flow) completes the level & unlocks next.
    public function test_passing_the_final_exam_via_the_real_flow_unlocks_the_next_level(): void
    {
        $student = $this->user();
        $this->authorContent(['A1', 'A2']);

        $a1 = $this->level('A1');
        $a2 = $this->level('A2');
        $exam = Exam::query()->where('level_id', $a1->id)->firstOrFail();

        // Complete A1 Training, then take A1's only exam through the real flow.
        $this->completeTraining($student, $a1);
        app(LevelProgressionService::class)->accessibleLevelIds($student); // bootstrap A1

        $this->actingAs($student)->post(route('student.exams.start', $exam))->assertRedirect();
        $attempt = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('user_id', $student->id)
            ->firstOrFail();

        foreach ($attempt->questions()->orderBy('question_order')->get() as $snapshot) {
            $this->actingAs($student)->post(route('student.exams.attempts.answer', $attempt), [
                'question_id' => $snapshot->id,
                'selected_option' => $snapshot->correct_option,
            ]);
        }

        $this->actingAs($student)->post(route('student.exams.attempts.submit', $attempt))->assertRedirect();

        // The exam pass completed A1 and unlocked A2 via the finalize hook.
        $this->assertTrue($attempt->fresh()->passed);

        $service = app(LevelProgressionService::class);
        $this->assertContains($a1->id, $service->completedLevelIds($student));
        $this->assertContains($a2->id, $service->accessibleLevelIds($student));
        $this->actingAs($student)->get(route('levels.courses', $a2->id))->assertOk();
    }

    // §2.8: two students with different progress have independent statuses.
    public function test_two_students_have_independent_level_statuses(): void
    {
        $this->authorContent(['A1', 'A2', 'B1']);

        $alice = $this->user();
        $bob = $this->user();

        $this->completeLevel($alice, 'A1');
        // Bob does nothing.

        $service = app(LevelProgressionService::class);

        // Alice: A1 Completed, A2 unlocked.
        $this->assertContains($this->level('A1')->id, $service->completedLevelIds($alice));
        $this->assertContains($this->level('A2')->id, $service->accessibleLevelIds($alice));

        // Bob: nothing completed, A2 still locked.
        $this->assertSame([], $service->completedLevelIds($bob));
        $this->assertNotContains($this->level('A2')->id, $service->accessibleLevelIds($bob));

        // UI stays independent per student.
        $this->actingAs($alice)->get(route('select.level'))->assertOk()->assertSee('ပြီးမြောက်');
        $this->actingAs($bob)->get(route('select.level'))->assertOk()->assertDontSee('ပြီးမြောက်');
        $this->actingAs($bob)->get(route('levels.courses', $this->level('A2')->id))->assertForbidden();
    }
}
