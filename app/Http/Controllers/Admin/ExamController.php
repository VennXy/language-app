<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $exams = Exam::query()
            ->where('status', 'draft')
            ->withCount([
                'questions as my_questions_count' => fn (Builder $query) => $query->where('admin_id', auth()->id()),
            ])
            ->orderBy('title')
            ->get();

        return view('admin.exam-questions.exams', compact('exams'));
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
