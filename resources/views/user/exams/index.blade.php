@section('student_shell', true)
@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <h1 class="gradient-text fw-bold">Available Exams</h1>
        <p class="text-white-50">Published exams available for you.</p>
    </div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @unless($trainingComplete)
        <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 flex-wrap" role="alert">
            <span>
                <i class="bi bi-lock-fill me-1"></i>
                Complete this level’s Training to unlock its Exams.
            </span>
            @if($level)
                <a class="btn btn-sm btn-dark" href="{{ route('levels.courses', $level->id) }}">Go to Training</a>
            @endif
        </div>
    @endunless

    <div class="row g-4">
        @forelse($exams as $exam)
            @php($attempt = $exam->attempts->first())
            <div class="col-12 col-md-6 col-xl-4">   
                <div class="card h-100 p-4 border border-light border-opacity-10" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px;color:#fff">
                    <div class="d-flex justify-content-between gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0">{{ $exam->display_title }}</h2>
                        <div class="d-flex gap-2 align-self-start flex-wrap justify-content-end">
                            <span class="badge bg-success">Published</span>
                            @if(! $attempt && ! in_array($exam->id, $startableExamIds))
                                <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-white-50 mb-2">Questions: {{ $exam->questions()->where('status', 'approved')->count() }}</p>
                    <p class="text-white-50 mb-3">Duration: {{ $exam->duration_minutes }} minutes</p>

                    @if($attempt)
                        <p class="mb-3">Attempt status:
                            <strong>{{ $attempt->status === 'in_progress' ? 'In Progress' : ($attempt->status === 'submitted' ? 'Completed' : 'Expired') }}</strong>
                        </p>
                        <a class="btn btn-outline-light mt-auto" href="{{ route('student.exams.attempts.show', $attempt) }}">
                            {{ $attempt->status === 'in_progress' ? 'Open Attempt' : 'View Attempt' }}
                        </a>
                    @elseif(in_array($exam->id, $startableExamIds))
                        <div class="d-flex gap-2 mt-auto flex-wrap">
                            <a class="btn btn-outline-light" href="{{ route('student.exams.show', $exam) }}">View Exam</a>
                            <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                                @csrf<button class="btn btn-primary" type="submit">Start Exam</button>
                            </form>
                        </div>
                    @else
                        <div class="d-flex gap-2 mt-auto flex-wrap align-items-center">
                            <button class="btn btn-outline-secondary" type="button" disabled aria-disabled="true">Start Exam</button>
                        </div>
                        <p class="text-white-50 small mt-2 mb-0"><i class="bi bi-lock-fill me-1"></i>Pass the previous exam to unlock.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card border border-light border-opacity-10 p-4 text-white-50" style="background:rgba(255,255,255,.04);border-radius:18px">No published exams are available right now.</div></div>
        @endforelse
    </div>

    @if($completedExams->isNotEmpty())
        <div class="mt-5">
            <div class="mb-3">
                <h2 class="h5 fw-bold mb-0">Completed Exams</h2>
                <p class="text-white-50 mb-0">Exams you have passed. These are no longer offered again.</p>
            </div>
            <div class="row g-4">
                @foreach($completedExams as $exam)
                    @php($attempt = $exam->attempts->first())
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100 p-4 border border-light border-opacity-10" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px;color:#fff">
                            <div class="d-flex justify-content-between gap-2 mb-3">
                                <h3 class="h5 fw-bold mb-0">{{ $exam->display_title }}</h3>
                                <span class="badge bg-info align-self-start">Completed</span>
                            </div>
                            <p class="text-white-50 mb-2">Questions: {{ $exam->questions()->where('status', 'approved')->count() }}</p>
                            <p class="text-white-50 mb-3">Duration: {{ $exam->duration_minutes }} minutes</p>
                            @if($attempt)
                                <a class="btn btn-outline-light mt-auto" href="{{ route('student.exams.attempts.show', $attempt) }}">View Result</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
