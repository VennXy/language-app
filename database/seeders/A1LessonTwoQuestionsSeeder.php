<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;
use RuntimeException;

class A1LessonTwoQuestionsSeeder extends Seeder
{
    /**
     * Add beginner word-selection questions to A1 Lesson-2 (ID 4).
     */
    public function run(): void
    {
        $lesson = Lesson::with('level')->findOrFail(4);
        $levelLabel = strtoupper((string) ($lesson->level?->name ?? $lesson->level?->title ?? ''));

        if (! preg_match('/\bA1\b/', $levelLabel)) {
            throw new RuntimeException('Lesson 4 is not assigned to the A1 level; no questions were added.');
        }

        $questions = [
            [
                'question' => 'これ は 本 です',
                'options' => ['これ', 'は', '本', 'です'],
                'correct_answer' => 'これ は 本 です',
            ],
            [
                'question' => 'わたし は 学生 です',
                'options' => ['わたし', 'は', '学生', 'です'],
                'correct_answer' => 'わたし は 学生 です',
            ],
            [
                'question' => '水 を ください',
                'options' => ['水', 'を', 'ください'],
                'correct_answer' => '水 を ください',
            ],
            [
                'question' => 'ねこ は かわいい です',
                'options' => ['ねこ', 'は', 'かわいい', 'です'],
                'correct_answer' => 'ねこ は かわいい です',
            ],
        ];

        foreach ($questions as $question) {
            Vocabulary::firstOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'question' => $question['question'],
                ],
                [
                    'question_type' => 'word_selection',
                    'options' => implode(',', $question['options']),
                    'correct_answer' => $question['correct_answer'],
                ]
            );
        }
    }
}
