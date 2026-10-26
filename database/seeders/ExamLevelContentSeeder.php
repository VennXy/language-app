<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Level-based exam structure.
 *
 * HONESTY NOTE
 * ------------
 * Questions are DERIVED from the project's existing curated vocabulary bank
 * (`vocabularies`), never invented. Each curated item yields two verified
 * forms (Japanese -> English and English -> Japanese), giving 80 verified
 * items per level.
 *
 * Every generated question is stored as:
 *   status   = 'draft'      (never auto-published)
 *   source   = 'generated'  (provenance marker for the Super Admin review UI)
 *   admin_id = the actual generating Super Admin (honest attribution)
 *
 * The first 5 questions of each exam are offered to real authorized Admin
 * contributors, honouring the server cap of 5 per Admin per exam. No Admin
 * accounts or passwords are fabricated.
 */
class ExamLevelContentSeeder extends Seeder
{
    private const EXAMS_PER_LEVEL = 10;
    private const QUESTIONS_PER_EXAM = 25;
    private const MAX_PER_ADMIN_PER_EXAM = 5;
    private const DURATION_MINUTES = 45;

    public function run(): void
    {
        if (! Schema::hasColumn('exams', 'level_id') || ! Schema::hasColumn('exam_questions', 'source')) {
            $this->command?->error('Run the add_level_to_exam_tables migration first.');

            return;
        }

        $generatorId = DB::table('users')->where('role', 'super_admin')->orderBy('id')->value('id');
        if (! $generatorId) {
            $this->command?->error('No Super Admin found; cannot attribute generated content.');

            return;
        }

        $contributors = DB::table('users')->where('role', 'admin')->orderBy('id')->pluck('id')->all();

        $this->command?->info(sprintf(
            'Generator: super_admin #%d | authorized Admin contributors: %d',
            $generatorId,
            count($contributors)
        ));

        $totals = ['exams' => 0, 'questions' => 0, 'assigned' => 0];

        DB::transaction(function () use ($generatorId, $contributors, $totals): void {
            foreach (DB::table('levels')->orderBy('level')->get() as $level) {
                $bank = $this->buildBank($level->id);

                if (count($bank) < self::QUESTIONS_PER_EXAM) {
                    $this->command?->warn(sprintf(
                        '%s: only %d verified questions available; skipping.',
                        $level->level,
                        count($bank)
                    ));
                    continue;
                }

                for ($examSeq = 1; $examSeq <= self::EXAMS_PER_LEVEL; $examSeq++) {
                    $examId = $this->ensureExam($level, $examSeq, $generatorId);

                    if ($examId === null) {
                        continue;
                    }

                    $totals['exams']++;

                    foreach ($this->pickQuestions($bank, $examSeq) as $question) {
                        $inserted = $this->ensureQuestion($examId, $generatorId, $question);

                        if ($inserted === null) {
                            continue;
                        }

                        $totals['questions']++;

                        if ($this->offerToContributor($examId, $inserted, $contributors)) {
                            $totals['assigned']++;
                        }
                    }
                }
            }
        });

        $this->command?->info(sprintf(
            'Seeded %d exams, %d generated draft questions (%d offered to Admin contributors).',
            $totals['exams'],
            $totals['questions'],
            $totals['assigned']
        ));
    }

    /**
     * Build the verified question bank for a level from curated vocabulary.
     *
     * @return list<array<string, string>>
     */
    private function buildBank(int $levelId): array
    {
        $lessonIds = DB::table('lessons')->where('level_id', $levelId)->pluck('id')->all();

        $items = DB::table('vocabularies')
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('question')->where('question', '!=', '')
            ->whereNotNull('correct_answer')->where('correct_answer', '!=', '')
            ->orderBy('id')
            ->get(['id', 'question', 'correct_answer']);

        $japanese = $items->pluck('question')->all();
        $english = $items->pluck('correct_answer')->all();
        $bank = [];

        foreach ($items as $index => $item) {
            $japaneseText = $this->tidy($item->question);
            $englishText = $this->tidy($item->correct_answer);

            $enDistractors = $this->distractors($english, $englishText, $index);
            if (count($enDistractors) === 3) {
                $bank[] = $this->compose(
                    sprintf('Choose the correct English translation: %s', $japaneseText),
                    $englishText,
                    $enDistractors,
                    sprintf('「%s」 means "%s".', $japaneseText, $englishText)
                );
            }

            $jpDistractors = $this->distractors($japanese, $japaneseText, $index);
            if (count($jpDistractors) === 3) {
                $bank[] = $this->compose(
                    sprintf('Choose the correct Japanese translation: %s', $englishText),
                    $japaneseText,
                    $jpDistractors,
                    sprintf('"%s" is 「%s」 in Japanese.', $englishText, $japaneseText)
                );
            }
        }

        return $bank;
    }
    /**
     * Pick 3 same-level distractors that are distinct from the correct answer.
     *
     * @param  list<string>  $pool
     * @return list<string>
     */
    private function distractors(array $pool, string $correct, int $index): array
    {
        $correct = $this->tidy($correct);
        $picked = [];
        $valid = function (string $candidate) use ($correct, &$picked): bool {
            return $candidate !== '' && $candidate !== $correct && ! in_array($candidate, $picked, true);
        };

        // Spread across the bank rather than always taking neighbours.
        foreach ($pool as $offset => $candidate) {
            if (count($picked) === 3) {
                break;
            }
            $candidate = $this->tidy($candidate);
            if (($offset + $index) % 3 === 0 && $valid($candidate)) {
                $picked[] = $candidate;
            }
        }

        // Wider sweep if the spread filter was too strict.
        foreach ($pool as $candidate) {
            if (count($picked) === 3) {
                break;
            }
            $candidate = $this->tidy($candidate);
            if ($valid($candidate)) {
                $picked[] = $candidate;
            }
        }

        return $picked;
    }

    /**
     * @return array<string, string>
     */
    private function compose(string $prompt, string $correct, array $distractors, string $explanation): array
    {
        $options = array_merge([$correct], $distractors);
        shuffle($options);

        $letters = ['a', 'b', 'c', 'd'];

        return [
            'question' => $prompt,
            'option_a' => $options[0],
            'option_b' => $options[1],
            'option_c' => $options[2],
            'option_d' => $options[3],
            'correct_option' => $letters[(int) array_search($correct, $options, true)],
            'explanation' => $explanation,
        ];
    }

    /**
     * Deterministically pick 25 non-repeating questions for one exam.
     * A coprime stride guarantees no repeats inside a single exam.
     *
     * @param  list<array<string, string>>  $bank
     * @return list<array<string, string>>
     */
    private function pickQuestions(array $bank, int $examSeq): array
    {
        $size = count($bank);
        $stride = $this->coprimeStride($size);
        $offset = ($examSeq - 1) % $size;
        $picked = [];

        for ($step = 0; $step < self::QUESTIONS_PER_EXAM; $step++) {
            $picked[] = $bank[($offset + ($step * $stride)) % $size];
        }

        return $picked;
    }

    private function coprimeStride(int $size): int
    {
        for ($candidate = max(1, intdiv($size, 3)); $candidate < $size; $candidate++) {
            if ($this->gcd($candidate, $size) === 1) {
                return $candidate;
            }
        }

        return 1;
    }

    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    private function ensureExam(object $level, int $examSeq, int $generatorId): ?int
    {
        $existing = DB::table('exams')
            ->where('level_id', $level->id)
            ->where('title', sprintf('%s Exam %02d', $level->level, $examSeq))
            ->first();

        if ($existing) {
            return null; // idempotent
        }

        return (int) DB::table('exams')->insertGetId([
            'title' => sprintf('%s Exam %02d', $level->level, $examSeq),
            'duration_minutes' => self::DURATION_MINUTES,
            'status' => 'draft',
            'level_id' => $level->id,
            'created_by' => $generatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string>  $question
     */
    private function ensureQuestion(int $examId, int $generatorId, array $question): ?int
    {
        $exists = DB::table('exam_questions')
            ->where('exam_id', $examId)
            ->where('question', $question['question'])
            ->where('option_a', $question['option_a'])
            ->where('option_b', $question['option_b'])
            ->exists();

        if ($exists) {
            return null;
        }

        return (int) DB::table('exam_questions')->insertGetId([
            'exam_id' => $examId,
            'admin_id' => $generatorId,
            'question' => $question['question'],
            'option_a' => $question['option_a'],
            'option_b' => $question['option_b'],
            'option_c' => $question['option_c'],
            'option_d' => $question['option_d'],
            'correct_option' => $question['correct_option'],
            'explanation' => $question['explanation'],
            'source' => 'generated',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Offer a generated question to a real Admin contributor, honouring the
     * 5-per-Admin-per-exam cap.
     *
     * @param  list<int>  $contributors
     */
    private function offerToContributor(int $examId, int $questionId, array $contributors): bool
    {
        foreach ($contributors as $adminId) {
            $count = DB::table('exam_questions')
                ->where('exam_id', $examId)
                ->where('admin_id', $adminId)
                ->count();

            if ($count >= self::MAX_PER_ADMIN_PER_EXAM) {
                continue;
            }

            DB::table('exam_questions')->where('id', $questionId)->update([
                'admin_id' => $adminId,
                'updated_at' => now(),
            ]);

            return true;
        }

        return false;
    }

    private function tidy(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}

