<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptQuestion;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserExamController extends Controller
{
    private const QUESTION_COUNT = 25;

    public function index(): View
    {
        $this->authorizeStudent();

        $exams = Exam::query()
            ->where('status', 'published')
            ->with(['attempts' => fn ($query) => $query->where('user_id', auth()->id())])
            ->orderBy('title')
            ->get();

        return view('user.exams.index', compact('exams'));
    }

    public function show(Exam $exam): View
    {
        $this->authorizeStudent();
        abort_unless($exam->status === 'published', 404);

        $attempt = $exam->attempts()->where('user_id', auth()->id())->first();

        return view('user.exams.show', compact('exam', 'attempt'));
    }

    public function start(Exam $exam): RedirectResponse
    {
        $this->authorizeStudent();

        try {
            $attempt = DB::transaction(function () use ($exam): ExamAttempt {
                $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedExam->status === 'published', 404);

                $studentId = auth()->id();
                $existingAttempt = $lockedExam->attempts()->where('user_id', $studentId)->first();
                if ($existingAttempt) {
                    return $existingAttempt;
                }

                $approvedQuestions = $lockedExam->questions()
                    ->where('status', 'approved')
                    ->inRandomOrder()
                    ->limit(self::QUESTION_COUNT)
                    ->get();

                if ($approvedQuestions->count() !== self::QUESTION_COUNT) {
                    throw ValidationException::withMessages([
                        'exam' => 'This exam needs at least 25 approved questions before it can be started.',
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

        abort_unless($questions->count() === self::QUESTION_COUNT, 409, 'This attempt does not contain the expected question snapshots.');

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

        abort_unless($questions->count() === self::QUESTION_COUNT, 409, 'This attempt does not contain the expected question snapshots.');

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
        $attempt->score_percent = round(($correctCount / self::QUESTION_COUNT) * 100, 2);
        $attempt->passed = $correctCount >= (self::QUESTION_COUNT * 0.6);
        $attempt->save();
    }

    private function authorizeStudent(): void
    {
        abort_unless(auth()->user()?->role === 'student', 403);
    }
}
