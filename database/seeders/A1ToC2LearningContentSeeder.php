<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Level;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class A1ToC2LearningContentSeeder extends Seeder
{
    /**
     * Create the starter lesson set and text-based training questions.
     * Existing A1 lessons and questions are retained; only missing rows are added.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->alignExistingA1Lessons();

            foreach ($this->content() as $levelCode => $lessonDefinitions) {
                $level = Level::where('level', $levelCode)->first();

                if (!$level) {
                    throw new RuntimeException("Required level {$levelCode} does not exist.");
                }

                $categoryId = $this->categoryIdFor($level);
                $existingLessons = Lesson::where('level_id', $level->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->values();

                foreach ($lessonDefinitions as $lessonIndex => $definition) {
                    $lesson = null;

                    if ($levelCode === 'A1' && $lessonIndex < 2) {
                        $lesson = $existingLessons->get($lessonIndex);
                    }

                    if (!$lesson) {
                        $lesson = Lesson::firstOrCreate(
                            [
                                'level_id' => $level->id,
                                'title' => $definition['title'],
                            ],
                            [
                                'category_id' => $categoryId,
                                'type' => 'training',
                                'score' => 0,
                                'sort_order' => $definition['sort_order'],
                                'description' => $definition['description'],
                            ]
                        );
                    }

                    $questionCount = $lesson->vocabularies()->count();

                    foreach ($definition['questions'] as $questionIndex => $tokenString) {
                        $tokens = explode('|', $tokenString);

                        if (count($tokens) < 2 || in_array('', $tokens, true)) {
                            throw new RuntimeException("Invalid question data in {$levelCode} / {$definition['title']}.");
                        }

                        $originalSentence = implode(' ', $tokens);
                        $question = $this->toHiragana($originalSentence);
                        $answer = $this->englishSentenceTranslations()[$levelCode][$lessonIndex][$questionIndex];
                        $existingQuestion = Vocabulary::where('lesson_id', $lesson->id)
                            ->where(function ($query) use ($originalSentence, $question): void {
                                $query->whereIn('question', array_unique([$originalSentence, $question]))
                                    ->orWhereRaw("REPLACE(question, ' ', '') = ?", [str_replace(' ', '', $originalSentence)]);
                            })
                            ->first();

                        if ($existingQuestion) {
                            $existingQuestion->update([
                                'question_type' => 'translation_tiles',
                                'question' => $question,
                                'options' => implode(',', preg_split('/\s+/u', trim($answer))),
                                'correct_answer' => $answer,
                            ]);
                        } elseif ($questionCount < 5) {
                            Vocabulary::create([
                                'lesson_id' => $lesson->id,
                                'question_type' => 'translation_tiles',
                                'question' => $question,
                                'options' => implode(',', preg_split('/\s+/u', trim($answer))),
                                'correct_answer' => $answer,
                            ]);
                            $questionCount++;
                        }
                    }
                }
            }

            $this->correctAuditedQuestions();
            $this->correctLegacyEnglishSentenceQuestion();
            $this->correctLegacyJapaneseSentences();
            $this->normalizeJapaneseQuestionContent();
            $this->seedQuestionVariety();
            $this->validateContentRules();
        });
    }

    private function alignExistingA1Lessons(): void
    {
        $level = Level::where('level', 'A1')->first();

        if (!$level) {
            return;
        }

        $lessons = Lesson::where('level_id', $level->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($lessons->count() !== 4) {
            return;
        }

        $structure = [
            ['title' => 'Greetings & Self Introduction', 'description' => 'Basic greetings, names, origins, and polite introductions.'],
            ['title' => 'Numbers, Time & Dates', 'description' => 'Simple numbers, clock times, weekdays, and dates.'],
            ['title' => 'Daily Life', 'description' => 'Simple routines, meals, study, and daily activities.'],
            ['title' => 'Places & Activities', 'description' => 'Common places, transport, directions, and activities.'],
        ];

        foreach ($lessons as $index => $lesson) {
            $lesson->update($structure[$index] + ['sort_order' => $index + 1]);
        }
    }

    private function correctAuditedQuestions(): void
    {
        $a1 = Level::where('level', 'A1')->first();
        $firstLesson = $a1
            ? Lesson::where('level_id', $a1->id)->where('sort_order', 1)->first()
            : null;

        if (!$firstLesson) {
            return;
        }

        $readingQuestion = Vocabulary::where('lesson_id', $firstLesson->id)
            ->where('question_type', 'word_selection')
            ->where('correct_answer', 'みず')
            ->first();

        if ($readingQuestion) {
            $readingQuestion->update([
                'question' => '「みず」のよみかたはどれですか？',
                'options' => 'みず,すし,おちゃ,ごはん',
            ]);
        }

        $imageQuestion = Vocabulary::where('lesson_id', $firstLesson->id)
            ->where('question_type', 'image_selection')
            ->first();

        if (!$imageQuestion) {
            return;
        }

        $images = json_decode((string) $imageQuestion->image_url, true);
        if (!is_array($images)) {
            return;
        }
        $oldOptions = explode(',', (string) $imageQuestion->options);
        $paths = array_map(fn (string $option) => $images[$option] ?? null, $oldOptions);

        if (count($paths) !== 3 || in_array(null, $paths, true)) {
            return;
        }

        foreach ($paths as $path) {
            if (!Storage::disk('public')->exists($path)) {
                return;
            }
        }

        $labels = ['げーむがめん', 'こーどがめん', 'たーみなるがめん'];
        $imageQuestion->update([
            'question' => 'Which picture is a game?',
            'options' => implode(',', $labels),
            'correct_answer' => $labels[0],
            'image_url' => json_encode(array_combine($labels, $paths), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function seedQuestionVariety(): void
    {
        foreach ($this->supplementalQuestions() as $levelCode => $lessonGroups) {
            $level = Level::where('level', $levelCode)->firstOrFail();

            foreach ($lessonGroups as $sortOrder => $definitions) {
                $lesson = Lesson::where('level_id', $level->id)
                    ->where('sort_order', $sortOrder)
                    ->first();

                if (!$lesson) {
                    throw new RuntimeException("Missing {$levelCode} lesson at position {$sortOrder}.");
                }

                $questionCount = $lesson->vocabularies()->count();

                foreach ($definitions as $definition) {
                    $question = Vocabulary::where('lesson_id', $lesson->id)
                        ->where('question_type', $definition['question_type'])
                        ->where('question', $definition['question'])
                        ->first();

                    if ($question) {
                        $question->update([
                            'options' => implode(',', $definition['options']),
                            'correct_answer' => $definition['correct_answer'],
                        ]);
                        continue;
                    }

                    if ($questionCount >= 10) {
                        continue;
                    }

                    $question = Vocabulary::create([
                        'lesson_id' => $lesson->id,
                        'question_type' => $definition['question_type'],
                        'question' => $definition['question'],
                        'options' => implode(',', $definition['options']),
                        'correct_answer' => $definition['correct_answer'],
                    ]);

                    if ($question->wasRecentlyCreated) {
                        $questionCount++;
                    }
                }

                if ($questionCount < 10) {
                    throw new RuntimeException("Insufficient unique questions for {$levelCode} / {$lesson->title}.");
                }
            }
        }
    }

    private function translation(string $question, string $answer, string $distractor): array
    {
        return [
            'question_type' => 'translation_tiles',
            'question' => $this->toHiragana($question),
            'options' => array_merge(preg_split('/\s+/u', trim($answer)), [$distractor]),
            'correct_answer' => $answer,
        ];
    }

    private function phrase(string $sentence, array $tokens): array
    {
        $tokens = array_map(fn (string $token) => $this->toHiragana($token), $tokens);
        $displaySentence = implode(' ', $tokens);

        return [
            'question_type' => 'phrase_ordering',
            'question' => $displaySentence,
            'options' => array_reverse($tokens),
            'correct_answer' => implode('', $tokens),
        ];
    }

    private function vocabulary(string $question, string $answer, array $distractors): array
    {
        return [
            'question_type' => 'word_selection',
            'question' => $this->toHiragana($question),
            'options' => array_merge([$answer], $distractors),
            'correct_answer' => $answer,
        ];
    }

    private function correctLegacyJapaneseSentences(): void
    {
        $a1 = Level::where('level', 'A1')->first();
        $numbersLesson = $a1
            ? Lesson::where('level_id', $a1->id)->where('sort_order', 2)->first()
            : null;

        if (!$numbersLesson) {
            return;
        }

        $translations = [
            'これ は 本 です' => 'This is a book.',
            'わたし は 学生 です' => 'I am a student.',
            '水 を ください' => 'Please give me water.',
            'ねこ は かわいい です' => 'The cat is cute.',
        ];

        foreach ($translations as $original => $answer) {
            $question = $this->toHiragana($original);
            $record = Vocabulary::where('question_type', 'word_selection')
                ->where('lesson_id', $numbersLesson->id)
                ->whereIn('question', [$original, $question])
                ->first();

            if ($record) {
                $record->update([
                    'question_type' => 'translation_tiles',
                    'question' => $question,
                    'options' => implode(',', preg_split('/\s+/u', trim($answer))),
                    'correct_answer' => $answer,
                ]);
            }
        }
    }

    private function correctLegacyEnglishSentenceQuestion(): void
    {
        $a1 = Level::where('level', 'A1')->first();
        $firstLesson = $a1
            ? Lesson::where('level_id', $a1->id)->where('sort_order', 1)->first()
            : null;

        if (!$firstLesson) {
            return;
        }

        $question = Vocabulary::where('lesson_id', $firstLesson->id)
            ->whereIn('question', ['みずです', 'みずです。'])
            ->whereIn('correct_answer', ['it is water', 'It is water.'])
            ->first();

        if ($question) {
            $answer = 'It is water.';
            $question->update([
                'question_type' => 'translation_tiles',
                'question' => 'みずです。',
                'options' => implode(',', array_merge(preg_split('/\s+/u', $answer), ['please', 'hat'])),
                'correct_answer' => $answer,
            ]);
        }
    }

    private function normalizeJapaneseQuestionContent(): void
    {
        foreach (Vocabulary::all() as $question) {
            $prompt = $this->toHiragana((string) $question->question);
            $options = array_map(
                fn (string $option) => $this->toHiragana($option),
                explode(',', (string) $question->options)
            );
            $answer = $this->toHiragana((string) $question->correct_answer);

            if ($prompt !== $question->question
                || implode(',', $options) !== $question->options
                || $answer !== $question->correct_answer) {
                $question->update([
                    'question' => $prompt,
                    'options' => implode(',', $options),
                    'correct_answer' => $answer,
                ]);
            }
        }
    }

    private function validateContentRules(): void
    {
        $lessons = Lesson::withCount('vocabularies')->get();
        $supportedTypes = ['word_selection', 'phrase_ordering', 'translation_tiles', 'image_selection'];
        $kanjiPattern = '/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u';
        $kanaPattern = '/[\x{3040}-\x{30FF}]/u';

        if ($lessons->count() !== 24 || $lessons->contains(fn (Lesson $lesson) => $lesson->vocabularies_count !== 10)) {
            throw new RuntimeException('The content set must contain 24 lessons with 10 questions each.');
        }

        foreach (Vocabulary::all() as $question) {
            if (!in_array($question->question_type, $supportedTypes, true)) {
                throw new RuntimeException("Unsupported question type remains on question {$question->id}.");
            }

            $options = explode(',', (string) $question->options);
            $allText = implode(' ', [$question->question, $question->options, $question->correct_answer]);
            if (preg_match($kanjiPattern, $allText)) {
                throw new RuntimeException("Kanji remains in question {$question->id}.");
            }

            if ($question->question_type === 'translation_tiles') {
                $tilesAndAnswer = implode(' ', $options) . ' ' . $question->correct_answer;
                if (preg_match($kanaPattern, $tilesAndAnswer)) {
                    throw new RuntimeException("Japanese text remains in English translation tiles on question {$question->id}.");
                }
                foreach (preg_split('/\s+/u', trim($question->correct_answer)) as $tile) {
                    if (!in_array($tile, $options, true)) {
                        throw new RuntimeException("Translation answer does not match its tiles on question {$question->id}.");
                    }
                }
            }

            if ($question->question_type === 'image_selection'
                && preg_match('/[^\x00-\x7F]/', (string) $question->question)) {
                throw new RuntimeException("Image question {$question->id} must use an English prompt.");
            }
        }

        $duplicates = Vocabulary::selectRaw('lesson_id, question, question_type, correct_answer, COUNT(*) AS aggregate_count')
            ->groupBy('lesson_id', 'question', 'question_type', 'correct_answer')
            ->having('aggregate_count', '>', 1)
            ->exists();

        if ($duplicates || Vocabulary::count() !== 240) {
            throw new RuntimeException('The question set contains duplicates or does not total exactly 240.');
        }
    }

    private function toHiragana(string $text): string
    {
        if (!preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $text)) {
            return $text;
        }

        $reading = $this->japaneseKanaReadings()[$text] ?? null;

        if (!is_string($reading)
            || preg_match('/[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}]/u', $reading)) {
            throw new RuntimeException('No Kanji-free reading is registered for question content: ' . $text);
        }

        return $reading;
    }

    private function englishSentenceTranslations(): array
    {
        return [
            'A1' => [
                ['Good morning Mr. Tanaka.', 'Nice to meet you. I am Tanaka.', 'I came from Osaka.', 'I look forward to getting to know you.', 'This is my friend Mr. Yamada.'],
                ['What time is it now?', 'I get up at seven every morning.', 'Today is May third.', 'I watch a movie with my friend on Sunday.', 'I study from two to three.'],
                ['I eat breakfast and go to school.', 'I study Japanese at the library.', 'I read a book at home yesterday.', 'I go to bed at eleven every night.', 'I clean my room on weekends.'],
                ['There is a bank in front of the station.', 'I play soccer with my friend in the park.', 'I buy milk and bread at the supermarket.', 'I go to school by bus.', 'Please turn right.'],
            ],
            'A2' => [
                ['I take a shower every morning before breakfast.', 'It rained yesterday so I watched a movie at home.', 'I have to get up early tomorrow so I will go to bed early tonight.', 'I had a meal with a friend after work.', 'I try to swim three times a week.'],
                ['These shoes are a little cheaper than those.', 'Excuse me may I try this on?', 'Two coffees please.', 'Could you recommend a dish that is not spicy?', 'How much is the total?'],
                ['Which platform does the next train leave from?', 'When you get to the station please turn right.', 'I got lost so I asked at the police box.', 'It is better to book the hotel in advance.', 'It looks like it will rain so I will take an umbrella.'],
                ['Have you ever been to Hokkaido?', 'I plan to move next month.', 'If I have time I want to go to the art museum.', 'I tried making Japanese food but it turned out a little salty.', 'I was busy so I could not meet my friend.'],
            ],
            'B1' => [
                ['I think it is better to use public transportation.', 'This restaurant is not only inexpensive but the food is good too.', 'We postponed the presentation because we did not have enough time.', 'We should think about what each of us can do to protect the environment.', 'The reason I agree with his opinion is that his explanation is specific.'],
                ['I traveled abroad alone for the first time after entering university.', 'When I was a child I often stayed at my grandmother’s house.', 'I got lost but someone nearby kindly showed me the way.', 'I left home early so I would not be late for the meeting.', 'The experience of getting through a busy period helps me in my current job.'],
                ['If you are free would you like to go for a meal together sometime?', 'I am sorry I cannot make it that day even though you kindly invited me.', 'Thank you for listening to all my concerns the other day.', 'I will make sure to confirm the meeting time once more.', 'It is important to speak politely while considering the other person’s position.'],
                ['According to the survey people with shorter commutes tend to be more satisfied.', 'The author values not only convenience but also ties to the community.', 'While the number of users has grown securing operating funds is now a challenge.', 'This program began because residents asked for it.', 'Therefore we need a longer-term study to judge the results.'],
            ],
            'B2' => [
                ['Could you please wait here until the department head arrives?', 'I would appreciate it if you could review this by the end of today.', 'I know about it but I do not understand the details of how it happened.', 'I understand that the meeting start time has changed.', 'I will coordinate with the person in charge so we can meet the customer’s request.'],
                ['More companies are automating some tasks because of labor shortages.', 'We need to carefully consider the cost effectiveness to make this proposal happen.', 'As flexible work becomes more common people are also calling for a review of evaluation systems.', 'Unless departments share information the same problems may keep recurring.', 'She used her experience to help launch a new business.'],
                ['The government announced household support measures in response to rising prices.', 'Experts expect short-term effects but challenges remain.', 'The published statistics exceeded last year’s figures but differences between industries are large.', 'To understand the news we also need to understand the systems behind it.', 'Considering this series of developments a cautious response will be needed going forward.'],
                ['Your point is reasonable but we should also consider it from another perspective.', 'Even to meet the deadline we cannot skip quality checks.', 'He opposed the plan because the explanation itself was insufficient.', 'After further discussion the two sides reached a proposal they could both accept.', 'Please share the information promptly as soon as the situation changes.'],
            ],
            'C1' => [
                ['Thank you sincerely for attending despite your busy schedule.', 'If there are any issues with the materials I would be grateful if you could point them out.', 'In light of the system’s purpose we need to review how it is operated.', 'Taking their wishes into account I will provide a formal response.', 'We will decide this matter after consulting the relevant organizations.'],
                ['Technological progress can improve convenience while also creating new ethical challenges.', 'Freedom does not simply mean having no restrictions; it also means accepting responsibility for one’s choices.', 'Dialogue is essential for people from different cultural backgrounds to coexist.', 'We must not overlook the long-term effects on the environment in pursuit of economic growth.', 'This debate ultimately comes down to how efficiency and fairness can both be achieved.'],
                ['The author questions the view that rising figures necessarily indicate success.', 'To summarize the argument participants’ acceptance is essential to the system’s sustainability.', 'The two seemingly contradictory descriptions can be explained by their different time frames.', 'The earlier example helps support the hypothesis presented later.', 'Therefore the author argues that policy should not be judged by short-term results alone.'],
                ['There are concerns but it may not be necessary to withdraw the plan at this stage.', 'His remarks should be taken as a proposal for improvement rather than criticism.', 'Even if he was unaware of the circumstances he is not exempt from the responsibility to check.', 'It may not meet expectations but it is a strong alternative.', 'He chose his words carefully because he wanted to reserve judgment rather than refuse.'],
            ],
            'C2' => [
                ['If we rely solely on precedent we may misread changing realities.', 'Although the proposal seems reasonable at first glance it questions existing assumptions at their root.', 'Her analysis carefully distinguishes between concepts with multiple meanings.', 'We should avoid a hasty conclusion and assess the scope of the evidence.', 'We must also consider side effects when discussing whether a measure is justified.'],
                ['When revising the system we should consider practical constraints as well as its ideals.', 'Public trust cannot be restored without accountability.', 'His proposal gained support precisely because it was grounded in reality.', 'Even a seemingly minor difference cannot be overlooked if it can shape the context.', 'Only after sufficient evidence is presented can the conclusion withstand scrutiny.'],
                ['Because this paper relies on limited historical sources any generalization calls for caution.', 'The author limits the conditions under which the opposing view holds rather than dismissing it.', 'This metaphor suggests institutional rigidity but does not imply a complete rejection.', 'The concession in the latter half shows that the author distinguishes principles from exceptions.', 'Whether the conclusion applies beyond the examples in the text still requires consideration.'],
                ['Though he wanted to save face he diverted attention from the essential issue.', 'That judgment is timely but insufficient as a foundation for the future.', 'Unless we grasp the irony implied between the lines we will misunderstand the speaker’s true intent.', 'Following convention is not always best and can sometimes preserve outdated practices.', 'Although he seemed unwilling to yield he eventually looked for a compromise.'],
            ],
        ];
    }

    /** Five additional, level-appropriate questions for each lesson. */
    private function supplementalQuestions(): array
    {
        $sets = [
            'A1' => [
                [
                    ['これは私の友だちです。', 'This is my friend.', 'That is my teacher.'], ['私は東京に住んでいます。', 'I live in Tokyo.', 'I work in Tokyo.'], ['田中さんは学生です。', 'Mr. Tanaka is a student.', 'Mr. Tanaka is a teacher.'],
                    ['私は日本から来ました。', ['私は', '日本から', '来ました。']], ['「こんにちは」の意味はどれですか？', 'hello', ['goodbye', 'thank you']],
                ],
                [
                    ['今日は六月一日です。', 'Today is June first.', 'Today is July first.'], ['今は午前九時です。', 'It is nine a.m. now.', 'It is nine p.m. now.'], ['りんごを三つ買いました。', 'I bought three apples.', 'I bought two apples.'],
                    ['会議は午後二時に始まります。', ['会議は', '午後二時に', '始まります。']], ['「半」の意味はどれですか？', 'half', ['quarter', 'minute']],
                ],
                [
                    ['毎朝七時に起きます。', 'I get up at seven every morning.', 'I go to bed at seven every morning.'], ['昼ご飯のあとで勉強します。', 'I study after lunch.', 'I study before lunch.'], ['週末は家で本を読みます。', 'I read books at home on weekends.', 'I write books at home on weekends.'],
                    ['妹は音楽を聞いています。', ['妹は', '音楽を', '聞いています。']], ['「洗います」の意味はどれですか？', 'to wash', ['to cook', 'to borrow']],
                ],
                [
                    ['駅の前に銀行があります。', 'There is a bank in front of the station.', 'There is a bank behind the station.'], ['学校まで歩いて十分です。', 'It takes ten minutes to walk to school.', 'It takes ten minutes by train to school.'], ['公園で友だちとテニスをします。', 'I play tennis with a friend in the park.', 'I watch tennis with a friend at home.'],
                    ['図書館で日本語を勉強します。', ['図書館で', '日本語を', '勉強します。']], ['「左」の意味はどれですか？', 'left', ['right', 'straight']],
                ],
            ],
            'A2' => [
                [['雨が降っていたので、バスで帰りました。', 'It was raining so I went home by bus.', 'It was sunny so I walked home.'], ['朝食を食べてから、会社へ行きます。', 'After breakfast I go to work.', 'Before breakfast I leave work.'], ['昨日は疲れていたので、早く寝ました。', 'I was tired yesterday so I went to bed early.', 'I was tired yesterday so I got up early.'], ['駅に着いたら、電話してください。', ['駅に', '着いたら', '電話してください。']], ['「予約する」の意味はどれですか？', 'to make a reservation', ['to cancel a reservation', 'to pay a bill']]],
                [['この店は安くて、おいしいです。', 'This restaurant is inexpensive and delicious.', 'This restaurant is expensive and quiet.'], ['試着してもいいですか。', 'May I try this on?', 'May I return this?'], ['野菜を多めにしてください。', 'Please give me extra vegetables.', 'Please remove the vegetables.'], ['温かいお茶を一つお願いします。', ['温かいお茶を', '一つ', 'お願いします。']], ['「値段」の意味はどれですか？', 'price', ['size', 'receipt']]],
                [['次の角を右に曲がってください。', 'Please turn right at the next corner.', 'Please turn left at the next corner.'], ['空港まで電車で一時間かかります。', 'It takes an hour by train to the airport.', 'It takes an hour by bus from the airport.'], ['切符を買ってから、改札を通ります。', 'After buying a ticket I go through the gate.', 'After leaving the gate I buy a ticket.'], ['ホテルは郵便局の向かいにあります。', ['ホテルは', '郵便局の向かいに', 'あります。']], ['「乗り換える」の意味はどれですか？', 'to transfer', ['to get lost', 'to arrive']]],
                [['来年、京都へ旅行するつもりです。', 'I plan to travel to Kyoto next year.', 'I traveled to Kyoto last year.'], ['雨なら、家で映画を見ましょう。', 'If it rains let us watch a movie at home.', 'When it rains let us go outside.'], ['先週見た映画はとても面白かったです。', 'The movie I saw last week was very interesting.', 'The movie I will see next week is interesting.'], ['時間があれば、博物館にも行きたいです。', ['時間が', 'あれば', '博物館にも行きたいです。']], ['「経験」の意味はどれですか？', 'experience', ['plan', 'invitation']]],
            ],
            'B1' => [
                [['通勤時間が短くなれば、生活に余裕ができると思います。', 'I think shorter commutes would give people more time in their lives.', 'I think longer commutes would make life easier.'], ['便利さだけでなく、環境への影響も考えるべきです。', 'We should consider environmental effects as well as convenience.', 'We should consider convenience instead of the environment.'], ['費用が高い一方で、長く使えるという利点があります。', 'Although it costs more it has the advantage of lasting longer.', 'It costs less but cannot be used for long.'], ['私はこの案に賛成ですが、条件があります。', ['私は', 'この案に', '賛成ですが', '条件があります。']], ['「影響」の意味はどれですか？', 'effect', ['schedule', 'custom']]],
                [['初めて一人で旅行したとき、道に迷いました。', 'When I first traveled alone I got lost.', 'When I first traveled with friends I found the way.'], ['駅員に聞いたおかげで、無事にホテルへ着きました。', 'Thanks to asking a station attendant I reached the hotel safely.', 'Because I asked at the hotel I missed the train.'], ['子どものころ、祖父からよく昔話を聞きました。', 'When I was a child my grandfather often told me old stories.', 'When I was a child I often told my grandfather stories.'], ['その出来事をきっかけに、写真を撮り始めました。', ['その出来事を', 'きっかけに', '写真を撮り始めました。']], ['「思い出」の意味はどれですか？', 'memory', ['appointment', 'mistake']]],
                [['都合が悪ければ、別の日に変更できます。', 'If the date does not work for you we can change it.', 'If the date works for you we must cancel it.'], ['せっかく誘ってくれたのに、行けなくて残念です。', 'I am sorry I cannot go even though you kindly invited me.', 'I am glad I could invite you to come.'], ['相手の話を最後まで聞いてから返事をしました。', 'I replied after listening to the other person to the end.', 'I interrupted before hearing the other person.'], ['無理のない範囲で手伝ってもらえませんか。', ['無理のない範囲で', '手伝って', 'もらえませんか。']], ['「遠慮する」の意味はどれですか？', 'to hold back politely', ['to insist strongly', 'to arrive early']]],
                [['案内によると、展示室は午後五時に閉まります。', 'According to the notice the gallery closes at five p.m.', 'According to the notice the gallery opens at five a.m.'], ['本文では、参加者が増えた理由を二つ挙げています。', 'The passage gives two reasons the number of participants increased.', 'The passage gives two plans to reduce participants.'], ['この調査だけでは、原因を特定できません。', 'This survey alone cannot identify the cause.', 'This survey proves the cause beyond doubt.'], ['筆者は最後に、地域の協力が必要だと述べています。', ['筆者は最後に', '地域の協力が', '必要だと述べています。']], ['「傾向」の意味はどれですか？', 'trend', ['exception', 'permission']]],
            ],
            'B2' => [
                [['恐れ入りますが、資料を本日中にご確認いただけますでしょうか。', 'Could you please review the materials by the end of today?', 'Could you please send the materials next month?'], ['社内では率直に話し、顧客には丁寧に説明します。', 'We speak frankly internally and explain things politely to clients.', 'We speak politely internally and frankly to clients.'], ['ご指摘を踏まえ、案内文を修正いたしました。', 'We revised the notice in light of your feedback.', 'We ignored your feedback and withdrew the notice.'], ['確認が取れ次第、担当者からご連絡いたします。', ['確認が', '取れ次第', '担当者から', 'ご連絡いたします。']], ['「差し支えない」の意味はどれですか？', 'to be acceptable', ['to be compulsory', 'to be mistaken']]],
                [['人手不足を補うため、業務の一部を自動化しました。', 'We automated part of the work to address the labor shortage.', 'We automated the work to increase the labor shortage.'], ['制度を定着させるには、現場の意見を聞く必要があります。', 'To establish the system we need to hear from people on the ground.', 'To remove the system we need to avoid employee feedback.'], ['短期的な成果だけで判断するのは早計です。', 'It is premature to judge by short-term results alone.', 'It is wise to judge only by short-term results.'], ['地域社会との連携を強める方針が示されました。', ['地域社会との', '連携を', '強める方針が', '示されました。']], ['「担い手」の意味はどれですか？', 'person responsible', ['financial cost', 'legal restriction']]],
                [['統計上は改善しているものの、地域差は依然として大きいです。', 'Although the statistics show improvement regional differences remain large.', 'The statistics show decline and no regional differences.'], ['専門家は、数字の背景にある要因も見るべきだと述べました。', 'The expert said we should also examine the factors behind the figures.', 'The expert said figures should be considered without context.'], ['発表された数値だけでは、政策の効果を測れません。', 'The published figures alone cannot measure the policy’s effect.', 'The published figures fully explain the policy.'], ['報道によれば、計画の実施時期はまだ決まっていません。', ['報道によれば', '計画の実施時期は', 'まだ決まっていません。']], ['「見通し」の意味はどれですか？', 'outlook', ['quotation', 'complaint']]],
                [['納期を守ることは重要ですが、品質の確認も欠かせません。', 'Meeting the deadline matters but quality checks are also essential.', 'Meeting the deadline means quality checks can be skipped.'], ['ご懸念は理解しておりますが、別の案も検討させてください。', 'I understand your concerns but please allow us to consider another option.', 'I reject your concerns and will not consider alternatives.'], ['条件を明確にしたうえで、契約を進めるべきです。', 'We should proceed with the contract after clarifying the terms.', 'We should sign before clarifying the terms.'], ['双方が納得できる解決策を探る必要があります。', ['双方が', '納得できる', '解決策を', '探る必要があります。']], ['「折り合い」の意味はどれですか？', 'compromise', ['warning', 'deadline']]],
            ],
            'C1' => [
                [['ご多用の折恐縮ですが、別添資料をご査収いただけますと幸甚に存じます。', 'I would be grateful if you could review the attached materials despite your busy schedule.', 'Please discard the attached materials when convenient.'], ['先方のご意向を踏まえ、回答は後日改めて差し上げます。', 'In light of their wishes we will respond again at a later date.', 'We will answer immediately without considering their wishes.'], ['本件につきましては、関係各所と協議のうえ決定いたします。', 'We will decide this matter after consulting the relevant parties.', 'We have decided this matter without consultation.'], ['ご不明な点がございましたら、何なりとお申し付けください。', ['ご不明な点が', 'ございましたら', '何なりと', 'お申し付けください。']], ['「ご査収」の意味はどれですか？', 'to review and receive', ['to postpone indefinitely', 'to pay in advance']]],
                [['効率性を追求するあまり、公平性が損なわれてはならない。', 'In pursuing efficiency fairness must not be compromised.', 'Efficiency should replace every concern for fairness.'], ['個人の選択は、社会的な条件と無関係ではありません。', 'Individual choices are not unrelated to social conditions.', 'Individual choices are entirely independent of society.'], ['この議論は、責任を誰が負うべきかという問いに帰着する。', 'This debate ultimately comes down to who should bear responsibility.', 'This debate avoids the question of responsibility.'], ['異なる価値観の共存には、対話を重ねる姿勢が欠かせません。', ['異なる価値観の', '共存には', '対話を重ねる姿勢が', '欠かせません。']], ['「帰着する」の意味はどれですか？', 'to ultimately come down to', ['to be temporarily delayed', 'to be physically moved']]],
                [['筆者は一見中立的な表現にも、特定の前提が含まれると指摘する。', 'The author points out that even seemingly neutral wording contains certain assumptions.', 'The author argues that neutral wording contains no assumptions.'], ['複数の資料を照合した結果、当初の仮説は修正された。', 'After cross-checking several sources the initial hypothesis was revised.', 'The initial hypothesis was confirmed without checking sources.'], ['この事例だけから全体の傾向を推し量ることはできない。', 'The overall trend cannot be inferred from this case alone.', 'This single case proves the overall trend.'], ['結論に至る過程を検証することが重要です。', ['結論に至る', '過程を', '検証することが', '重要です。']], ['「推量する」の意味はどれですか？', 'to infer', ['to prohibit', 'to reproduce']]],
                [['懸念を払拭するには、根拠を示した説明が求められます。', 'An evidence-based explanation is needed to dispel the concern.', 'An unsupported claim is needed to strengthen the concern.'], ['彼の発言は断定を避けつつ、慎重な再検討を促している。', 'His remarks encourage careful reconsideration while avoiding a definitive claim.', 'His remarks definitively reject any reconsideration.'], ['表現の違いが、受け手の解釈を左右することがあります。', 'Differences in wording can affect how an audience interprets a message.', 'Wording never affects an audience’s interpretation.'], ['必ずしも反対というわけではありません。', ['必ずしも', '反対という', 'わけでは', 'ありません。']], ['「含意」の意味はどれですか？', 'implication', ['quotation', 'schedule']]],
            ],
            'C2' => [
                [['議論の射程を見誤れば、妥当な批判も的外れになりかねない。', 'If we misjudge the scope of the argument even valid criticism may miss the point.', 'If we limit the argument every criticism becomes valid.'], ['彼の論考は、通説を退けるのではなく、その適用範囲を問い直している。', 'His essay questions the scope of the prevailing view rather than rejecting it.', 'His essay accepts the prevailing view without qualification.'], ['概念の定義を曖昧にしたままでは、比較そのものが成立しない。', 'Without clear definitions the comparison itself cannot hold.', 'Clear definitions make comparison unnecessary.'], ['議論の前提を吟味して初めて、結論の妥当性を評価できます。', ['議論の前提を', '吟味して初めて', '結論の妥当性を', '評価できます。']], ['「射程」の意味はどれですか？', 'scope', ['exception', 'obligation']]],
                [['規範を一律に適用すれば、個別の事情を取りこぼす恐れがある。', 'Applying a norm uniformly risks overlooking individual circumstances.', 'Applying a norm uniformly guarantees every circumstance is considered.'], ['文脈を離れて形式だけを論じても、解釈は深まりません。', 'Discussing form alone outside its context will not deepen interpretation.', 'Context is irrelevant when interpreting form.'], ['反証可能性を欠く主張は、検証の対象になりにくい。', 'A claim that lacks falsifiability is difficult to subject to verification.', 'An unfalsifiable claim is easy to verify.'], ['例外を認めることは、原則を放棄することと同義ではありません。', ['例外を認めることは', '原則を放棄することと', '同義ではありません。']], ['「恣意的」の意味はどれですか？', 'arbitrary', ['inevitable', 'impartial']]],
                [['資料の偏りを考慮せずに一般化するのは慎むべきだ。', 'We should refrain from generalizing without considering bias in the sources.', 'We should generalize before reviewing the sources.'], ['反対説の弱点を認めても、主張の核心は損なわれない。', 'Acknowledging the weakness of the opposing view does not undermine the core claim.', 'Acknowledging an opposing view means abandoning the claim.'], ['記述と評価を区別することが、論旨を読み解く手がかりとなる。', 'Distinguishing description from evaluation helps us understand the argument.', 'Description and evaluation should always be treated as identical.'], ['論拠の強さは、結論の確かさと切り離して検討すべきです。', ['論拠の強さは', '結論の確かさと', '切り離して', '検討すべきです。']], ['「反証」の意味はどれですか？', 'disproof', ['summary', 'analogy']]],
                [['彼は面目を保つため、異論を承知で沈黙を選んだ。', 'He chose silence despite knowing of the objection in order to save face.', 'He voiced his objection to protect someone else’s reputation.'], ['慣例に倣うことが常に最善とは限らず、時には見直しも要る。', 'Following convention is not always best; sometimes it needs reconsideration.', 'Conventions should never be reconsidered.'], ['婉曲な表現の背後にある含みを読み取る必要がある。', 'We need to read the implication behind the indirect wording.', 'We need to ignore the implication and take every phrase literally.'], ['その判断は、先例を踏まえつつも事情に即したものです。', ['その判断は', '先例を踏まえつつも', '事情に即した', 'ものです。']], ['「面目を保つ」の意味はどれですか？', 'to save face', ['to change the subject', 'to make an excuse']]],
            ],
        ];

        $result = [];
        foreach ($sets as $level => $lessons) {
            foreach ($lessons as $index => $items) {
                $result[$level][$index + 1] = [
                    $this->translation(...$items[0]),
                    $this->translation(...$items[1]),
                    $this->translation(...$items[2]),
                    $this->phrase(...$items[3]),
                    $this->vocabulary($items[4][0], $items[4][1], $items[4][2]),
                ];
            }
        }

        return $result;
    }

    private function categoryIdFor(Level $level): int
    {
        $category = DB::table('categories')
            ->where('level_id', $level->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        $now = now();

        return (int) DB::table('categories')->insertGetId([
            'level_id' => $level->id,
            'title' => $level->level . ' Learning Content',
            'description' => 'Japanese learning lessons for ' . $level->level . '.',
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function content(): array
    {
        return [
            'A1' => [
                [
                    'title' => 'Greetings & Self Introduction',
                    'description' => 'Basic greetings, names, origins, and polite introductions.',
                    'sort_order' => 1,
                    'questions' => [
                        '田中|さん|おはよう|ございます',
                        'はじめまして|わたし|は|田中|です',
                        'わたし|は|大阪|から|来ました',
                        'どうぞ|よろしく|お願いします',
                        'こちら|は|友だち|の|山田|さん|です',
                    ],
                ],
                [
                    'title' => 'Numbers, Time & Dates',
                    'description' => 'Simple numbers, clock times, weekdays, and dates.',
                    'sort_order' => 2,
                    'questions' => [
                        '今|何時|ですか',
                        '毎朝|七時|に|起きます',
                        '今日は|五月|三日|です',
                        '日曜日|に|友だち|と|映画|を|見ます',
                        '二時|から|三時|まで|勉強します',
                    ],
                ],
                [
                    'title' => 'Daily Life',
                    'description' => 'Simple routines, meals, study, and daily activities.',
                    'sort_order' => 3,
                    'questions' => [
                        '朝ごはん|を|食べて|学校|へ|行きます',
                        '図書館|で|日本語|を|勉強します',
                        '昨日|は|家|で|本|を|読みました',
                        '毎晩|十一時|に|寝ます',
                        '週末|に|部屋|を|そうじします',
                    ],
                ],
                [
                    'title' => 'Places & Activities',
                    'description' => 'Common places, transport, directions, and activities.',
                    'sort_order' => 4,
                    'questions' => [
                        '駅|の|前|に|銀行|が|あります',
                        '公園|で|友だち|と|サッカー|を|します',
                        'スーパー|で|牛乳|と|パン|を|買います',
                        '学校|まで|バス|で|行きます',
                        '右|に|曲がって|ください',
                    ],
                ],
            ],
            'A2' => [
                [
                    'title' => 'Daily Routines',
                    'description' => 'Routines with past events, reasons, and habits.',
                    'sort_order' => 1,
                    'questions' => [
                        '毎朝|シャワー|を|浴びてから|朝ごはん|を|食べます',
                        '昨日|は|雨|が|降っていたので|家|で|映画|を|見ました',
                        '明日|は|早く|起きなければ|ならないので|今夜|は|早く|寝ます',
                        '仕事|が|終わったあとで|友だち|と|食事|を|しました',
                        '週|に|三回|泳ぐように|しています',
                    ],
                ],
                [
                    'title' => 'Shopping & Food',
                    'description' => 'Shopping requests, prices, preferences, and restaurant expressions.',
                    'sort_order' => 2,
                    'questions' => [
                        'この|靴|は|あの|靴|より|少し|安いです',
                        'すみません|試着しても|いいですか',
                        'コーヒー|を|二つ|お願いします',
                        '辛くない|料理|を|おすすめして|いただけますか',
                        '合計|で|いくら|に|なりますか',
                    ],
                ],
                [
                    'title' => 'Travel & Directions',
                    'description' => 'Travel plans, directions, reservations, and useful requests.',
                    'sort_order' => 3,
                    'questions' => [
                        '次|の|電車|は|何番線|から|出ますか',
                        '駅|に|着いたら|右|へ|曲がって|ください',
                        '道|に|迷ったので|交番|で|聞きました',
                        'ホテル|を|予約して|おいたほうが|いいです',
                        '雨|が|降りそうなので|傘|を|持って|行きます',
                    ],
                ],
                [
                    'title' => 'Experiences & Plans',
                    'description' => 'Past experiences, intentions, conditions, and plans.',
                    'sort_order' => 4,
                    'questions' => [
                        '北海道|へ|行ったこと|が|あります',
                        '来月|引っ越す|つもり|です',
                        '時間|が|あれば|美術館|に|行きたいです',
                        '日本料理|を|作ってみたけれど|少し|塩辛く|なりました',
                        '忙しかったので|友だち|に|会えませんでした',
                    ],
                ],
            ],
            'B1' => [
                [
                    'title' => 'Opinions & Reasons',
                    'description' => 'Expressing opinions, supporting reasons, and considering alternatives.',
                    'sort_order' => 1,
                    'questions' => [
                        '私は|公共交通機関|を|利用したほうが|よい|と|思います',
                        'この|店|は|安いだけでなく|料理|も|おいしいです',
                        '時間|が|足りなかったため|発表|を|延期しました',
                        '環境|を|守るために|一人一人|が|できること|を|考えるべきです',
                        '彼|の|意見|に|賛成する|理由|は|説明|が|具体的|だからです',
                    ],
                ],
                [
                    'title' => 'Experiences & Stories',
                    'description' => 'Narrating experiences and explaining how they influence the present.',
                    'sort_order' => 2,
                    'questions' => [
                        '大学|に|入ってから|初めて|一人|で|海外旅行|を|しました',
                        '子ども|の|ころ|よく|祖母|の|家|に|泊まりに|行ったものです',
                        '道|に|迷ってしまった|が|近く|の|人|が|親切|に|案内してくれました',
                        '会議|に|遅れないように|早め|に|家|を|出ました',
                        '忙しい|時期|を|乗り越えた|経験|が|今|の|仕事|に|役立っています',
                    ],
                ],
                [
                    'title' => 'Social Conversations',
                    'description' => 'Natural invitations, polite refusals, and considerate conversation.',
                    'sort_order' => 3,
                    'questions' => [
                        'もし|よかったら|今度|一緒|に|食事|に|行きませんか',
                        'せっかく|誘っていただいた|のに|その日|は|都合|が|つきません',
                        '先日|は|いろいろ|相談|に|乗ってくれて|ありがとう',
                        '念|の|ため|集合時間|を|もう一度|確認しておきます',
                        '相手|の|立場|を|考えながら|丁寧|に|伝えること|が|大切です',
                    ],
                ],
                [
                    'title' => 'Everyday Reading',
                    'description' => 'Short informational passages, evidence, and conclusions.',
                    'sort_order' => 4,
                    'questions' => [
                        '調査|によると|通勤時間|が|短い|人|ほど|満足度|が|高い|そうです',
                        '筆者|は|便利さ|だけでなく|地域|と|の|つながり|も|重視しています',
                        '利用者|が|増えた|一方|で|運営費|の|確保|が|課題|と|なっています',
                        'この|制度|が|始まった|の|は|住民|の|要望|が|きっかけでした',
                        'したがって|結果|を|判断する|に|は|さらに|長期的|な|調査|が|必要です',
                    ],
                ],
            ],
            'B2' => [
                [
                    'title' => 'Formal & Informal Japanese',
                    'description' => 'Honorific, business, and nuanced formal/informal expressions.',
                    'sort_order' => 1,
                    'questions' => [
                        '部長|が|いらっしゃる|まで|こちら|で|お待ちいただけますか',
                        '本日中|に|ご確認いただけますと|幸いです',
                        'それ|は|知っている|ものの|詳しい|経緯|まで|は|把握していません',
                        '会議|の|開始時刻|が|変更|に|なった|と|の|ことです',
                        'お客様|の|ご要望|に|沿えるよう|担当者|と|調整いたします',
                    ],
                ],
                [
                    'title' => 'Work & Society',
                    'description' => 'Workplace proposals, social change, and cause-and-effect discussion.',
                    'sort_order' => 2,
                    'questions' => [
                        '人手不足|に|伴い|業務|の|一部|を|自動化する|企業|が|増えています',
                        'この|提案|を|実現する|に|は|費用対効果|を|慎重|に|検討する|必要|が|あります',
                        '柔軟|な|働き方|が|普及する|一方|で|評価制度|の|見直し|も|求められています',
                        '部署間|で|情報|を|共有しなければ|同じ|問題|が|繰り返されかねません',
                        '彼女|は|経験|を|生かして|新規事業|の|立ち上げ|に|貢献しました',
                    ],
                ],
                [
                    'title' => 'News & Information',
                    'description' => 'Interpreting reports, statistics, expert views, and policy responses.',
                    'sort_order' => 3,
                    'questions' => [
                        '政府|は|物価|の|上昇|を|受け|家計支援策|を|発表しました',
                        '専門家|に|よれば|短期的|な|効果|は|期待できる|ものの|課題|も|残ります',
                        '発表された|統計|は|前年|を|上回った|が|業界差|が|大きい|と|指摘されています',
                        '報道内容|を|理解する|に|は|背景|に|ある|制度|も|把握する|必要|が|あります',
                        '一連|の|動き|を|踏まえる|と|今後|は|慎重|な|対応|が|求められます',
                    ],
                ],
                [
                    'title' => 'Complex Conversations',
                    'description' => 'Qualifying opinions, negotiating, and communicating conditional decisions.',
                    'sort_order' => 4,
                    'questions' => [
                        'ご指摘|の|点|は|もっとも|です|が|別|の|観点|から|も|検討すべきです',
                        '納期|を|守る|ため|と|は|いえ|品質確認|を|省く|わけ|に|は|いきません',
                        '彼|が|反対した|の|は|計画|そのもの|より|説明|が|不足していた|から|です',
                        '話し合い|を|重ねた|結果|双方|が|受け入れられる|案|に|まとまりました',
                        '状況|が|変わり次第|速やか|に|関係者|へ|共有してください',
                    ],
                ],
            ],
            'C1' => [
                [
                    'title' => 'Advanced Formal Japanese',
                    'description' => 'Formal correspondence, honorific language, and institutional phrasing.',
                    'sort_order' => 1,
                    'questions' => [
                        'ご多忙|の|ところ|ご出席賜り|誠|に|ありがとうございます',
                        '資料|に|不備|が|ございましたら|ご指摘いただけます|と|幸甚|に|存じます',
                        '制度|の|趣旨|に|鑑み|運用方法|を|改めて|検討する|必要|が|ございます',
                        '先方|の|意向|を|踏まえた|うえ|で|正式|な|回答|を|差し上げます',
                        '本件|に|つきまして|は|関係各所|と|協議|の|うえ|決定いたします',
                    ],
                ],
                [
                    'title' => 'Abstract Topics',
                    'description' => 'Discussing ethics, responsibility, coexistence, and competing values.',
                    'sort_order' => 2,
                    'questions' => [
                        '技術|の|進歩|は|利便性|を|高める|一方|新た|な|倫理的課題|を|生み出しかねません',
                        '自由|と|は|単|に|制約|が|ない|こと|で|は|なく|選択|に|伴う|責任|を|引き受ける|こと|です',
                        '文化的背景|を|異にする|人々|が|共生する|に|は|対話|の|場|が|欠かせません',
                        '経済成長|を|優先する|あまり|環境|へ|の|長期的影響|を|軽視して|は|なりません',
                        'この|議論|は|効率性|と|公平性|を|いかに|両立させる|か|という|問い|に|帰着します',
                    ],
                ],
                [
                    'title' => 'Advanced Reading',
                    'description' => 'Analyzing claims, evidence, inference, and the structure of an argument.',
                    'sort_order' => 3,
                    'questions' => [
                        '著者|は|数値|の|上昇|を|成果|と|みなす|見方|に|疑問|を|呈しています',
                        '本文|の|主張|を|要約する|なら|制度|の|持続可能性|に|は|参加者|の|納得|が|不可欠です',
                        '一見|矛盾する|二つ|の|記述|は|対象|と|する|時間軸|の|違い|から|説明できます',
                        '前段|の|事例|は|後|に|示される|仮説|を|補強する|役割|を|果たしています',
                        'したがって|筆者|は|短期|の|成果|だけ|で|政策|を|評価すべきで|は|ない|と|論じています',
                    ],
                ],
                [
                    'title' => 'Nuanced Expressions',
                    'description' => 'Interpreting qualification, implication, concession, and indirect intent.',
                    'sort_order' => 4,
                    'questions' => [
                        '懸念|が|ない|わけ|で|は|ありません|が|現時点|で|計画|を|撤回する|必要|は|ない|でしょう',
                        '彼|の|発言|は|批判|と|いうより|改善|を|促す|提案|と|受け取るべきです',
                        '事情|を|知らなかった|に|せよ|確認|を|怠った|責任|は|免れません',
                        '期待|に|沿う|と|は|限りません|が|代替案|と|して|は|有力です',
                        '言葉|を|濁した|の|は|断る|意図|というより|判断|を|保留したかった|ため|です',
                    ],
                ],
            ],
            'C2' => [
                [
                    'title' => 'Advanced Vocabulary',
                    'description' => 'Precise academic and professional vocabulary in context.',
                    'sort_order' => 1,
                    'questions' => [
                        '前例踏襲|に|終始すれば|変化する|実情|を|見誤りかねません',
                        'その|提言|は|一見|穏当|ながら|既存|の|前提|を|根底|から|問い直しています',
                        '彼女|の|論考|は|多義的|な|概念|を|丁寧|に|峻別しています',
                        '拙速|な|結論|を|避け|証拠|の|射程|を|見極める|べきです',
                        '措置|の|妥当性|を|論じる|に|は|副作用|も|勘案しなければ|なりません',
                    ],
                ],
                [
                    'title' => 'Complex Grammar',
                    'description' => 'Dense clauses, logical qualifications, and formal written constructions.',
                    'sort_order' => 2,
                    'questions' => [
                        '制度|を|改める|に|あたって|は|理念|のみならず|実務上|の|制約|に|も|目|を|向ける|べきです',
                        '説明責任|を|果たさず|し|て|社会的|な|信頼|を|回復する|こと|は|できません',
                        '彼|の|提案|は|現実|に|即している|から|こそ|関係者|の|支持|を|得た|の|です',
                        '一見|些細|な|差異|で|あって|も|文脈|を|左右しうる|以上|看過できません',
                        '論拠|が|十分|に|示されて|初めて|結論|の|妥当性|が|検証|に|耐えます',
                    ],
                ],
                [
                    'title' => 'Advanced Reading & Interpretation',
                    'description' => 'Reading argument structure, limitations, concession, and implied scope.',
                    'sort_order' => 3,
                    'questions' => [
                        '本稿|が|依拠する|史料|は|限られて|おり|一般化|に|は|慎重|を|要します',
                        '著者|は|反対説|を|退ける|の|で|は|なく|その|成立条件|を|限定しています',
                        'この|比喩|は|制度|の|硬直性|を|示唆する|ものの|全面的|な|否定|を|意味しません',
                        '後半|の|譲歩表現|から|読み取れる|の|は|筆者|が|原則|と|例外|を|区別している|点|です',
                        '結論|は|本文|の|事例|を|超えて|適用できる|か|なお|検討|の|余地|が|あります',
                    ],
                ],
                [
                    'title' => 'Nuance, Idioms & Formal Context',
                    'description' => 'Idiomatic implication, subtle disagreement, and precise formal language.',
                    'sort_order' => 4,
                    'questions' => [
                        '彼|は|面目|を|保つ|ため|と|は|いえ|本質的|な|論点|から|目|を|そらした',
                        'その|判断|は|時宜|を|得ている|反面|将来|へ|の|布石|と|して|は|不十分です',
                        '言外|に|含まれた|皮肉|を|汲み取れなければ|発言|の|真意|を|取り違えます',
                        '慣例|に|倣う|こと|が|常|に|最善|と|は|限らず|時|に|は|旧弊|を|温存します',
                        '彼|は|一歩|も|譲らない|よう|に|見えて|最終的|に|は|妥協点|を|探りました',
                    ],
                ],
            ],
        ];
    }


    private function japaneseKanaReadings(): array
    {
        static $readings = null;

        if ($readings === null) {
            $compressed = implode('', [
            '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'
            ]);
            $json = gzdecode(base64_decode($compressed, true));

            if ($json === false) {
                throw new RuntimeException('Could not load the audited Japanese reading map.');
            }

            $readings = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return $readings;
    }

}
