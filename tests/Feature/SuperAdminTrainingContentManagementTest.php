<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminTrainingContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_training_management_is_super_admin_only_and_guest_uses_authentication(): void
    {
        $this->get(route('super.admin.training.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('super.admin.training.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('super.admin.training.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('super.admin.training.index'))->assertOk();
    }

    public function test_fresh_vocabulary_schema_supports_question_only_records(): void
    {
        $this->assertTrue(Schema::hasColumns('vocabularies', [
            'word', 'reading', 'meaning', 'question', 'image_url', 'question_type', 'options', 'correct_answer',
        ]));

        [, , $lesson] = $this->createLesson('A1');
        $questionId = DB::table('vocabularies')->insertGetId([
            'lesson_id' => $lesson->id,
            'question_type' => 'word_selection',
            'question' => 'Question-only record',
            'options' => 'yes,no',
            'correct_answer' => 'yes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vocabularies', ['id' => $questionId, 'question' => 'Question-only record']);
    }

    public function test_same_management_page_lists_all_six_levels(): void
    {
        $levels = [];
        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $code) {
            $levels[] = $this->createLevel($code);
        }

        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $levels[0], 'title' => 'A1 Category', 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->post(route('super.admin.training.lessons.store', $levels[0]), [
                'title' => 'Created A1 Lesson', 'category_id' => $categoryId, 'sort_order' => 1,
            ])->assertRedirect();
        $this->assertDatabaseHas('lessons', ['title' => 'Created A1 Lesson', 'level_id' => $levels[0]]);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('super.admin.training.index', ['level_id' => $levels[5]]))
            ->assertOk()
            ->assertSee('C2')
            ->assertViewHas('levels', fn ($rows) => $rows->count() === 6);
    }

    public function test_super_admin_can_manage_lesson_items_and_linked_questions_safely(): void
    {
        [$level, $category, $lesson] = $this->createLesson('A1');
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superAdmin);

        $existingQuestionId = DB::table('vocabularies')->insertGetId([
            'lesson_id' => $lesson->id, 'word' => 'わたし', 'reading' => 'わたし', 'meaning' => 'I',
            'question_type' => 'word_selection', 'question' => 'Existing question',
            'correct_answer' => 'I', 'options' => 'I,you', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('super.admin.training.lessons.show', $lesson))->assertOk()->assertSee('Add vocabulary / learning item');
        $this->post(route('super.admin.training.items.store', $lesson), [
            'japanese' => 'こんにちは', 'reading' => 'こんにちは', 'english' => 'hello', 'sort_order' => 1,
        ])->assertRedirect();
        $itemId = DB::table('lesson_vocabulary_items')->where('lesson_id', $lesson->id)->value('id');
        $this->assertNotNull($itemId);

        $this->put(route('super.admin.training.items.update', [$lesson, $itemId]), [
            'japanese' => 'おはよう', 'reading' => 'おはよう', 'english' => 'good morning', 'sort_order' => 0,
        ])->assertRedirect();
        $this->assertDatabaseHas('lesson_vocabulary_items', ['id' => $itemId, 'english' => 'good morning']);

        $this->post(route('super.admin.training.questions.store', $lesson), [
            'question_type' => 'word_selection',
            'learning_item_id' => $itemId,
            'stage' => 'word_recognition',
            'stage_order' => 1,
            'question' => 'Choose the greeting meaning good morning.',
            'options' => "good morning\nhello",
            'correct_answer' => 'good morning',
        ])->assertRedirect();

        $questionId = DB::table('vocabularies')->where('question', 'Choose the greeting meaning good morning.')->value('id');
        $this->assertDatabaseHas('lesson_vocabulary_questions', [
            'lesson_vocabulary_item_id' => $itemId,
            'vocabulary_question_id' => $questionId,
            'stage' => 'word_recognition',
            'stage_order' => 1,
        ]);

        $this->put(route('super.admin.training.questions.update', [$lesson, $questionId]), [
            'current_item_id' => $itemId,
            'current_stage' => 'word_recognition',
            'question_type' => 'translation_tiles',
            'learning_item_id' => $itemId,
            'stage' => 'usage',
            'stage_order' => 2,
            'question' => 'Translate the phrase.',
            'options' => "good\nmorning",
            'correct_answer' => 'good morning',
        ])->assertRedirect();
        $this->assertDatabaseHas('vocabularies', ['id' => $questionId, 'question_type' => 'translation_tiles']);
        $this->assertDatabaseHas('lesson_vocabulary_questions', [
            'lesson_vocabulary_item_id' => $itemId,
            'vocabulary_question_id' => $questionId,
            'stage' => 'usage',
            'stage_order' => 2,
        ]);

        $this->delete(route('super.admin.training.items.destroy', [$lesson, $itemId]))->assertRedirect();
        $this->assertDatabaseMissing('lesson_vocabulary_items', ['id' => $itemId]);
        $this->assertDatabaseHas('vocabularies', ['id' => $questionId]);
        $this->assertDatabaseMissing('lesson_vocabulary_questions', ['lesson_vocabulary_item_id' => $itemId]);

        $this->delete(route('super.admin.training.questions.destroy', [$lesson, $questionId]))->assertRedirect();
        $this->assertDatabaseMissing('vocabularies', ['id' => $questionId]);
        $this->assertDatabaseHas('vocabularies', ['id' => $existingQuestionId, 'question' => 'Existing question']);
    }

    public function test_question_validation_rejects_unsupported_type_stage_and_cross_lesson_item(): void
    {
        [, , $lesson] = $this->createLesson('A1');
        [, , $otherLesson] = $this->createLesson('A2');
        $itemId = DB::table('lesson_vocabulary_items')->insertGetId([
            'lesson_id' => $otherLesson->id,
            'japanese' => 'ありがとう', 'reading' => 'ありがとう', 'english' => 'thank you',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->from(route('super.admin.training.lessons.show', $lesson))
            ->post(route('super.admin.training.items.store', $lesson), [
                'japanese' => '日本語', 'reading' => 'にほんご', 'english' => 'Japanese', 'sort_order' => 0,
            ])->assertSessionHasErrors('japanese');
        $this->assertDatabaseCount('lesson_vocabulary_items', 1);

        $payload = [
            'question_type' => 'made_up_type', 'learning_item_id' => $itemId,
            'stage' => 'made_up_stage', 'stage_order' => 0,
            'question' => 'Question?', 'options' => "yes\nno", 'correct_answer' => 'yes',
        ];

        $this->from(route('super.admin.training.lessons.show', $lesson))
            ->post(route('super.admin.training.questions.store', $lesson), $payload)
            ->assertSessionHasErrors(['question_type', 'learning_item_id', 'stage']);
        $this->assertDatabaseCount('vocabularies', 0);
    }

    public function test_student_training_automatically_loads_content_created_through_management(): void
    {
        [, , $lesson] = $this->createLesson('A1');
        $itemId = DB::table('lesson_vocabulary_items')->insertGetId([
            'lesson_id' => $lesson->id,
            'japanese' => 'こんにちは', 'reading' => 'こんにちは', 'english' => 'hello',
            'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $unlinkedQuestionId = DB::table('vocabularies')->insertGetId([
            'lesson_id' => $lesson->id,
            'word' => 'さようなら', 'reading' => 'さようなら', 'meaning' => 'goodbye',
            'question_type' => 'word_selection', 'question' => 'Legacy unlinked question',
            'correct_answer' => 'goodbye', 'options' => 'goodbye,hello', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $questionId = DB::table('vocabularies')->insertGetId([
            'lesson_id' => $lesson->id,
            'word' => 'こんにちは', 'reading' => 'こんにちは', 'meaning' => 'hello',
            'question_type' => 'word_selection', 'question' => 'Choose the meaning of こんにちは.',
            'correct_answer' => 'hello', 'options' => 'hello,goodbye', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('lesson_vocabulary_questions')->insert([
            'lesson_vocabulary_item_id' => $itemId,
            'vocabulary_question_id' => $questionId,
            'stage' => 'word_recognition', 'stage_order' => 0,
        ]);
        DB::table('lesson_vocabulary_questions')->insert([
            'lesson_vocabulary_item_id' => $itemId,
            'vocabulary_question_id' => $questionId,
            'stage' => 'review', 'stage_order' => 1,
        ]);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('isTeaching', true)
            ->assertViewHas('currentLearningItem', fn ($item) => (int) $item->id === $itemId);

        $this->post(route('user.lesson.learning.complete', [$lesson, $itemId]))->assertRedirect();
        $this->get(route('user.training', $lesson->id))
            ->assertOk()
            ->assertViewHas('lesson', function ($viewLesson) use ($questionId, $unlinkedQuestionId): bool {
                $questionIds = $viewLesson->vocabularies->pluck('id');

                return $questionIds->count() === 2
                    && $questionIds->unique()->count() === 2
                    && $questionIds->contains($questionId)
                    && $questionIds->contains($unlinkedQuestionId);
            });

        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $questionId,
            'answer' => 'hello',
        ])->assertOk()->assertJson(['correct' => true]);
        $this->post(route('user.lesson.answer', $lesson), [
            'vocabulary_id' => $unlinkedQuestionId,
            'answer' => 'goodbye',
        ])->assertOk()->assertJson(['correct' => true, 'lesson_completed' => true]);
    }

    public function test_vocabulary_media_is_retained_while_another_question_references_it(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('vocabularies/shared.png', 'shared image');
        Storage::disk('public')->put('audios/shared.mp3', 'shared audio');

        [, , $lesson] = $this->createLesson('A1');
        $attributes = [
            'lesson_id' => $lesson->id,
            'word' => 'test',
            'reading' => 'test',
            'meaning' => 'test',
            'question_type' => 'image_selection',
            'question' => 'Choose one.',
            'options' => 'A,B',
            'correct_answer' => 'A',
            'image_url' => json_encode(['A' => 'vocabularies/shared.png']),
            'audio' => 'audios/shared.mp3',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $questionId = DB::table('vocabularies')->insertGetId($attributes);
        DB::table('vocabularies')->insert($attributes);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->put(route('super.admin.vocabularies.update', $questionId), [
                'question_type' => 'word_selection',
                'question' => 'Choose one.',
                'correct_answer' => 'B',
                'options' => 'B',
                'audio' => UploadedFile::fake()->create('replacement.mp3', 10, 'audio/mpeg'),
            ])->assertRedirect();

        Storage::disk('public')->assertExists('vocabularies/shared.png');
        Storage::disk('public')->assertExists('audios/shared.mp3');

        $updatedQuestion = DB::table('vocabularies')->where('id', $questionId)->first();
        $newAudioPath = $updatedQuestion->audio;
        Storage::disk('public')->assertExists($newAudioPath);
    $this->assertNull($updatedQuestion->image_url);

        $this->delete(route('super.admin.vocabularies.destroy', $questionId))->assertRedirect();

        Storage::disk('public')->assertExists('vocabularies/shared.png');
        Storage::disk('public')->assertExists('audios/shared.mp3');
        Storage::disk('public')->assertMissing($newAudioPath);
    }

    private function createLesson(string $code): array
    {
        $levelId = $this->createLevel($code);
        $now = now();
        $categoryId = DB::table('categories')->insertGetId([
            'level_id' => $levelId, 'title' => $code . ' Category', 'sort_order' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $lesson = Lesson::create([
            'level_id' => $levelId, 'category_id' => $categoryId, 'title' => $code . ' Lesson',
            'type' => 'training', 'score' => 0, 'sort_order' => 0,
        ]);

        return [$levelId, $categoryId, $lesson];
    }

    private function createLevel(string $code): int
    {
        $now = now();

        return DB::table('levels')->insertGetId([
            'name' => $code, 'level' => $code, 'tier' => $code, 'description' => $code . ' level',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
