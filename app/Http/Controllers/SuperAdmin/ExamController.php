<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $this->authorizeSuperAdmin();

        $exams = Exam::query()
            ->with('creator:id,name')
            ->withCount([
                'questions',
                'attempts',
                'questions as approved_questions_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->latest()
            ->paginate(15);

        return view('admin.exams.index', compact('exams'));
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        return view('admin.exams.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate($this->examRules());

        if ($validated['status'] === 'published') {
            throw ValidationException::withMessages([
                'status' => 'An exam must have at least 25 approved questions before it can be published.',
            ]);
        }

        $exam = Exam::create([
            'title' => $validated['title'],
            'duration_minutes' => $validated['duration_minutes'],
            'status' => $validated['status'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('super.admin.exams.show', $exam)->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): View
    {
        $this->authorizeSuperAdmin();
        $exam->load('creator:id,name')->loadCount([
            'questions',
            'attempts',
            'questions as approved_questions_count' => fn (Builder $query) => $query->where('status', 'approved'),
        ]);

        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam): View|RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if ($exam->attempts()->exists()) {
            return redirect()->route('super.admin.exams.show', $exam)
                ->with('error', 'This exam has attempt history and can no longer be edited.');
        }

        return view('admin.exams.edit', compact('exam'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $validated = $request->validate($this->examRules());

        DB::transaction(function () use ($exam, $validated): void {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();

            if ($lockedExam->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'exam' => 'This exam has attempt history and can no longer be edited.',
                ]);
            }

            if ($lockedExam->status === 'closed' && $validated['status'] !== 'closed') {
                throw ValidationException::withMessages([
                    'status' => 'A closed exam cannot be reopened.',
                ]);
            }

            if ($validated['status'] === 'published') {
                $this->ensurePublishable($lockedExam);
            }

            $lockedExam->update($validated);
        });

        return redirect()->route('super.admin.exams.show', $exam)->with('success', 'Exam updated successfully.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        DB::transaction(function () use ($exam): void {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();

            if ($lockedExam->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft exam can be published.',
                ]);
            }

            $this->ensurePublishable($lockedExam);

            $lockedExam->update(['status' => 'published']);
        });

        return redirect()->route('super.admin.exams.index')->with('success', 'Exam published successfully.');
    }

    public function close(Exam $exam): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        DB::transaction(function () use ($exam): void {
            $lockedExam = Exam::query()->whereKey($exam->id)->lockForUpdate()->firstOrFail();

            if ($lockedExam->status !== 'published') {
                throw ValidationException::withMessages([
                    'status' => 'Only a published exam can be closed.',
                ]);
            }

            $lockedExam->update(['status' => 'closed']);
        });

        return redirect()->route('super.admin.exams.index')->with('success', 'Exam closed successfully.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if ($exam->status !== 'draft' || $exam->attempts()->exists()) {
            return redirect()->route('super.admin.exams.index')
                ->with('error', 'Only draft exams without attempt history can be deleted.');
        }

        $exam->delete();

        return redirect()->route('super.admin.exams.index')->with('success', 'Draft exam deleted.');
    }

    /** @return array<string, string> */
    private function examRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', 'in:draft,published,closed'],
        ];
    }

    private function ensurePublishable(Exam $exam): void
    {
        if ($exam->questions()->where('status', 'approved')->count() < 25) {
            throw ValidationException::withMessages([
                'status' => 'An exam must have at least 25 approved questions before it can be published.',
            ]);
        }
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }
}
