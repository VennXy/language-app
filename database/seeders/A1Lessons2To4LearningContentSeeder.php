<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class A1Lessons2To4LearningContentSeeder extends Seeder
{
    private const STAGES = [
        'picture_recognition',
        'word_recognition',
        'recall',
        'listening',
        'usage',
        'review',
    ];

    private const LESSONS = [
        2 => [
            'title' => 'Numbers, Time & Dates',
            'items' => [
                ['japanese' => 'いま', 'reading' => 'いま', 'english' => 'now; at this time', 'sort_order' => 1, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'いま なんじ ですか', 'options' => 'What,time,is,it,now?', 'answer' => 'What time is it now?', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'いま'],
                ]],
                ['japanese' => 'なんじ', 'reading' => 'なんじ', 'english' => 'what time', 'sort_order' => 2, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'いま なんじ ですか', 'options' => 'What,time,is,it,now?', 'answer' => 'What time is it now?', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'なんじ'],
                ]],
                ['japanese' => 'まいあさ', 'reading' => 'まいあさ', 'english' => 'every morning', 'sort_order' => 3, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'まいあさ ななじ に おきます', 'options' => 'I,get,up,at,seven,every,morning.', 'answer' => 'I get up at seven every morning.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'まいあさ'],
                ]],
                ['japanese' => 'ななじ', 'reading' => 'ななじ', 'english' => 'seven o’clock', 'sort_order' => 4, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'まいあさ ななじ に おきます', 'options' => 'I,get,up,at,seven,every,morning.', 'answer' => 'I get up at seven every morning.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'ななじ'],
                ]],
                ['japanese' => 'きょう', 'reading' => 'きょう', 'english' => 'today', 'sort_order' => 5, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'きょうは ごがつ さんにち です', 'options' => 'Today,is,May,third.', 'answer' => 'Today is May third.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'きょう'],
                    ['type' => 'translation_tiles', 'question' => 'きょうはろくがついちにちです。', 'options' => 'Today,is,June,first.,Today is July first.', 'answer' => 'Today is June first.', 'stage' => 'review', 'stage_order' => 1, 'evidence' => 'きょう'],
                ]],
                ['japanese' => 'ごがつさんにち', 'reading' => 'ごがつさんにち', 'english' => 'May third; May 3', 'sort_order' => 6, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'きょうは ごがつ さんにち です', 'options' => 'Today,is,May,third.', 'answer' => 'Today is May third.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'ごがつさんにち'],
                ]],
                ['japanese' => 'にちようび', 'reading' => 'にちようび', 'english' => 'Sunday', 'sort_order' => 7, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'にちようび に ともだち と えいが を みます', 'options' => 'I,watch,a,movie,with,my,friend,on,Sunday.', 'answer' => 'I watch a movie with my friend on Sunday.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'にちようび'],
                ]],
                ['japanese' => 'にじからさんじまで', 'reading' => 'にじからさんじまで', 'english' => 'from two o’clock to three o’clock', 'sort_order' => 8, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'にじ から さんじ まで べんきょうします', 'options' => 'I,study,from,two,to,three.', 'answer' => 'I study from two to three.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'にじからさんじまで'],
                ]],
            ],
        ],
        3 => [
            'title' => 'Daily Life',
            'items' => [
                ['japanese' => 'あさごはん', 'reading' => 'あさごはん', 'english' => 'breakfast', 'sort_order' => 1, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'あさごはん を たべて がっこう へ いきます', 'options' => 'I,eat,breakfast,and,go,to,school.', 'answer' => 'I eat breakfast and go to school.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'あさごはん'],
                ]],
                ['japanese' => 'たべます', 'reading' => 'たべます', 'english' => 'to eat', 'sort_order' => 2, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'あさごはん を たべて がっこう へ いきます', 'options' => 'I,eat,breakfast,and,go,to,school.', 'answer' => 'I eat breakfast and go to school.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'たべて'],
                ]],
                ['japanese' => 'としょかん', 'reading' => 'としょかん', 'english' => 'library', 'sort_order' => 3, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'としょかん で にほんご を べんきょうします', 'options' => 'I,study,Japanese,at,the,library.', 'answer' => 'I study Japanese at the library.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'としょかん'],
                ]],
                ['japanese' => 'べんきょうします', 'reading' => 'べんきょうします', 'english' => 'to study', 'sort_order' => 4, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'としょかん で にほんご を べんきょうします', 'options' => 'I,study,Japanese,at,the,library.', 'answer' => 'I study Japanese at the library.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'べんきょうします'],
                    ['type' => 'translation_tiles', 'question' => 'ひるごはんのあとでべんきょうします。', 'options' => 'I,study,after,lunch.,I study before lunch.', 'answer' => 'I study after lunch.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'べんきょうします'],
                ]],
                ['japanese' => 'きのう', 'reading' => 'きのう', 'english' => 'yesterday', 'sort_order' => 5, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'きのう は いえ で ほん を よみました', 'options' => 'I,read,a,book,at,home,yesterday.', 'answer' => 'I read a book at home yesterday.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'きのう'],
                ]],
                ['japanese' => 'よみます', 'reading' => 'よみます', 'english' => 'to read', 'sort_order' => 6, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'きのう は いえ で ほん を よみました', 'options' => 'I,read,a,book,at,home,yesterday.', 'answer' => 'I read a book at home yesterday.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'よみました'],
                    ['type' => 'translation_tiles', 'question' => 'しゅうまつはいえでほんをよみます。', 'options' => 'I,read,books,at,home,on,weekends.,I write books at home on weekends.', 'answer' => 'I read books at home on weekends.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'よみます'],
                ]],
                ['japanese' => 'しゅうまつ', 'reading' => 'しゅうまつ', 'english' => 'weekend; weekends', 'sort_order' => 7, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'しゅうまつ に へや を そうじします', 'options' => 'I,clean,my,room,on,weekends.', 'answer' => 'I clean my room on weekends.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'しゅうまつ'],
                    ['type' => 'translation_tiles', 'question' => 'しゅうまつはいえでほんをよみます。', 'options' => 'I,read,books,at,home,on,weekends.,I write books at home on weekends.', 'answer' => 'I read books at home on weekends.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'しゅうまつ'],
                ]],
                ['japanese' => 'あらいます', 'reading' => 'あらいます', 'english' => 'to wash', 'sort_order' => 8, 'questions' => [
                    ['type' => 'word_selection', 'question' => '「あらいます」のいみはどれですか？', 'options' => 'to wash,to cook,to borrow', 'answer' => 'to wash', 'stage' => 'word_recognition', 'stage_order' => 1, 'evidence' => 'あらいます'],
                ]],
            ],
        ],
        4 => [
            'title' => 'Places & Activities',
            'items' => [
                ['japanese' => 'えきのまえ', 'reading' => 'えきのまえ', 'english' => 'in front of the station', 'sort_order' => 1, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'えき の まえ に ぎんこう が あります', 'options' => 'There,is,a,bank,in,front,of,the,station.', 'answer' => 'There is a bank in front of the station.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'えきのまえ'],
                    ['type' => 'translation_tiles', 'question' => 'えきのまえにぎんこうがあります。', 'options' => 'There,is,a,bank,in,front,of,the,station.,There is a bank behind the station.', 'answer' => 'There is a bank in front of the station.', 'stage' => 'review', 'stage_order' => 1, 'evidence' => 'えきのまえ'],
                ]],
                ['japanese' => 'ぎんこう', 'reading' => 'ぎんこう', 'english' => 'bank', 'sort_order' => 2, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'えき の まえ に ぎんこう が あります', 'options' => 'There,is,a,bank,in,front,of,the,station.', 'answer' => 'There is a bank in front of the station.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'ぎんこう'],
                    ['type' => 'translation_tiles', 'question' => 'えきのまえにぎんこうがあります。', 'options' => 'There,is,a,bank,in,front,of,the,station.,There is a bank behind the station.', 'answer' => 'There is a bank in front of the station.', 'stage' => 'review', 'stage_order' => 1, 'evidence' => 'ぎんこう'],
                ]],
                ['japanese' => 'こうえん', 'reading' => 'こうえん', 'english' => 'park', 'sort_order' => 3, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'こうえん で ともだち と サッカー を します', 'options' => 'I,play,soccer,with,my,friend,in,the,park.', 'answer' => 'I play soccer with my friend in the park.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'こうえん'],
                    ['type' => 'translation_tiles', 'question' => 'こうえんでともだちとテニスをします。', 'options' => 'I,play,tennis,with,a,friend,in,the,park.,I watch tennis with a friend at home.', 'answer' => 'I play tennis with a friend in the park.', 'stage' => 'review', 'stage_order' => 1, 'evidence' => 'こうえん'],
                ]],
                ['japanese' => 'としょかん', 'reading' => 'としょかん', 'english' => 'library', 'sort_order' => 4, 'questions' => [
                    ['type' => 'phrase_ordering', 'question' => 'としょかんで にほんごを べんきょうします。', 'options' => 'べんきょうします。,にほんごを,としょかんで', 'answer' => 'としょかんでにほんごをべんきょうします。', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'としょかん'],
                ]],
                ['japanese' => 'スーパー', 'reading' => 'スーパー', 'english' => 'supermarket', 'sort_order' => 5, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'スーパー で ぎゅうにゅう と パン を かいます', 'options' => 'I,buy,milk,and,bread,at,the,supermarket.', 'answer' => 'I buy milk and bread at the supermarket.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'スーパー'],
                ]],
                ['japanese' => 'がっこう', 'reading' => 'がっこう', 'english' => 'school', 'sort_order' => 6, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'がっこう まで バス で いきます', 'options' => 'I,go,to,school,by,bus.', 'answer' => 'I go to school by bus.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'がっこう'],
                    ['type' => 'translation_tiles', 'question' => 'がっこうまであるいてじゅうぶんです。', 'options' => 'It,takes,ten,minutes,to,walk,to,school.,It takes ten minutes by train to school.', 'answer' => 'It takes ten minutes to walk to school.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'がっこう'],
                ]],
                ['japanese' => 'みぎ', 'reading' => 'みぎ', 'english' => 'right; the right side', 'sort_order' => 7, 'questions' => [
                    ['type' => 'translation_tiles', 'question' => 'みぎ に まがって ください', 'options' => 'Please,turn,right.', 'answer' => 'Please turn right.', 'stage' => 'usage', 'stage_order' => 1, 'evidence' => 'みぎ'],
                ]],
                ['japanese' => 'ひだり', 'reading' => 'ひだり', 'english' => 'left; the left side', 'sort_order' => 8, 'questions' => [
                    ['type' => 'word_selection', 'question' => '「ひだり」のいみはどれですか？', 'options' => 'left,right,straight', 'answer' => 'left', 'stage' => 'word_recognition', 'stage_order' => 1, 'evidence' => 'ひだり'],
                ]],
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $level = DB::table('levels')->where('level', 'A1')->first();
            if (!$level) {
                throw new RuntimeException('The A1 level was not found.');
            }

            foreach (self::LESSONS as $sortOrder => $definition) {
                $matches = DB::table('lessons')
                    ->where('level_id', $level->id)
                    ->where('sort_order', $sortOrder)
                    ->where('title', $definition['title'])
                    ->get();

                if ($matches->count() !== 1) {
                    throw new RuntimeException(
                        "Expected exactly one A1 Lesson {$sortOrder} titled '{$definition['title']}'."
                    );
                }

                $this->seedLesson((int) $matches->first()->id, $definition['items']);
            }
        });
    }

    private function seedLesson(int $lessonId, array $definitions): void
    {
        foreach ($definitions as $definition) {
            $this->assertKanaOnly($definition['japanese']);
            $this->assertKanaOnly($definition['reading']);

            $existing = DB::table('lesson_vocabulary_items')
                ->where('lesson_id', $lessonId)
                ->where('japanese', $definition['japanese'])
                ->where('reading', $definition['reading'])
                ->first();

            $attributes = [
                'english' => $definition['english'],
                'image_path' => null,
                'audio_path' => null,
                'sort_order' => $definition['sort_order'],
            ];

            if (!$existing) {
                $now = now();
                $itemId = (int) DB::table('lesson_vocabulary_items')->insertGetId([
                    'lesson_id' => $lessonId,
                    'japanese' => $definition['japanese'],
                    'reading' => $definition['reading'],
                    ...$attributes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $itemId = (int) $existing->id;
                $changes = [];
                foreach ($attributes as $column => $value) {
                    if ($existing->{$column} != $value) {
                        $changes[$column] = $value;
                    }
                }
                if ($changes !== []) {
                    $changes['updated_at'] = now();
                    DB::table('lesson_vocabulary_items')->where('id', $itemId)->update($changes);
                }
            }

            foreach ($definition['questions'] as $questionDefinition) {
                $this->assertStageAndType($questionDefinition);
                $question = $this->findExistingQuestion($lessonId, $questionDefinition);
                $this->assertEvidence($questionDefinition['question'], $questionDefinition['evidence']);

                $link = DB::table('lesson_vocabulary_questions')
                    ->where('lesson_vocabulary_item_id', $itemId)
                    ->where('vocabulary_question_id', $question->id)
                    ->where('stage', $questionDefinition['stage'])
                    ->first();

                if (!$link) {
                    DB::table('lesson_vocabulary_questions')->insert([
                        'lesson_vocabulary_item_id' => $itemId,
                        'vocabulary_question_id' => $question->id,
                        'stage' => $questionDefinition['stage'],
                        'stage_order' => $questionDefinition['stage_order'],
                    ]);
                } elseif ((int) $link->stage_order !== $questionDefinition['stage_order']) {
                    DB::table('lesson_vocabulary_questions')
                        ->where('lesson_vocabulary_item_id', $itemId)
                        ->where('vocabulary_question_id', $question->id)
                        ->where('stage', $questionDefinition['stage'])
                        ->update(['stage_order' => $questionDefinition['stage_order']]);
                }
            }
        }
    }

    private function findExistingQuestion(int $lessonId, array $definition): object
    {
        $matches = DB::table('vocabularies')
            ->where('lesson_id', $lessonId)
            ->where('question_type', $definition['type'])
            ->where('question', $definition['question'])
            ->where('options', $definition['options'])
            ->where('correct_answer', $definition['answer'])
            ->get(['id', 'lesson_id', 'question']);

        if ($matches->count() !== 1) {
            throw new RuntimeException(
                "Expected exactly one unchanged question in lesson {$lessonId}: {$definition['question']}"
            );
        }

        return $matches->first();
    }

    private function assertStageAndType(array $definition): void
    {
        if (!in_array($definition['stage'], self::STAGES, true)) {
            throw new RuntimeException('Unsupported learning question stage.');
        }

        if ($definition['stage'] === 'word_recognition' && $definition['type'] !== 'word_selection') {
            throw new RuntimeException('Word recognition mappings must use existing word-selection questions.');
        }
    }

    private function assertKanaOnly(string $text): void
    {
        if (!preg_match('/^[\p{Hiragana}\p{Katakana}ー・、。「」！？\s]+$/u', $text)) {
            throw new RuntimeException('New learning item Japanese and reading fields must contain kana only.');
        }
    }

    private function assertEvidence(string $question, string $evidence): void
    {
        $normalizedQuestion = preg_replace('/\s+/u', '', $question) ?? $question;
        $normalizedEvidence = preg_replace('/\s+/u', '', $evidence) ?? $evidence;

        if (!str_contains($normalizedQuestion, $normalizedEvidence)) {
            throw new RuntimeException("Question evidence '{$evidence}' was not found in its unchanged prompt.");
        }
    }
}
