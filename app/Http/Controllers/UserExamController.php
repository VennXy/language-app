<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use App\Models\ExamSlot;
use App\Models\Level;
use App\Services\LevelProgressionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserExamController extends Controller
{
    /** Number of stable exam slots offered to a student at a time. */
    private const EXAM_SLOTS = 3;

    public function index(): View
    {
        $this->authorizeStudent();

        $level = $this->currentLevel();
        $studentId = auth()->id();

        // A level's Exams only unlock once that level's required Training is
        // complete (reusing the established level-completion definition). When it
        // is not complete, no exam in the level is offered as startable, matching
        // the server-side ensureTrainingComplete() guard on show()/start().
        $trainingComplete = $level === null
            ? true
            : app(LevelProgressionService::class)->isLevelTrainingComplete(auth()->user(), $level);

        // Eligible exams: published, matching the student's current level, and
        // carrying at least the minimum number of approved questions. Ordered by
        // id so the deterministic progression order (ascending id) is respected
        // when filling empty slots.
        $eligibleExamIds = Exam::query()
            ->where('status', 'published')
            ->when($level, fn ($query) => $query->where('level_id', $level->id))
            ->whereRaw(
                '(select count(*) from exam_questions where exam_questions.exam_id = exams.id and exam_questions.status = ?) >= ?',
                ['approved', $this->minimumApprovedQuestions()]
            )
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Resolve the student's stable slots. This is idempotent: refreshing the
        // index returns the same exam ids, only replacing a slot whose exam has
        // been passed (or has otherwise become ineligible) with a new eligible
        // exam. Failed exams stay in their slot until passed.
        $selectedExamIds = $this->reconcileExamSlots($studentId, $eligibleExamIds);

        $exams = $selectedExamIds === []
            ? collect()
            : Exam::query()
                ->whereIn('id', $selectedExamIds)
                ->with(['attempts' => fn ($query) => $query->where('user_id', $studentId)])
                ->get()
                // Preserve the stable slot (position) order, not title order.
                ->sortBy(fn (Exam $exam): int => array_search($exam->id, $selectedExamIds, true))
                ->values();

        // Trusted, server-side startable status for the UI: an exam is startable
        // when the same rules enforced by start()/show() are satisfied (training
        // complete for the level AND the progression prerequisite passed). The
        // view relies on this instead of re-implementing the rule, so the UI lock
        // state always matches the server and cannot drift out of sync.
        $startableExamIds = $trainingComplete
            ? $exams
                ->filter(fn (Exam $exam): bool => $this->isExamStartable($exam, $studentId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        // Exams the student has passed are surfaced as completed (never offered
        // again as available slots). The view renders these as "Completed".
        $completedExams = $this->completedExams($studentId, $eligibleExamIds);

        return view('user.exams.index', compact(
            'exams',
            'completedExams',
            'level',
            'startableExamIds',
            'trainingComplete',
        ));
    }

    public function show(Exam $exam): View
    {
        $this->authorizeStudent();
        abort_unless($exam->status === 'published', 404);
        $this->ensureLevelEligible($exam);
        $this->ensureTrainingComplete($exam);
        $this->ensurePreviousExamPassed($exam);

        // Latest attempt wins: with repeat attempts allowed there may be
        // several rows; always surface the newest one.
        $attempt = $exam->attempts()
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->first();

        return view('user.exams.show', compact('exam', 'attempt'));
    }

    public function start(Exam $exam): RedirectResponse
    {
        $this->authorizeStudent();

        try {
            $attempt = DB::transaction(function () use ($exam): ExamAttempt {
                $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedExam->status === 'published', 404);

                $this->ensureLevelEligible($lockedExam);
                $this->ensureTrainingComplete($lockedExam);

                // Server-side progression lock: a student must pass the current
                // exam before starting the next one in the prescribed sequence.
                $this->ensurePreviousExamPassed($lockedExam);

                $studentId = auth()->id();

                // Reuse only an ACTIVE attempt so a refresh keeps the same
                // snapshot; finished attempts stay in history and a later
                // start creates a fresh attempt (repeat attempts allowed).
                $activeAttempt = $lockedExam->attempts()
                    ->where('user_id', $studentId)
                    ->where('status', 'in_progress')
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                if ($activeAttempt) {
                    return $activeAttempt;
                }

                $minimum = $this->minimumApprovedQuestions();
                $perAttempt = $this->questionsPerAttempt();

                // Rotation cycle: a "Question Set" is this exam's approved bank.
                // Prefer questions NOT yet used anywhere in the learner's current
                // rotation cycle (all prior attempts on this exam) so each retry
                // draws a genuinely fresh set while unused questions remain.
                $cycleUsedIds = $this->usedQuestionIdsInCurrentCycle($lockedExam, $studentId);
                $approvedQuestions = $this->selectQuestions($lockedExam, $cycleUsedIds, $perAttempt);

                // New cycle: once the unused pool can no longer fill a full set,
                // begin a new cycle. Prefer a set that differs from the immediately
                // previous attempt, then fall back to the whole eligible bank.
                if ($approvedQuestions->count() < $perAttempt) {
                    $previousIds = $this->previousAttemptQuestionIds($lockedExam, $studentId);
                    $differFromPrevious = $this->selectQuestions($lockedExam, $previousIds, $perAttempt);

                    if ($differFromPrevious->count() > $approvedQuestions->count()) {
                        $approvedQuestions = $differFromPrevious;
                    }

                    if ($approvedQuestions->count() < $perAttempt) {
                        $withoutAvoid = $this->selectQuestions($lockedExam, [], $perAttempt);

                        if ($withoutAvoid->count() > $approvedQuestions->count()) {
                            $approvedQuestions = $withoutAvoid;
                        }
                    }
                }

                if ($approvedQuestions->count() < $minimum || $approvedQuestions->isEmpty()) {
                    throw ValidationException::withMessages([
                        'exam' => "This exam needs at least {$minimum} approved question(s) before it can be started.",
                    ]);
                }

                $startedAt = now();
                $attempt = ExamAttempt::create([
                    'exam_id' => $lockedExam->id,
                    'user_id' => $studentId,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addMinutes($lockedExam->duration_minutes),
                    'status' => 'in_progress',
                ]);

                foreach ($approvedQuestions as $index => $question) {
                    ExamAttemptQuestion::create([
                        'exam_attempt_id' => $attempt->id,
                        'exam_question_id' => $question->id,
                        'question_order' => $index + 1,
                        'question' => $question->question,
                        'option_a' => $question->option_a,
                        'option_b' => $question->option_b,
                        'option_c' => $question->option_c,
                        'option_d' => $question->option_d,
                        'correct_option' => $question->correct_option,
                        'selected_option' => null,
                        'is_correct' => null,
                    ]);
                }

                return $attempt;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $attempt = ExamAttempt::query()
                ->where('exam_id', $exam->id)
                ->where('user_id', auth()->id())
                ->first();

            if (! $attempt) {
                throw $exception;
            }
        }

        return redirect()->route('student.exams.attempts.show', $attempt);
    }

    public function attempt(Request $request, ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeStudent();

        $attempt = DB::transaction(function () use ($attempt): ExamAttempt {
            $lockedAttempt = $this->lockOwnedAttempt($attempt->id);
            if ($lockedAttempt->status === 'in_progress' && now()->greaterThanOrEqualTo($lockedAttempt->expires_at)) {
                $this->finalizeLockedAttempt($lockedAttempt);
            }

            return $lockedAttempt;
        });

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.exams.attempts.result', $attempt);
        }

        $attempt->load('exam:id,title,duration_minutes,status');
        $questions = $attempt->questions()
            ->orderBy('question_order')
            ->get([
                'id',
                'question_order',
                'question',
                'option_a',
                'option_b',
                'option_c',
                'option_d',
                'selected_option',
            ]);

        $requestedOrder = $request->integer('question', 1);
        $currentQuestion = $questions->firstWhere('question_order', $requestedOrder) ?? $questions->first();
        $answeredCount = $questions->whereNotNull('selected_option')->count();
        $serverNowMs = now()->getTimestamp() * 1000;
        $expiresAtMs = $attempt->expires_at->getTimestamp() * 1000;

        return view('user.exams.attempt', compact(
            'attempt',
            'questions',
            'currentQuestion',
            'answeredCount',
            'serverNowMs',
            'expiresAtMs',
        ));
    }

    public function answer(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeStudent();
        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'selected_option' => ['required', 'in:a,b,c,d'],
        ]);

        [$lockedAttempt, $questionOrder] = DB::transaction(function () use ($attempt, $validated): array {
            $lockedAttempt = $this->lockOwnedAttempt($attempt->id);

            if ($lockedAttempt->status !== 'in_progress') {
                return [$lockedAttempt, null];
            }

            if (now()->greaterThanOrEqualTo($lockedAttempt->expires_at)) {
                $this->finalizeLockedAttempt($lockedAttempt);

                return [$lockedAttempt, null];
            }

            $snapshot = ExamAttemptQuestion::query()
                ->where('exam_attempt_id', $lockedAttempt->id)
                ->whereKey($validated['question_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $snapshot->selected_option = $validated['selected_option'];
            $snapshot->is_correct = null;
            $snapshot->save();

            return [$lockedAttempt, $snapshot->question_order];
        });

        if ($lockedAttempt->status !== 'in_progress') {
            return redirect()->route('student.exams.attempts.result', $lockedAttempt);
        }

        return redirect()->route('student.exams.attempts.show', [
            'attempt' => $lockedAttempt,
            'question' => $questionOrder,
        ]);
    }

    public function submit(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeStudent();

        $finalizedAttempt = DB::transaction(function () use ($attempt): ExamAttempt {
            $lockedAttempt = $this->lockOwnedAttempt($attempt->id);

            if ($lockedAttempt->status === 'in_progress') {
                $this->finalizeLockedAttempt($lockedAttempt);
            }

            return $lockedAttempt;
        });

        return redirect()->route('student.exams.attempts.result', $finalizedAttempt);
    }

    public function result(ExamAttempt $attempt): View|RedirectResponse
    {
        $this->authorizeStudent();

        $attempt = DB::transaction(function () use ($attempt): ExamAttempt {
            $lockedAttempt = $this->lockOwnedAttempt($attempt->id);

            if ($lockedAttempt->status === 'in_progress') {
                if (now()->greaterThanOrEqualTo($lockedAttempt->expires_at)) {
                    $this->finalizeLockedAttempt($lockedAttempt);
                } else {
                    return $lockedAttempt;
                }
            }

            return $lockedAttempt;
        });

        if ($attempt->status === 'in_progress') {
            return redirect()->route('student.exams.attempts.show', $attempt);
        }

        $attempt->load([
            'exam:id,title,duration_minutes',
            'questions' => fn ($query) => $query->orderBy('question_order'),
        ]);

        return view('user.exams.result', compact('attempt'));
    }

    private function lockOwnedAttempt(int $attemptId): ExamAttempt
    {
        return ExamAttempt::query()
            ->whereKey($attemptId)
            ->where('user_id', auth()->id())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function finalizeLockedAttempt(ExamAttempt $attempt): void
    {
        $now = now();
        $questions = $attempt->questions()->orderBy('question_order')->lockForUpdate()->get();

        // Score against the actual number of snapshotted questions, not a fixed
        // target. start() snapshots up to questionsPerAttempt() questions (fewer
        // when the eligible bank is smaller), so the denominator must match the
        // snapshot so 3-question attempts (and legacy 25-question attempts) both
        // finalize correctly at the unchanged 60% pass threshold.
        $questionCount = $questions->count();

        abort_unless($questionCount > 0, 409, 'This attempt does not contain the expected question snapshots.');

        $correctCount = 0;
        foreach ($questions as $question) {
            $isCorrect = $question->selected_option !== null
                && $question->selected_option === $question->correct_option;
            $question->is_correct = $isCorrect;
            $question->save();
            $correctCount += $isCorrect ? 1 : 0;
        }

        $attempt->status = $now->greaterThanOrEqualTo($attempt->expires_at) ? 'expired' : 'submitted';
        $attempt->submitted_at = $now;
        $attempt->correct_count = $correctCount;
        $attempt->score_percent = round(($correctCount / $questionCount) * 100, 2);
        $attempt->passed = $correctCount >= ($questionCount * 0.6);
        $attempt->save();

        // Passing an exam may complete the level (Training + all published exams
        // passed), which unlocks the next level. Delegated to the shared
        // progression rule so the exam path and the lesson path agree.
        if ($attempt->passed && $attempt->exam?->level_id) {
            $level = Level::find($attempt->exam->level_id);

            if ($level) {
                app(LevelProgressionService::class)->syncLevelCompletion(auth()->user(), $level);
            }
        }
    }

    /**
     * Reconcile the student's up-to-three stable exam slots against the current
     * set of eligible exams.
     *
     * Rules enforced here:
     *  - A slot whose exam is still eligible and not yet passed is kept in its
     *    exact position (this keeps a failed exam in place for retry and keeps
     *    unfinished exams stable across refreshes).
     *  - A slot whose exam has been passed or is no longer eligible is replaced,
     *    and only that slot changes; the other slots keep their exam and their
     *    position.
     *  - Empty slots are filled with eligible, not-yet-passed exams that are not
     *    already occupying another slot (ascending id order for determinism).
     *  - The same exam id never occupies two slots (DB unique constraint + logic).
     *
     * @param  list<int>  $eligibleExamIds
     * @return list<int>  the selected exam ids in stable slot (position) order
     */
    private function reconcileExamSlots(int $studentId, array $eligibleExamIds): array
    {
        return DB::transaction(function () use ($studentId, $eligibleExamIds): array {
            $eligible = array_values(array_unique(array_map('intval', $eligibleExamIds)));

            // Passed exams (any passed attempt) are never offered as available.
            $passedIds = $eligible === []
                ? []
                : ExamAttempt::query()
                    ->where('user_id', $studentId)
                    ->where('passed', true)
                    ->whereIn('exam_id', $eligible)
                    ->pluck('exam_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->all();

            // Available = eligible and not yet passed.
            $availableIds = array_values(array_diff($eligible, $passedIds));

            $slots = ExamSlot::query()
                ->where('user_id', $studentId)
                ->orderBy('position')
                ->lockForUpdate()
                ->get();

            // Keep only slots whose exam is still available; record the exam by
            // position so a replaced slot does not disturb the other positions.
            $kept = [];
            foreach ($slots as $slot) {
                $examId = (int) $slot->exam_id;
                if (in_array($examId, $availableIds, true)) {
                    $kept[(int) $slot->position] = $examId;
                }
            }

            // Exams that can still fill a slot: available and not already kept.
            $keptExamIds = array_values($kept);
            $toFill = array_values(array_diff($availableIds, $keptExamIds));

            // Keep the still-available exams and fill up to EXAM_SLOTS with the
            // next available exams not already kept.
            $selected = $keptExamIds;
            foreach ($toFill as $examId) {
                if (count($selected) >= self::EXAM_SLOTS) {
                    break;
                }
                $selected[] = $examId;
            }

            $selected = array_slice($selected, 0, self::EXAM_SLOTS);

            // Always present the slots in curriculum order. The seeder creates
            // A1 Exam 01..10 with ascending ids and progression (and this list)
            // both rely on ascending id, so sorting by id keeps the display in
            // curriculum order and prevents a replaced slot from scrambling the
            // order into something like [D, B, C] after the first exam is passed.
            sort($selected);

            // Rewrite the persisted slots to match the reconciled selection,
            // one row per occupied position in curriculum order.
            ExamSlot::query()->where('user_id', $studentId)->delete();

            $position = 0;
            foreach ($selected as $examId) {
                ExamSlot::create([
                    'user_id' => $studentId,
                    'exam_id' => $examId,
                    'position' => ++$position,
                ]);
            }

            return $selected;
        });
    }

    /**
     * Eligible exams the student has already passed, for the "Completed" section.
     * These are never returned as available slots.
     *
     * @param  list<int>  $eligibleExamIds
     */
    private function completedExams(int $studentId, array $eligibleExamIds): \Illuminate\Support\Collection
    {
        if ($eligibleExamIds === []) {
            return collect();
        }

        $passedIds = ExamAttempt::query()
            ->where('user_id', $studentId)
            ->where('passed', true)
            ->whereIn('exam_id', $eligibleExamIds)
            ->pluck('exam_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        if ($passedIds === []) {
            return collect();
        }

        return Exam::query()
            ->whereIn('id', $passedIds)
            ->with(['attempts' => fn ($query) => $query->where('user_id', $studentId)])
            ->get()
            ->sortBy('title')
            ->values();
    }

    /**
     * Minimum approved questions required before an exam can be started.
     * Testing mode lowers the production requirement of 25 to 1.
     */
    private function minimumApprovedQuestions(): int
    {
        if (config('gamification.exam_testing_mode', false)) {
            return max(1, (int) config('gamification.exam_minimum_approved_questions', 1));
        }

        return (int) config('gamification.exam_production_approved_questions', 25);
    }

    /** Number of questions shown in a single attempt (fewer only when the bank is smaller). */
    private function questionsPerAttempt(): int
    {
        return max(1, (int) config('gamification.exam_questions_per_attempt', 3));
    }

    /** Randomly select up to $limit approved questions, optionally excluding $excludeIds. */
    private function selectQuestions(Exam $exam, array $excludeIds, int $limit)
    {
        return $exam->questions()
            ->where('status', 'approved')
            ->when(! empty($excludeIds), fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->inRandomOrder()
            ->limit($limit)
            ->lockForUpdate()
            ->get();
    }

    /** Question IDs used in the immediately finished previous attempt. */
    private function previousAttemptQuestionIds(Exam $exam, int $studentId): array
    {
        $previousAttempt = $exam->attempts()
            ->where('user_id', $studentId)
            ->whereIn('status', ['submitted', 'expired'])
            ->orderByDesc('id')
            ->first();

        if (! $previousAttempt) {
            return [];
        }

        return ExamAttemptQuestion::query()
            ->where('exam_attempt_id', $previousAttempt->id)
            ->pluck('exam_question_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Question IDs the learner has already used across every finished attempt on
     * this exam (the current rotation cycle). A new attempt prefers questions not
     * in this set so retries keep drawing fresh sets until the bank is exhausted;
     * start() then begins a new cycle. Only finalized (submitted/expired) attempts
     * count, so an in-progress attempt never reduces its own future options.
     */
    private function usedQuestionIdsInCurrentCycle(Exam $exam, int $studentId): array
    {
        $finishedAttemptIds = $exam->attempts()
            ->where('user_id', $studentId)
            ->whereIn('status', ['submitted', 'expired'])
            ->pluck('id')
            ->all();

        if ($finishedAttemptIds === []) {
            return [];
        }

        return ExamAttemptQuestion::query()
            ->whereIn('exam_attempt_id', $finishedAttemptIds)
            ->pluck('exam_question_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Whether the student may start the given exam right now, using the exact
     * same progression rule as ensurePreviousExamPassed(). Provided to the view
     * so the UI lock state matches the server instead of re-implementing the
     * rule in Blade. This is a read-only boolean mirror; the authoritative guard
     * remains ensurePreviousExamPassed() on show()/start().
     */
    private function isExamStartable(Exam $exam, int $studentId): bool
    {
        $previousExam = Exam::query()
            ->where('level_id', $exam->level_id)
            ->where('status', 'published')
            ->where('id', '<', $exam->id)
            ->orderByDesc('id')
            ->first();

        if (! $previousExam) {
            return true;
        }

        return (bool) $previousExam->attempts()
            ->where('user_id', $studentId)
            ->where('passed', true)
            ->exists();
    }

    /**
     * Server-side progression lock: the immediately preceding published exam in
     * the same level must already have a passed attempt for this student.
     * Runs on both show() and start() so direct URL access is covered.
     */
    private function ensurePreviousExamPassed(Exam $exam): void
    {
        $level = $this->currentLevel();

        if (! $level) {
            return;
        }

        $previousExam = Exam::query()
            ->where('level_id', $exam->level_id)
            ->where('status', 'published')
            ->where('id', '<', $exam->id)
            ->orderByDesc('id')
            ->first();

        if (! $previousExam) {
            return;
        }

        $passed = $previousExam->attempts()
            ->where('user_id', auth()->id())
            ->where('passed', true)
            ->exists();

        abort_unless($passed, 403, 'You must pass the previous exam before starting this one.');
    }

    /**
     * The student's currently active level, resolved the same way the student
     * dashboard does it: the first accessible level whose user_levels row is
     * 'active', falling back to the first accessible level. Students always
     * auto-activate at A1, so this is A1 unless the level has been completed.
     */
    private function currentLevel(): ?Level
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'student') {
            return null;
        }

        $progression = app(LevelProgressionService::class);
        $levels = $progression->levelsInOrder();
        $accessibleLevelIds = $progression->accessibleLevelIds($user);

        if (empty($accessibleLevelIds)) {
            return null;
        }

        $statuses = DB::table('user_levels')
            ->where('user_id', $user->id)
            ->pluck('status', 'level_id');

        return $levels->first(
            fn (Level $level) => in_array((int) $level->id, $accessibleLevelIds, true)
                && ($statuses[(int) $level->id] ?? null) === 'active'
        )
            ?? $levels->first(fn (Level $level) => in_array((int) $level->id, $accessibleLevelIds, true));
    }

    /**
     * A student may only view or start an exam that belongs to their current
     * level. Enforced on both show() and start() so a direct URL to another
     * level's exam is rejected (404) rather than silently allowed.
     */
    private function ensureLevelEligible(Exam $exam): void
    {
        $level = $this->currentLevel();

        if (! $level) {
            return;
        }

        abort_unless((int) $exam->level_id === (int) $level->id, 404);
    }

    /**
     * Server-side gate: a level's Exams are only available once that level's
     * required Training is complete. Reuses the established level-completion
     * definition via LevelProgressionService::isLevelTrainingComplete(). Runs on
     * show() and start() so direct URL / crafted requests cannot bypass it.
     */
    private function ensureTrainingComplete(Exam $exam): void
    {
        if (! $exam->level_id) {
            return;
        }

        $level = Level::query()->find($exam->level_id);

        if (! $level) {
            return;
        }

        abort_unless(
            app(LevelProgressionService::class)->isLevelTrainingComplete(auth()->user(), $level),
            403,
            'Complete this level’s training before taking its exams.'
        );
    }

    private function authorizeStudent(): void
    {
        abort_unless(auth()->user()?->role === 'student', 403);
    }
}
