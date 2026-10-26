<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\User;
use App\Models\Vocabulary;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->role === 'super_admin', 403);

        $admins = User::where('role', 'admin')->get();
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'student')->count();
        $totalLessons = Lesson::count();
        $totalVocabularies = Vocabulary::count();
        $totalExams = Exam::count();
        $pendingReviews = ExamQuestion::where('status', 'pending')->count();

        $registrationTrend = $this->registrationTrend();
        $examStatus = $this->examStatusDistribution();
        $questionStatus = $this->questionReviewStatus();
        $roadmap = $this->levelContentRoadmap();
        $recentRegistrations = $this->recentRegistrations(6);
        $recentQuestionActivity = $this->recentQuestionActivity(6);
        $chartData = $this->chartData();
        $chartJson = $this->chartJson();

        return view('admin.dashboard', compact(
            'admins', 'totalUsers', 'totalStudents', 'totalLessons', 'totalVocabularies', 'totalExams', 'pendingReviews',
            'registrationTrend', 'examStatus', 'questionStatus', 'roadmap', 'recentRegistrations', 'recentQuestionActivity',
            'chartData', 'chartJson'
        ));
    }

    private function registrationTrend(): Collection
    {
        $start = now()->subMonths(12)->startOfMonth();
        $end = now()->endOfMonth();

        return DB::table('exam_attempts')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function examStatusDistribution(): array
    {
        $order = ['draft' => 0, 'published' => 1, 'closed' => 2];
        $rows = DB::table('exams')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->sortBy(fn ($row) => $order[$row->status] ?? 99)
            ->values();

        return [
            'labels' => $rows->pluck('status')->toArray(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function questionReviewStatus(): array
    {
        $order = ['pending' => 0, 'approved' => 1, 'rejected' => 2];
        $rows = DB::table('exam_questions')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->sortBy(fn ($row) => $order[$row->status] ?? 99)
            ->values();

        return [
            'labels' => $rows->pluck('status')->toArray(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    private function levelContentRoadmap(): Collection
    {
        return Level::with('lessons')->get()->map(fn ($level) => [
            'id' => $level->id,
            'name' => $level->name,
            'lesson_count' => $level->lessons->count(),
            'lessons' => $level->lessons->pluck('title', 'id')->toArray(),
        ]);
    }

    private function recentRegistrations(int $limit = 6): Collection
    {
        return ExamAttempt::with(['exam', 'student'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(fn ($attempt) => [
                'date' => $attempt->created_at ? $attempt->created_at->format('Y-m-d H:i') : '-',
                'exam' => $attempt->exam->display_title ?? $attempt->exam->title,
                'student' => $attempt->student->name ?? '-',
                'score' => $attempt->score_percent ?? ($attempt->passed ? 'Pass' : 'Fail'),
            ]);
    }

    private function recentQuestionActivity(int $limit = 6): Collection
    {
        return ExamQuestion::with('owner')
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->orderBy('submitted_at', 'desc')
            ->take($limit)
            ->get()
            ->map(fn ($question) => [
                'title' => Str::limit($question->question, 60),
                'status' => $question->status,
                'owner' => $question->owner->name ?? '-',
                'submitted_at' => $question->submitted_at ? $question->submitted_at->format('Y-m-d') : '-',
            ]);
    }

    private function chartData(): array
    {
        $trend = $this->registrationTrend();

        return [
            'labels' => $trend->pluck('date')->map(fn (string $date) => Carbon::parse($date)->format('M Y'))->toArray(),
            'series' => $trend->pluck('total')->toArray(),
        ];
    }

    private function chartJson(): string
    {
        return json_encode($this->chartData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
