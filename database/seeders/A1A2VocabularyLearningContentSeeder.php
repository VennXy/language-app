<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Populate the A1/A2 learning cards (lesson_vocabulary_items) and link them to
 * the existing practice questions (lesson_vocabulary_questions), restoring the
 * student learning flow for the recovered A1/A2 lessons.
 *
 * - A1 cards reproduce the project's original curated mappings
 *   (A1Lesson1LearningContentSeeder + A1Lessons2To4LearningContentSeeder),
 *   re-resolved against the current question rows by exact text/answer.
 * - A2 cards are authored to match each restored lesson's actual questions.
 * - Idempotent: items/links are only inserted when missing; safe to re-run.
 * - Only touches lessons of levels A1 and A2. Media stays null (no assets).
 */
class A1A2VocabularyLearningContentSeeder extends Seeder
{
    private const STAGES = [
        'picture_recognition',
        'word_recognition',
        'recall',
        'listening',
        'usage',
        'review',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->content() as $levelCode => $lessons) {
                $level = DB::table('levels')->where('level', $levelCode)->first();

                if (!$level) {
                    throw new RuntimeException("The {$levelCode} level was not found.");
                }

                foreach ($lessons as $definition) {
                    $matches = DB::table('lessons')
                        ->where('level_id', $level->id)
                        ->where('sort_order', $definition['sort_order'])
                        ->where('title', $definition['title'])
                        ->get();

                    if ($matches->count() !== 1) {
                        throw new RuntimeException(
                            "Expected exactly one {$levelCode} lesson at position {$definition['sort_order']} titled '{$definition['title']}'."
                        );
                    }

                    $this->seedLesson((int) $matches->first()->id, $definition['items']);
                }
            }
        });
    }

    private function seedLesson(int $lessonId, array $items): void
    {
        foreach ($items as $definition) {
            $this->assertKanaOnly($definition['japanese']);
            $this->assertKanaOnly($definition['reading']);

            $item = DB::table('lesson_vocabulary_items')
                ->where('lesson_id', $lessonId)
                ->where('japanese', $definition['japanese'])
                ->where('reading', $definition['reading'])
                ->first();

            if (!$item) {
                $now = now();
                $itemId = (int) DB::table('lesson_vocabulary_items')->insertGetId([
                    'lesson_id' => $lessonId,
                    'japanese' => $definition['japanese'],
                    'reading' => $definition['reading'],
                    'english' => $definition['english'],
                    'image_path' => null,
                    'audio_path' => null,
                    'sort_order' => $definition['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $itemId = (int) $item->id;
            }

            foreach ($definition['questions'] as $link) {
                if (!in_array($link['stage'], self::STAGES, true)) {
                    throw new RuntimeException("Unsupported learning stage '{$link['stage']}'.");
                }

                $question = DB::table('vocabularies')
                    ->where('lesson_id', $lessonId)
                    ->where('question_type', $link['type'])
                    ->where('question', $link['question'])
                    ->where('correct_answer', $link['answer'])
                    ->get(['id']);

                if ($question->count() !== 1) {
                    throw new RuntimeException(
                        "Expected exactly one {$link['type']} question in lesson {$lessonId}: {$link['question']}"
                    );
                }

                $questionId = (int) $question->first()->id;

                $linkExists = DB::table('lesson_vocabulary_questions')
                    ->where('lesson_vocabulary_item_id', $itemId)
                    ->where('vocabulary_question_id', $questionId)
                    ->where('stage', $link['stage'])
                    ->exists();

                if (!$linkExists) {
                    DB::table('lesson_vocabulary_questions')->insert([
                        'lesson_vocabulary_item_id' => $itemId,
                        'vocabulary_question_id' => $questionId,
                        'stage' => $link['stage'],
                        'stage_order' => $link['stage_order'],
                    ]);
                }
            }
        }
    }

    private function assertKanaOnly(string $text): void
    {
        if (preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $text)) {
            throw new RuntimeException('Kanji is not allowed in learning card text: ' . $text);
        }
    }

    private function content(): array
    {
        return [
            'A1' => [
                [
                    'title' => 'Greetings & Self Introduction',
                    'sort_order' => 1,
                    'items' => [
                        ['japanese' => 'はじめまして', 'reading' => 'はじめまして', 'english' => 'Nice to meet you (when meeting someone for the first time).', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'はじめまして わたし は たなか です', 'answer' => 'Nice to meet you. I am Tanaka.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'わたし', 'reading' => 'わたし', 'english' => 'I; me.', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'はじめまして わたし は たなか です', 'answer' => 'Nice to meet you. I am Tanaka.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'わたし は おおさか から きました', 'answer' => 'I came from Osaka.', 'stage' => 'usage', 'stage_order' => 2],
                            ['type' => 'translation_tiles', 'question' => 'これはわたしのともだちです。', 'answer' => 'This is my friend.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'おはようございます', 'reading' => 'おはようございます', 'english' => 'Good morning (polite).', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'たなか さん おはよう ございます', 'answer' => 'Good morning Mr. Tanaka.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'よろしくおねがいします', 'reading' => 'よろしくおねがいします', 'english' => 'I look forward to getting to know you.', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'どうぞ よろしく おねがいします', 'answer' => 'I look forward to getting to know you.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ともだち', 'reading' => 'ともだち', 'english' => 'friend', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'こちら は ともだち の やまだ さん です', 'answer' => 'This is my friend Mr. Yamada.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'これはわたしのともだちです。', 'answer' => 'This is my friend.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'なまえ', 'reading' => 'なまえ', 'english' => 'name', 'sort_order' => 6, 'questions' => []],
                        ['japanese' => 'こんにちは', 'reading' => 'こんにちは', 'english' => 'Hello; good afternoon.', 'sort_order' => 7, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「こんにちは」のいみはどれですか？', 'answer' => 'hello', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Numbers, Time & Dates',
                    'sort_order' => 2,
                    'items' => [
                        ['japanese' => 'いま', 'reading' => 'いま', 'english' => 'now; at this time', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'いま なんじ ですか', 'answer' => 'What time is it now?', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'なんじ', 'reading' => 'なんじ', 'english' => 'what time', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'いま なんじ ですか', 'answer' => 'What time is it now?', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'まいあさ', 'reading' => 'まいあさ', 'english' => 'every morning', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'まいあさ ななじ に おきます', 'answer' => 'I get up at seven every morning.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ななじ', 'reading' => 'ななじ', 'english' => 'seven o’clock', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'まいあさ ななじ に おきます', 'answer' => 'I get up at seven every morning.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'きょう', 'reading' => 'きょう', 'english' => 'today', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きょうは ごがつ さんにち です', 'answer' => 'Today is May third.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'きょうはろくがついちにちです。', 'answer' => 'Today is June first.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ごがつさんにち', 'reading' => 'ごがつさんにち', 'english' => 'May third; May 3', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きょうは ごがつ さんにち です', 'answer' => 'Today is May third.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'にちようび', 'reading' => 'にちようび', 'english' => 'Sunday', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'にちようび に ともだち と えいが を みます', 'answer' => 'I watch a movie with my friend on Sunday.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'にじからさんじまで', 'reading' => 'にじからさんじまで', 'english' => 'from two o’clock to three o’clock', 'sort_order' => 8, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'にじ から さんじ まで べんきょうします', 'answer' => 'I study from two to three.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'はん', 'reading' => 'はん', 'english' => 'half', 'sort_order' => 9, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「はん」のいみはどれですか？', 'answer' => 'half', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Daily Life',
                    'sort_order' => 3,
                    'items' => [
                        ['japanese' => 'あさごはん', 'reading' => 'あさごはん', 'english' => 'breakfast', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あさごはん を たべて がっこう へ いきます', 'answer' => 'I eat breakfast and go to school.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'たべます', 'reading' => 'たべます', 'english' => 'to eat', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あさごはん を たべて がっこう へ いきます', 'answer' => 'I eat breakfast and go to school.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'としょかん', 'reading' => 'としょかん', 'english' => 'library', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'としょかん で にほんご を べんきょうします', 'answer' => 'I study Japanese at the library.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'べんきょうします', 'reading' => 'べんきょうします', 'english' => 'to study', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'としょかん で にほんご を べんきょうします', 'answer' => 'I study Japanese at the library.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'ひるごはんのあとでべんきょうします。', 'answer' => 'I study after lunch.', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'きのう', 'reading' => 'きのう', 'english' => 'yesterday', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きのう は いえ で ほん を よみました', 'answer' => 'I read a book at home yesterday.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'よみます', 'reading' => 'よみます', 'english' => 'to read', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きのう は いえ で ほん を よみました', 'answer' => 'I read a book at home yesterday.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'しゅうまつはいえでほんをよみます。', 'answer' => 'I read books at home on weekends.', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'しゅうまつ', 'reading' => 'しゅうまつ', 'english' => 'weekend; weekends', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'しゅうまつ に へや を そうじします', 'answer' => 'I clean my room on weekends.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'しゅうまつはいえでほんをよみます。', 'answer' => 'I read books at home on weekends.', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'あらいます', 'reading' => 'あらいます', 'english' => 'to wash', 'sort_order' => 8, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「あらいます」のいみはどれですか？', 'answer' => 'to wash', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Places & Activities',
                    'sort_order' => 4,
                    'items' => [
                        ['japanese' => 'えきのまえ', 'reading' => 'えきのまえ', 'english' => 'in front of the station', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'えき の まえ に ぎんこう が あります', 'answer' => 'There is a bank in front of the station.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'えきのまえにぎんこうがあります。', 'answer' => 'There is a bank in front of the station.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ぎんこう', 'reading' => 'ぎんこう', 'english' => 'bank', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'えき の まえ に ぎんこう が あります', 'answer' => 'There is a bank in front of the station.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'えきのまえにぎんこうがあります。', 'answer' => 'There is a bank in front of the station.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'こうえん', 'reading' => 'こうえん', 'english' => 'park', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'こうえん で ともだち と サッカー を します', 'answer' => 'I play soccer with my friend in the park.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'こうえんでともだちとテニスをします。', 'answer' => 'I play tennis with a friend in the park.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'としょかん', 'reading' => 'としょかん', 'english' => 'library', 'sort_order' => 4, 'questions' => [
                            ['type' => 'phrase_ordering', 'question' => 'としょかんで にほんごを べんきょうします。', 'answer' => 'としょかんでにほんごをべんきょうします。', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'スーパー', 'reading' => 'スーパー', 'english' => 'supermarket', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'スーパー で ぎゅうにゅう と パン を かいます', 'answer' => 'I buy milk and bread at the supermarket.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'がっこう', 'reading' => 'がっこう', 'english' => 'school', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'がっこう まで バス で いきます', 'answer' => 'I go to school by bus.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'がっこうまであるいてじゅうぶんです。', 'answer' => 'It takes ten minutes to walk to school.', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'みぎ', 'reading' => 'みぎ', 'english' => 'right; the right side', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'みぎ に まがって ください', 'answer' => 'Please turn right.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ひだり', 'reading' => 'ひだり', 'english' => 'left; the left side', 'sort_order' => 8, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「ひだり」のいみはどれですか？', 'answer' => 'left', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
            ],
            'A2' => [
                [
                    'title' => 'Daily Routines',
                    'sort_order' => 1,
                    'items' => [
                        ['japanese' => 'あさごはん', 'reading' => 'あさごはん', 'english' => 'breakfast', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'まいあさ シャワー を あびてから あさごはん を たべます', 'answer' => 'I take a shower every morning before breakfast.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'シャワー', 'reading' => 'シャワー', 'english' => 'shower', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'まいあさ シャワー を あびてから あさごはん を たべます', 'answer' => 'I take a shower every morning before breakfast.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'あめ', 'reading' => 'あめ', 'english' => 'rain', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きのう は あめ が ふっていたので いえ で えいが を みました', 'answer' => 'It rained yesterday so I watched a movie at home.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'あめがふっていたので、バスでかえりました。', 'answer' => 'It was raining so I went home by bus.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'えいが', 'reading' => 'えいが', 'english' => 'movie; film', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きのう は あめ が ふっていたので いえ で えいが を みました', 'answer' => 'It rained yesterday so I watched a movie at home.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'はやく', 'reading' => 'はやく', 'english' => 'early; quickly', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あした は はやく おきなければ ならないので こんや は はやく ねます', 'answer' => 'I have to get up early tomorrow so I will go to bed early tonight.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'きのうはつかれていたので、はやくねました。', 'answer' => 'I was tired yesterday so I went to bed early.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ねます', 'reading' => 'ねます', 'english' => 'to go to bed; to sleep', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あした は はやく おきなければ ならないので こんや は はやく ねます', 'answer' => 'I have to get up early tomorrow so I will go to bed early tonight.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'きのうはつかれていたので、はやくねました。', 'answer' => 'I was tired yesterday so I went to bed early.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'しょくじ', 'reading' => 'しょくじ', 'english' => 'meal', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'しごと が おわったあとで ともだち と しょくじ を しました', 'answer' => 'I had a meal with a friend after work.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'しごと', 'reading' => 'しごと', 'english' => 'work; job', 'sort_order' => 8, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'しごと が おわったあとで ともだち と しょくじ を しました', 'answer' => 'I had a meal with a friend after work.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'およぎます', 'reading' => 'およぎます', 'english' => 'to swim', 'sort_order' => 9, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'しゅう に さんかい およぐように しています', 'answer' => 'I try to swim three times a week.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'でんわ', 'reading' => 'でんわ', 'english' => 'telephone; phone call', 'sort_order' => 10, 'questions' => [
                            ['type' => 'phrase_ordering', 'question' => 'えきに ついたら でんわしてください。', 'answer' => 'えきについたらでんわしてください。', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'よやくする', 'reading' => 'よやくする', 'english' => 'to make a reservation', 'sort_order' => 11, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「よやくする」のいみはどれですか？', 'answer' => 'to make a reservation', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Shopping & Food',
                    'sort_order' => 2,
                    'items' => [
                        ['japanese' => 'くつ', 'reading' => 'くつ', 'english' => 'shoes', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'この くつ は あの くつ より すこし やすいです', 'answer' => 'These shoes are a little cheaper than those.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'やすい', 'reading' => 'やすい', 'english' => 'cheap; inexpensive', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'この くつ は あの くつ より すこし やすいです', 'answer' => 'These shoes are a little cheaper than those.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'このみせはやすくて、おいしいです。', 'answer' => 'This restaurant is inexpensive and delicious.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'しちゃくします', 'reading' => 'しちゃくします', 'english' => 'to try on (clothes)', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'すみません しちゃくしても いいですか', 'answer' => 'Excuse me may I try this on?', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'しちゃくしてもいいですか。', 'answer' => 'May I try this on?', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'コーヒー', 'reading' => 'コーヒー', 'english' => 'coffee', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'コーヒー を ふたつ おねがいします', 'answer' => 'Two coffees please.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'りょうり', 'reading' => 'りょうり', 'english' => 'dish; cuisine; cooking', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'つらくない りょうり を おすすめして いただけますか', 'answer' => 'Could you recommend a dish that is not spicy?', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ごうけい', 'reading' => 'ごうけい', 'english' => 'total; sum', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'ごうけい で いくら に なりますか', 'answer' => 'How much is the total?', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'みせ', 'reading' => 'みせ', 'english' => 'shop; store', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'このみせはやすくて、おいしいです。', 'answer' => 'This restaurant is inexpensive and delicious.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'やさい', 'reading' => 'やさい', 'english' => 'vegetables', 'sort_order' => 8, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'やさいをおおめにしてください。', 'answer' => 'Please give me extra vegetables.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'おちゃ', 'reading' => 'おちゃ', 'english' => 'tea', 'sort_order' => 9, 'questions' => [
                            ['type' => 'phrase_ordering', 'question' => 'あたたかいおちゃを ひとつ おねがいします。', 'answer' => 'あたたかいおちゃをひとつおねがいします。', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ねだん', 'reading' => 'ねだん', 'english' => 'price', 'sort_order' => 10, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「ねだん」のいみはどれですか？', 'answer' => 'price', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Travel & Directions',
                    'sort_order' => 3,
                    'items' => [
                        ['japanese' => 'でんしゃ', 'reading' => 'でんしゃ', 'english' => 'train', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'つぎ の でんしゃ は なんばんせん から でますか', 'answer' => 'Which platform does the next train leave from?', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'くうこうまででんしゃでいちじかんかかります。', 'answer' => 'It takes an hour by train to the airport.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'えき', 'reading' => 'えき', 'english' => 'train station', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'えき に ついたら みぎ へ まがって ください', 'answer' => 'When you get to the station please turn right.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'みぎ', 'reading' => 'みぎ', 'english' => 'right; the right side', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'えき に ついたら みぎ へ まがって ください', 'answer' => 'When you get to the station please turn right.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'つぎのかくをみぎにまがってください。', 'answer' => 'Please turn right at the next corner.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'みち', 'reading' => 'みち', 'english' => 'road; way; street', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'みち に まよったので こうばん で ききました', 'answer' => 'I got lost so I asked at the police box.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'こうばん', 'reading' => 'こうばん', 'english' => 'police box', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'みち に まよったので こうばん で ききました', 'answer' => 'I got lost so I asked at the police box.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ホテル', 'reading' => 'ホテル', 'english' => 'hotel', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'ホテル を よやくして おいたほうが いいです', 'answer' => 'It is better to book the hotel in advance.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'phrase_ordering', 'question' => 'ホテルは ゆうびんきょくのむかいに あります。', 'answer' => 'ホテルはゆうびんきょくのむかいにあります。', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'かさ', 'reading' => 'かさ', 'english' => 'umbrella', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あめ が おりそうなので かさ を もって いきます', 'answer' => 'It looks like it will rain so I will take an umbrella.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'くうこう', 'reading' => 'くうこう', 'english' => 'airport', 'sort_order' => 8, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'くうこうまででんしゃでいちじかんかかります。', 'answer' => 'It takes an hour by train to the airport.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'きっぷ', 'reading' => 'きっぷ', 'english' => 'ticket', 'sort_order' => 9, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'きっぷをかってから、かいさつをとおります。', 'answer' => 'After buying a ticket I go through the gate.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ゆうびんきょく', 'reading' => 'ゆうびんきょく', 'english' => 'post office', 'sort_order' => 10, 'questions' => [
                            ['type' => 'phrase_ordering', 'question' => 'ホテルは ゆうびんきょくのむかいに あります。', 'answer' => 'ホテルはゆうびんきょくのむかいにあります。', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'のりかえる', 'reading' => 'のりかえる', 'english' => 'to transfer (trains); to change trains', 'sort_order' => 11, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「のりかえる」のいみはどれですか？', 'answer' => 'to transfer', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
                [
                    'title' => 'Experiences & Plans',
                    'sort_order' => 4,
                    'items' => [
                        ['japanese' => 'ほっかいどう', 'reading' => 'ほっかいどう', 'english' => 'Hokkaido', 'sort_order' => 1, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'ほっかいどう へ おこなったこと が あります', 'answer' => 'Have you ever been to Hokkaido?', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'らいげつ', 'reading' => 'らいげつ', 'english' => 'next month', 'sort_order' => 2, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'らいげつ ひっこす つもり です', 'answer' => 'I plan to move next month.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ひっこす', 'reading' => 'ひっこす', 'english' => 'to move (house); to relocate', 'sort_order' => 3, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'らいげつ ひっこす つもり です', 'answer' => 'I plan to move next month.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'つもり', 'reading' => 'つもり', 'english' => 'intention; plan', 'sort_order' => 4, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'らいげつ ひっこす つもり です', 'answer' => 'I plan to move next month.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'らいねん、きょうとへりょこうするつもりです。', 'answer' => 'I plan to travel to Kyoto next year.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'じかん', 'reading' => 'じかん', 'english' => 'time; hour', 'sort_order' => 5, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'じかん が あれば びじゅつかん に いきたいです', 'answer' => 'If I have time I want to go to the art museum.', 'stage' => 'usage', 'stage_order' => 1],
                            ['type' => 'phrase_ordering', 'question' => 'じかんが あれば はくぶつかんにもいきたいです。', 'answer' => 'じかんがあればはくぶつかんにもいきたいです。', 'stage' => 'usage', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'びじゅつかん', 'reading' => 'びじゅつかん', 'english' => 'art museum', 'sort_order' => 6, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'じかん が あれば びじゅつかん に いきたいです', 'answer' => 'If I have time I want to go to the art museum.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'にっぽんりょうり', 'reading' => 'にっぽんりょうり', 'english' => 'Japanese food; Japanese cuisine', 'sort_order' => 7, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'にっぽんりょうり を つくってみたけれど すこし しおからく なりました', 'answer' => 'I tried making Japanese food but it turned out a little salty.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'ともだち', 'reading' => 'ともだち', 'english' => 'friend', 'sort_order' => 8, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'いそがしかったので ともだち に あえませんでした', 'answer' => 'I was busy so I could not meet my friend.', 'stage' => 'usage', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'らいねん', 'reading' => 'らいねん', 'english' => 'next year', 'sort_order' => 9, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'らいねん、きょうとへりょこうするつもりです。', 'answer' => 'I plan to travel to Kyoto next year.', 'stage' => 'review', 'stage_order' => 1],
                        ]],
                        ['japanese' => 'えいが', 'reading' => 'えいが', 'english' => 'movie; film', 'sort_order' => 10, 'questions' => [
                            ['type' => 'translation_tiles', 'question' => 'あめなら、いえでえいがをみましょう。', 'answer' => 'If it rains let us watch a movie at home.', 'stage' => 'review', 'stage_order' => 1],
                            ['type' => 'translation_tiles', 'question' => 'せんしゅうみたえいがはとてもおもしろかったです。', 'answer' => 'The movie I saw last week was very interesting.', 'stage' => 'review', 'stage_order' => 2],
                        ]],
                        ['japanese' => 'けいけん', 'reading' => 'けいけん', 'english' => 'experience', 'sort_order' => 11, 'questions' => [
                            ['type' => 'word_selection', 'question' => '「けいけん」のいみはどれですか？', 'answer' => 'experience', 'stage' => 'word_recognition', 'stage_order' => 1],
                        ]],
                    ],
                ],
            ],
        ];
    }
}
