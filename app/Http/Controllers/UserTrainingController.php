<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use App\Models\Vocabulary;
use App\Services\HeartService;
use App\Services\LevelProgressionService;
use App\Services\TrainingAnswerValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTrainingController extends Controller
{
    private const DEFAULT_HEARTS = 5;
    private const LEARNING_FLOW_VERSION = 1;
    private const LEARNING_STAGES = [
        'picture_recognition',
        'word_recognition',
        'recall',
        'listening',
        'usage',
        'review',
    ];

    public function showTraining(Request $request, $id, LevelProgressionService $progression, HeartService $heartService)
    {
        $lesson = Lesson::with([
            'vocabularies' => fn ($query) => $query
                ->orderByRaw("CASE WHEN question_type = 'image_selection' THEN 0 ELSE 1 END")
                ->orderBy('id'),
            'level',
        ])->findOrFail($id);
        abort_unless($progression->canAccessLesson(auth()->user(), $lesson), 403);

        $user = auth()->user();
        $currentHearts = $user->role === 'student' ? $heartService->refresh($user) : null;
        $sessionKey = $this->lessonProgressSessionKey(auth()->id(), $lesson->id);
        $lessonState = $this->loadLessonState($sessionKey);

        if ($request->boolean('restart') && $currentHearts === 0) {
            $lessonState = array_replace(
                $this->newLessonState(0),
                is_array($lessonState) ? $lessonState : []
            );
            $lessonState['hearts'] = 0;
            $lessonState['session_ended'] = true;
            $lessonState['end_reason'] = 'hearts';
        } elseif ($request->boolean('restart')) {
            $lessonState = $this->newLessonState(
                $currentHearts ?? (int) (is_array($lessonState) ? ($lessonState['hearts'] ?? self::DEFAULT_HEARTS) : self::DEFAULT_HEARTS)
            );
        } elseif (!is_array($lessonState)) {
            $lessonState = $this->newLessonState(
                $currentHearts ?? self::DEFAULT_HEARTS
            );
            $lessonState['_fresh_without_durable'] = true;
        } elseif (($lessonState['lesson_completed'] ?? false)) {
            $lessonState = $this->newLessonState(
                $currentHearts ?? (int) ($lessonState['hearts'] ?? self::DEFAULT_HEARTS)
            );
        } else {
            $lessonState = array_replace($this->newLessonState($currentHearts ?? self::DEFAULT_HEARTS), $lessonState);
        }

        $learningItems = collect();
        $currentLearningItem = null;
        $usesLearningFlow = false;
        $isTeaching = false;
        $bookmarkedVocabularyIds = [];

        if (auth()->user()->role === 'student') {
            $bookmarkedVocabularyIds = DB::table('bookmarks')
                ->where('user_id', auth()->id())
                ->where('bookmarkable_type', Vocabulary::class)
                ->pluck('bookmarkable_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $learningItems = $this->learningItemsForLesson($lesson);

        if ($learningItems->isNotEmpty()) {
            $usesLearningFlow = true;

            if (($lessonState['learning_flow_version'] ?? null) !== self::LEARNING_FLOW_VERSION) {
                $previousState = $lessonState;
                $lessonState = $this->newLessonState();
                $lessonState['next_index'] = max(0, (int) ($previousState['next_index'] ?? 0));
                $lessonState['answered_ids'] = array_values(array_unique(array_map(
                    'intval',
                    $previousState['answered_ids'] ?? []
                )));
                $lessonState['skipped_ids'] = array_values(array_unique(array_map(
                    'intval',
                    $previousState['skipped_ids'] ?? []
                )));
                $lessonState['learning_item_index'] = max(0, (int) ($previousState['learning_item_index'] ?? 0));
                $lessonState['taught_learning_item_ids'] = array_values(array_unique(array_map(
                    'intval',
                    $previousState['taught_learning_item_ids'] ?? []
                )));
                $lessonState['lesson_completed'] = (bool) ($previousState['lesson_completed'] ?? false);
                if (!empty($previousState['_fresh_without_durable'])) {
                    $lessonState['_fresh_without_durable'] = true;
                }
                $lessonState['hearts'] = max(
                    0,
                    (int) ($previousState['hearts'] ?? self::DEFAULT_HEARTS)
                );
                $lessonState['session_ended'] = (bool) ($previousState['session_ended'] ?? false);
                if (isset($previousState['end_reason'])) {
                    $lessonState['end_reason'] = $previousState['end_reason'];
                }
            }

            $lessonState['learning_flow_version'] = self::LEARNING_FLOW_VERSION;
            $lessonState['learning_item_index'] = max(0, (int) $lessonState['learning_item_index']);
            $lessonState['taught_learning_item_ids'] = array_values(array_unique(array_map(
                'intval',
                $lessonState['taught_learning_item_ids'] ?? []
            )));

            $isTeaching = $lessonState['learning_item_index'] < $learningItems->count();
            if ($isTeaching) {
                $currentLearningItem = $learningItems->get($lessonState['learning_item_index']);
            }

            $lesson->setRelation('vocabularies', $this->orderedQuestions($lesson));
        }

        if ($currentHearts !== null) {
            $lessonState['hearts'] = $currentHearts;
            if ($currentHearts === 0) {
                $lessonState['session_ended'] = true;
                $lessonState['end_reason'] = 'hearts';
            } elseif (($lessonState['end_reason'] ?? null) === 'hearts') {
                $lessonState['session_ended'] = false;
                unset($lessonState['end_reason']);
            }
        }

        $this->storeLessonState($sessionKey, $lessonState);

        $type = $request->query('type', 'training');

        return view('user.training', compact(
            'lesson',
            'type',
            'lessonState',
            'learningItems',
            'currentLearningItem',
            'usesLearningFlow',
            'isTeaching',
            'bookmarkedVocabularyIds'
        ));
    }

    public function completeLearningItem(
        Request $request,
        Lesson $lesson,
        int $itemId,
        LevelProgressionService $progression,
        HeartService $heartService
    ) {
        abort_unless($progression->canAccessLesson(auth()->user(), $lesson), 403);
        abort_unless($this->usesLearningFlow($lesson), 404);

        $sessionKey = $this->lessonProgressSessionKey(auth()->id(), $lesson->id);
        $lessonState = $this->loadLessonState($sessionKey);

        if (auth()->user()->role === 'student') {
            $lessonState['hearts'] = $heartService->refresh(auth()->user());
            if ($lessonState['hearts'] === 0) {
                $lessonState['session_ended'] = true;
                $lessonState['end_reason'] = 'hearts';
                $this->storeLessonState($sessionKey, $lessonState);
                abort(409, 'This lesson session has ended.');
            }
        }

        abort_unless(
            is_array($lessonState)
                && ($lessonState['learning_flow_version'] ?? null) === self::LEARNING_FLOW_VERSION,
            409,
            'Start the lesson before advancing its learning cards.'
        );

        if (($lessonState['session_ended'] ?? false) || ($lessonState['hearts'] ?? 0) <= 0) {
            abort(409, 'This lesson session has ended.');
        }

        $items = $this->learningItemsForLesson($lesson);
        $taughtIds = array_map('intval', $lessonState['taught_learning_item_ids'] ?? []);

        if (in_array($itemId, $taughtIds, true)) {
            return redirect()->route('user.training', ['id' => $lesson->id]);
        }

        $currentIndex = (int) ($lessonState['learning_item_index'] ?? 0);
        $currentItem = $items->get($currentIndex);

        abort_unless($currentItem && (int) $currentItem->id === $itemId, 409, 'Continue the current learning card first.');

        $lessonState['taught_learning_item_ids'][] = $itemId;
        $lessonState['taught_learning_item_ids'] = array_values(array_unique(array_map(
            'intval',
            $lessonState['taught_learning_item_ids']
        )));
        $lessonState['learning_item_index'] = $currentIndex + 1;
        $this->storeLessonState($sessionKey, $lessonState);

        $questionCount = $this->orderedQuestions($lesson)->count();
        $stepCount = $items->count() + $questionCount;
        $completedSteps = count($lessonState['taught_learning_item_ids'])
            + count(array_unique(array_map('intval', $lessonState['answered_ids'] ?? [])));
        $progression->recordLessonProgress(
            auth()->user(),
            $lesson,
            $stepCount > 0 ? (int) floor(($completedSteps / $stepCount) * 100) : 0
        );

        return redirect()->route('user.training', ['id' => $lesson->id]);
    }

    public function submitAnswer(
        Request $request,
        Lesson $lesson,
        LevelProgressionService $progression,
        TrainingAnswerValidator $answerValidator,
        HeartService $heartService
    )
    {
        abort_unless($progression->canAccessLesson(auth()->user(), $lesson), 403);

        $validated = $request->validate([
            'vocabulary_id' => ['required', 'integer'],
            'answer' => ['required', 'string'],
        ]);

        $questions = $this->orderedQuestions($lesson);
        $questionIds = $questions->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($questionIds)) {
            return response()->json(['correct' => false, 'message' => 'This lesson has no questions.'], 422);
        }

        $vocabularyId = (int) $validated['vocabulary_id'];
        $questionIndex = array_search($vocabularyId, $questionIds, true);

        if ($questionIndex === false) {
            abort(404);
        }

        $sessionKey = $this->lessonProgressSessionKey(auth()->id(), $lesson->id);
        $lessonState = array_replace($this->newLessonState(), $this->loadLessonState($sessionKey) ?? []);

        if (auth()->user()->role === 'student') {
            $lessonState['hearts'] = $heartService->refresh(auth()->user());
            if ($lessonState['hearts'] === 0) {
                $lessonState['session_ended'] = true;
                $lessonState['end_reason'] = 'hearts';
                $this->storeLessonState($sessionKey, $lessonState);
            }
        }

        if (($lessonState['session_ended'] ?? false) || ($lessonState['hearts'] ?? 0) <= 0) {
            return response()->json([
                'correct' => false,
                'hearts' => max(0, (int) ($lessonState['hearts'] ?? 0)),
                'session_ended' => true,
            ], 409);
        }

        if ($this->usesLearningFlow($lesson)
            && (!$this->allLearningItemsTaught($lesson, $lessonState)
                || !$this->questionPrerequisitesTaught($lesson, $vocabularyId, $lessonState))) {
            return response()->json([
                'correct' => false,
                'message' => 'Complete the required learning cards before practicing this question.',
                'hearts' => $lessonState['hearts'],
            ], 409);
        }

        if ($questionIndex < $lessonState['next_index']
            && in_array($vocabularyId, $lessonState['answered_ids'], true)) {
            $vocabulary = $questions[$questionIndex];
            $isCorrect = $answerValidator->matches(
                $validated['answer'],
                $vocabulary->correct_answer ?? '',
                $vocabulary->question_type
            );

            if ($isCorrect) {
                return response()->json([
                    'correct' => true,
                    'already_accepted' => true,
                    'lesson_completed' => $lessonState['lesson_completed'],
                    'hearts' => $lessonState['hearts'],
                    'progression' => [
                        'fire_awarded' => 0,
                        'streak_count' => (int) DB::table('users')->where('id', auth()->id())->value('streak_count'),
                    ],
                ]);
            }
        }

        if ($questionIndex !== $lessonState['next_index']) {
            return response()->json([
                'correct' => false,
                'message' => 'Answer the current lesson question first.',
                'hearts' => $lessonState['hearts'],
            ], 409);
        }

        $vocabulary = $questions[$questionIndex];
        $isCorrect = $answerValidator->matches(
            $validated['answer'],
            $vocabulary->correct_answer ?? '',
            $vocabulary->question_type
        );

        if (!$isCorrect) {
            if (auth()->user()->role === 'student') {
                DB::table('user_question_mistakes')->insertOrIgnore([
                    'user_id' => auth()->id(),
                    'lesson_id' => $lesson->id,
                    'vocabulary_id' => $vocabularyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $lessonState['hearts'] = auth()->user()->role === 'student'
                ? $heartService->consume(auth()->user())
                : max(0, (int) $lessonState['hearts'] - 1);
            if ($lessonState['hearts'] === 0) {
                $lessonState['session_ended'] = true;
                $lessonState['end_reason'] = 'hearts';
            }
            $this->storeLessonState($sessionKey, $lessonState);

            return response()->json([
                'correct' => false,
                'hearts' => $lessonState['hearts'],
                'session_ended' => $lessonState['session_ended'],
            ], 422);
        }

        $lessonState['answered_ids'][] = $vocabularyId;
        $lessonState['next_index'] = $questionIndex + 1;
        $lessonCompleted = count($lessonState['answered_ids']) === count($questionIds);

        $progressionResult = null;

        if ($lessonCompleted) {
            $progressionResult = $progression->completeLesson(auth()->user(), $lesson);
            $lessonState['lesson_completed'] = true;
        } else {
            $stepCount = $this->learningItemsForLesson($lesson)->count() + count($questionIds);
            $completedSteps = count(array_unique(array_map('intval', $lessonState['taught_learning_item_ids'] ?? [])))
                + count(array_unique(array_map('intval', $lessonState['answered_ids'])));
            $progression->recordLessonProgress(
                auth()->user(),
                $lesson,
                $stepCount > 0 ? (int) floor(($completedSteps / $stepCount) * 100) : 0
            );
        }

        if (!$lessonCompleted && $lessonState['next_index'] >= count($questionIds)) {
            $lessonState['session_ended'] = true;
            $lessonState['end_reason'] = 'questions_finished';
        }

        $this->storeLessonState($sessionKey, $lessonState);

        return response()->json([
            'correct' => true,
            'lesson_completed' => $lessonCompleted,
            'session_ended' => $lessonState['session_ended'],
            'hearts' => $lessonState['hearts'],
            'progression' => $progressionResult,
        ]);
    }

    public function skipQuestion(Request $request, Lesson $lesson, LevelProgressionService $progression, HeartService $heartService)
    {
        abort_unless($progression->canAccessLesson(auth()->user(), $lesson), 403);

        $validated = $request->validate([
            'vocabulary_id' => ['required', 'integer'],
        ]);

        $questionIds = $this->orderedQuestions($lesson)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $vocabularyId = (int) $validated['vocabulary_id'];
        $questionIndex = array_search($vocabularyId, $questionIds, true);

        if ($questionIndex === false) {
            abort(404);
        }

        $sessionKey = $this->lessonProgressSessionKey(auth()->id(), $lesson->id);
        $lessonState = array_replace($this->newLessonState(), $this->loadLessonState($sessionKey) ?? []);

        if (auth()->user()->role === 'student') {
            $lessonState['hearts'] = $heartService->refresh(auth()->user());
            if ($lessonState['hearts'] === 0) {
                $lessonState['session_ended'] = true;
                $lessonState['end_reason'] = 'hearts';
                $this->storeLessonState($sessionKey, $lessonState);
            }
        }

        if ($this->usesLearningFlow($lesson)
            && (!$this->allLearningItemsTaught($lesson, $lessonState)
                || !$this->questionPrerequisitesTaught($lesson, $vocabularyId, $lessonState))) {
            return response()->json([
                'accepted' => false,
                'message' => 'Complete the required learning cards before practicing this question.',
                'hearts' => $lessonState['hearts'],
            ], 409);
        }

        if (in_array($vocabularyId, $lessonState['skipped_ids'], true)) {
            return response()->json([
                'accepted' => true,
                'already_skipped' => true,
                'hearts' => $lessonState['hearts'],
                'session_ended' => $lessonState['session_ended'],
                'end_reason' => $lessonState['end_reason'] ?? null,
            ]);
        }

        if (($lessonState['session_ended'] ?? false) || ($lessonState['hearts'] ?? 0) <= 0) {
            return response()->json([
                'accepted' => false,
                'hearts' => max(0, (int) ($lessonState['hearts'] ?? 0)),
                'session_ended' => true,
            ], 409);
        }

        if ($questionIndex !== $lessonState['next_index']) {
            return response()->json([
                'accepted' => false,
                'message' => 'This question has already been passed or is not current.',
                'hearts' => $lessonState['hearts'],
            ], 409);
        }

        $lessonState['skipped_ids'][] = $vocabularyId;
        $lessonState['next_index'] = $questionIndex + 1;
        $lessonState['hearts'] = auth()->user()->role === 'student'
            ? $heartService->consume(auth()->user())
            : max(0, (int) $lessonState['hearts'] - 1);

        if ($lessonState['hearts'] === 0 || $lessonState['next_index'] >= count($questionIds)) {
            $lessonState['session_ended'] = true;
            $lessonState['end_reason'] = $lessonState['hearts'] === 0 ? 'hearts' : 'questions_finished';
        }

        $this->storeLessonState($sessionKey, $lessonState);

        return response()->json([
            'accepted' => true,
            'hearts' => $lessonState['hearts'],
            'session_ended' => $lessonState['session_ended'],
            'end_reason' => $lessonState['end_reason'] ?? null,
        ]);
    }

    public function heartStatus(HeartService $heartService)
    {
        abort_unless(auth()->user()->role === 'student', 403);

        return response()->json($heartService->state(auth()->user()));
    }

    public function refillHeartsWithGems(
        Request $request,
        LevelProgressionService $progression,
        HeartService $heartService
    )
    {
        abort_unless(auth()->user()->role === 'student', 403);

        $lesson = null;
        if ($request->filled('lesson_id')) {
            $validated = $request->validate([
                'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            ]);
            $lesson = Lesson::findOrFail($validated['lesson_id']);
            abort_unless($progression->canAccessLesson(auth()->user(), $lesson), 403);
        }

        $result = $heartService->refillWithGems(auth()->user());
        $destination = $lesson
            ? redirect()->route('user.training', $lesson->id)
            : back();

        if (!$result['success']) {
            $message = match ($result['reason']) {
                'insufficient_gems' => 'Not enough Gems. You need ' . $this->formatGemCost((int) config('gamification.heart_refill_gem_cost')) . ' Gems to refill your hearts.',
                default => 'Your hearts are already full.',
            };

            return $destination->with('gem_refill_error', $message);
        }

        return $destination->with('gem_refill_success', 'Hearts refilled. Your lesson position is ready to resume.');
    }

    private function formatGemCost(int $cost): string
    {
        return number_format(max(0, $cost));
    }

    private function orderedQuestions(Lesson $lesson)
    {
        if ($this->usesLearningFlow($lesson)) {
            $stageOrder = array_flip(self::LEARNING_STAGES);
            $links = DB::table('lesson_vocabulary_questions as lvq')
                ->join('lesson_vocabulary_items as lvi', 'lvi.id', '=', 'lvq.lesson_vocabulary_item_id')
                ->join('vocabularies as v', 'v.id', '=', 'lvq.vocabulary_question_id')
                ->where('lvi.lesson_id', $lesson->id)
                ->where('v.lesson_id', $lesson->id)
                ->whereIn('lvq.stage', self::LEARNING_STAGES)
                ->orderByRaw("CASE lvq.stage " . implode(' ', array_map(
                    fn ($stage, $rank) => "WHEN '" . addslashes($stage) . "' THEN {$rank}",
                    array_keys($stageOrder),
                    array_values($stageOrder)
                )) . ' ELSE 99 END')
                ->orderBy('lvi.sort_order')
                ->orderBy('lvq.stage_order')
                ->orderBy('lvi.id')
                ->orderBy('v.id')
                ->get([
                    'v.id as question_id',
                    'lvq.stage as learning_stage',
                    'lvi.audio_path as learning_audio_path',
                ]);
            $linkedQuestionIds = $links->pluck('question_id')->unique()->values();

            $seen = [];
            $orderedLinks = $links->filter(function ($link) use (&$seen): bool {
                if (isset($seen[$link->question_id])) {
                    return false;
                }

                $seen[$link->question_id] = true;

                return true;
            });

            $questions = Vocabulary::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('id', $orderedLinks->pluck('question_id'))
                ->get()
                ->keyBy('id');

            $linkedQuestions = $orderedLinks->map(function ($link) use ($questions) {
                $question = $questions->get($link->question_id);
                if (!$question) {
                    return null;
                }

                // A picture/audio mapping is usable only when its media exists.
                if ($link->learning_stage === 'picture_recognition'
                    && ($question->question_type !== 'image_selection'
                        || !$question->image_url
                        || preg_match('/[\\p{Hiragana}\\p{Katakana}\\p{Han}]/u', $question->question))) {
                    return null;
                }
                if ($link->learning_stage === 'listening'
                    && !$link->learning_audio_path
                    && !$question->audio) {
                    return null;
                }

                $question->learning_stage = $link->learning_stage;
                $question->learning_audio_path = $link->learning_audio_path ?: $question->audio;

                return $question;
            })->filter()->values();

            $unlinkedQuestions = Vocabulary::query()
                ->where('lesson_id', $lesson->id)
                ->whereNotIn('id', $linkedQuestionIds)
                ->orderByRaw("CASE WHEN question_type = 'image_selection' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            return $linkedQuestions->concat($unlinkedQuestions)->values();
        }

        return $lesson->vocabularies()
            ->orderByRaw("CASE WHEN question_type = 'image_selection' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    private function usesLearningFlow(Lesson $lesson): bool
    {
        return $this->learningItemsForLesson($lesson)->isNotEmpty();
    }

    private function learningItemsForLesson(Lesson $lesson)
    {
        return DB::table('lesson_vocabulary_items')
            ->where('lesson_id', $lesson->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function allLearningItemsTaught(Lesson $lesson, array $lessonState): bool
    {
        $items = $this->learningItemsForLesson($lesson);
        $taughtIds = array_map('intval', $lessonState['taught_learning_item_ids'] ?? []);

        return $items->isNotEmpty()
            && (int) ($lessonState['learning_item_index'] ?? 0) >= $items->count()
            && $items->every(fn ($item) => in_array((int) $item->id, $taughtIds, true));
    }

    private function questionPrerequisitesTaught(Lesson $lesson, int $questionId, array $lessonState): bool
    {
        $requiredItemIds = DB::table('lesson_vocabulary_questions as lvq')
            ->join('lesson_vocabulary_items as lvi', 'lvi.id', '=', 'lvq.lesson_vocabulary_item_id')
            ->join('vocabularies as v', 'v.id', '=', 'lvq.vocabulary_question_id')
            ->where('lvi.lesson_id', $lesson->id)
            ->where('v.lesson_id', $lesson->id)
            ->where('v.id', $questionId)
            ->whereIn('lvq.stage', self::LEARNING_STAGES)
            ->distinct()
            ->pluck('lvi.id')
            ->map(fn ($id) => (int) $id);

        if ($requiredItemIds->isEmpty()) {
            return true;
        }

        $taughtIds = array_map('intval', $lessonState['taught_learning_item_ids'] ?? []);

        return $requiredItemIds->every(fn ($id) => in_array($id, $taughtIds, true));
    }

    /**
     * Lesson state lives in the session first; fall back to the durable copy stored on the
     * existing user_progress row when the session copy is gone (e.g. expiry after the
     * learner left a lesson at 0 hearts and returned once hearts refilled).
     */
    private function loadLessonState(string $sessionKey): ?array
    {
        $state = session()->get($sessionKey);
        if (is_array($state)) {
            return $state;
        }

        [$userId, $lessonId] = $this->parseLessonProgressSessionKey($sessionKey);
        if ($userId === null || $lessonId === null) {
            return null;
        }

        $stored = DB::table('user_progress')
            ->where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->value('lesson_state');

        if (!is_string($stored) || $stored === '') {
            return null;
        }

        $decoded = json_decode($stored, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Keep the session copy and a durable copy in sync on the existing user_progress row.
     * Only states carrying real progress are persisted; fresh states clear any stale copy so
     * an explicit restart or completed lesson never resurrects an old position.
     */
    private function storeLessonState(string $sessionKey, array $state): void
    {
        session()->put($sessionKey, $state);

        // A plain first-visit GET (no durable copy existed) must never touch
        // the durable column; restart/completion still flow through below.
        $freshWithoutDurable = !empty($state['_fresh_without_durable']);
        unset($state['_fresh_without_durable']);

        [$userId, $lessonId] = $this->parseLessonProgressSessionKey($sessionKey);
        if ($userId === null || $lessonId === null) {
            return;
        }

        $query = DB::table('user_progress')->where('user_id', $userId)->where('lesson_id', $lessonId);

        $hasProgress = (int) ($state['next_index'] ?? 0) > 0
            || !empty($state['answered_ids'])
            || !empty($state['skipped_ids'])
            || (int) ($state['learning_item_index'] ?? 0) > 0
            || !empty($state['taught_learning_item_ids'])
            || !empty($state['lesson_completed'])
            || !empty($state['session_ended']);

        if (!$hasProgress) {
            if ($freshWithoutDurable) {
                return;
            }
            $query->update(['lesson_state' => null]);
            return;
        }

        if ((clone $query)->exists()) {
            $query->update(['lesson_state' => json_encode($state), 'updated_at' => now()]);
            return;
        }

        DB::table('user_progress')->insert([
            'user_id' => $userId,
            'lesson_id' => $lessonId,
            'status' => 'in_progress',
            'progress_percent' => 0,
            'lesson_state' => json_encode($state),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function parseLessonProgressSessionKey(string $sessionKey): array
    {
        $parts = explode('.', $sessionKey);
        if (count($parts) !== 3 || $parts[0] !== 'lesson_question_progress') {
            return [null, null];
        }

        return [(int) $parts[1], (int) $parts[2]];
    }

    private function newLessonState(int $hearts = self::DEFAULT_HEARTS): array
    {
        return [
            'next_index' => 0,
            'answered_ids' => [],
            'skipped_ids' => [],
            'lesson_completed' => false,
            'session_ended' => false,
            'hearts' => max(0, min(self::DEFAULT_HEARTS, $hearts)),
        ];
    }

    private function lessonProgressSessionKey(int $userId, int $lessonId): string
    {
        return 'lesson_question_progress.' . $userId . '.' . $lessonId;
    }

}
