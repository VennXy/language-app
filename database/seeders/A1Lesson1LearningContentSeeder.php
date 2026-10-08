<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class A1Lesson1LearningContentSeeder extends Seeder
{
    private const LESSON_ID = 3;

    private const STAGES = [
        'picture_recognition',
        'word_recognition',
        'recall',
        'listening',
        'usage',
        'review',
    ];

    /**
     * Curated A1 Lesson 1 mappings only. This seeder is intentionally not
     * registered in DatabaseSeeder and must be run explicitly.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $lesson = Lesson::with('level')->find(self::LESSON_ID);

            if (!$lesson
                || $lesson->title !== 'Greetings & Self Introduction'
                || ($lesson->level->level ?? null) !== 'A1') {
                throw new RuntimeException('Expected A1 Lesson 1 (ID 3) was not found.');
            }

            $items = [
                [
                    'japanese' => 'はじめまして',
                    'reading' => 'はじめまして',
                    'english' => 'Nice to meet you (when meeting someone for the first time).',
                    'sort_order' => 1,
                    'questions' => [
                        [
                            'id' => 149,
                            'question' => 'はじめまして わたし は たなか です',
                            'correct_answer' => 'Nice to meet you. I am Tanaka.',
                            'stage' => 'usage',
                            'stage_order' => 1,
                        ],
                    ],
                ],
                [
                    'japanese' => 'わたし',
                    'reading' => 'わたし',
                    'english' => 'I; me.',
                    'sort_order' => 2,
                    'questions' => [
                        [
                            'id' => 149,
                            'question' => 'はじめまして わたし は たなか です',
                            'correct_answer' => 'Nice to meet you. I am Tanaka.',
                            'stage' => 'usage',
                            'stage_order' => 1,
                        ],
                        [
                            'id' => 150,
                            'question' => 'わたし は おおさか から きました',
                            'correct_answer' => 'I came from Osaka.',
                            'stage' => 'usage',
                            'stage_order' => 2,
                        ],
                        [
                            'id' => 268,
                            'question' => 'これはわたしのともだちです。',
                            'correct_answer' => 'This is my friend.',
                            'stage' => 'review',
                            'stage_order' => 1,
                        ],
                    ],
                ],
                [
                    'japanese' => 'おはようございます',
                    'reading' => 'おはようございます',
                    'english' => 'Good morning (polite).',
                    'sort_order' => 3,
                    'questions' => [
                        [
                            'id' => 148,
                            'question' => 'たなか さん おはよう ございます',
                            'correct_answer' => 'Good morning Mr. Tanaka.',
                            'stage' => 'usage',
                            'stage_order' => 1,
                        ],
                    ],
                ],
                [
                    'japanese' => 'よろしくおねがいします',
                    'reading' => 'よろしくおねがいします',
                    'english' => 'I look forward to getting to know you.',
                    'sort_order' => 4,
                    'questions' => [
                        [
                            'id' => 151,
                            'question' => 'どうぞ よろしく おねがいします',
                            'correct_answer' => 'I look forward to getting to know you.',
                            'stage' => 'usage',
                            'stage_order' => 1,
                        ],
                    ],
                ],
                [
                    'japanese' => 'ともだち',
                    'reading' => 'ともだち',
                    'english' => 'friend',
                    'sort_order' => 5,
                    'questions' => [
                        [
                            'id' => 152,
                            'question' => 'こちら は ともだち の やまだ さん です',
                            'correct_answer' => 'This is my friend Mr. Yamada.',
                            'stage' => 'usage',
                            'stage_order' => 1,
                        ],
                        [
                            'id' => 268,
                            'question' => 'これはわたしのともだちです。',
                            'correct_answer' => 'This is my friend.',
                            'stage' => 'review',
                            'stage_order' => 1,
                        ],
                    ],
                ],
                [
                    'japanese' => 'なまえ',
                    'reading' => 'なまえ',
                    'english' => 'name',
                    'sort_order' => 6,
                    'questions' => [],
                ],
                [
                    'japanese' => 'こんにちは',
                    'reading' => 'こんにちは',
                    'english' => 'Hello; good afternoon.',
                    'sort_order' => 7,
                    'questions' => [],
                ],
            ];

            foreach ($items as $definition) {
                $this->assertKanaOnly($definition['japanese']);
                $this->assertKanaOnly($definition['reading']);

                $item = DB::table('lesson_vocabulary_items')
                    ->where('lesson_id', $lesson->id)
                    ->where('japanese', $definition['japanese'])
                    ->where('reading', $definition['reading'])
                    ->first();

                $attributes = [
                    'english' => $definition['english'],
                    'image_path' => null,
                    'audio_path' => null,
                    'sort_order' => $definition['sort_order'],
                ];

                if (!$item) {
                    $now = now();
                    $itemId = DB::table('lesson_vocabulary_items')->insertGetId([
                        'lesson_id' => $lesson->id,
                        'japanese' => $definition['japanese'],
                        'reading' => $definition['reading'],
                        ...$attributes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $itemId = $item->id;
                    $changes = [];

                    foreach ($attributes as $column => $value) {
                        if ($item->{$column} != $value) {
                            $changes[$column] = $value;
                        }
                    }

                    if ($changes !== []) {
                        $changes['updated_at'] = now();
                        DB::table('lesson_vocabulary_items')->where('id', $itemId)->update($changes);
                    }
                }

                foreach ($definition['questions'] as $questionDefinition) {
                    if (!in_array($questionDefinition['stage'], self::STAGES, true)) {
                        throw new RuntimeException('Unsupported learning question stage.');
                    }

                    $question = DB::table('vocabularies')
                        ->where('id', $questionDefinition['id'])
                        ->where('lesson_id', $lesson->id)
                        ->where('question_type', 'translation_tiles')
                        ->where('question', $questionDefinition['question'])
                        ->where('correct_answer', $questionDefinition['correct_answer'])
                        ->first();

                    if (!$question
                        || !$this->containsJapaneseText($question->question, $definition['japanese'])) {
                        throw new RuntimeException(
                            "Question {$questionDefinition['id']} no longer matches the curated A1 Lesson 1 mapping."
                        );
                    }

                    $this->assertNoKanji($question->question . ' ' . $question->options);

                    $existingLinks = DB::table('lesson_vocabulary_questions')
                        ->where('lesson_vocabulary_item_id', $itemId)
                        ->where('vocabulary_question_id', $question->id)
                        ->get();

                    if ($existingLinks->count() > 1) {
                        throw new RuntimeException(
                            "Duplicate links found for learning item {$itemId} and question {$question->id}."
                        );
                    }

                    $link = $existingLinks->first();

                    if (!$link) {
                        DB::table('lesson_vocabulary_questions')->insert([
                            'lesson_vocabulary_item_id' => $itemId,
                            'vocabulary_question_id' => $question->id,
                            'stage' => $questionDefinition['stage'],
                            'stage_order' => $questionDefinition['stage_order'],
                        ]);
                        continue;
                    }

                    $changes = [];
                    if ($link->stage !== $questionDefinition['stage']) {
                        $changes['stage'] = $questionDefinition['stage'];
                    }
                    if ((int) $link->stage_order !== $questionDefinition['stage_order']) {
                        $changes['stage_order'] = $questionDefinition['stage_order'];
                    }

                    if ($changes !== []) {
                        DB::table('lesson_vocabulary_questions')
                            ->where('lesson_vocabulary_item_id', $itemId)
                            ->where('vocabulary_question_id', $question->id)
                            ->update($changes);
                    }
                }
            }
        });
    }

    private function assertKanaOnly(string $text): void
    {
        if (!preg_match('/^[ぁ-ゖァ-ヺー・。、！？「」『』（）\s]+$/u', $text)) {
            throw new RuntimeException('Learning item Japanese and reading fields must contain kana only.');
        }
    }

    private function assertNoKanji(string $text): void
    {
        if (preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $text)) {
            throw new RuntimeException('A linked A1 Lesson 1 question contains Kanji.');
        }
    }

    private function containsJapaneseText(string $question, string $japanese): bool
    {
        $normalizedQuestion = preg_replace('/\s+/u', '', $question) ?? $question;
        $normalizedJapanese = preg_replace('/\s+/u', '', $japanese) ?? $japanese;

        return str_contains($normalizedQuestion, $normalizedJapanese);
    }
}
