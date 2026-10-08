@section('student_shell', true)
@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <h1 class="gradient-text fw-bold">Available Exams</h1>
        <p class="text-white-50">Published exams available for you.</p>
    </div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        @forelse($exams as $exam)
            @php($attempt = $exam->attempts->first())
            <div class="col-12 col-md-6 col-xl-4">   
                <div class="card h-100 p-4 border border-light border-opacity-10" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px;color:#fff">
                    <div class="d-flex justify-content-between gap-2 mb-3">
                        <h2 class="h5 fw-bold mb-0">{{ $exam->title }}</h2>
                        <span class="badge bg-success align-self-start">Published</span>
                    </div>
                    <p class="text-white-50 mb-2">Questions: 25</p>
                    <p class="text-white-50 mb-3">Duration: {{ $exam->duration_minutes }} minutes</p>

                    @if($attempt)
                        <p class="mb-3">Attempt status:
                            <strong>{{ $attempt->status === 'in_progress' ? 'In Progress' : ($attempt->status === 'submitted' ? 'Completed' : 'Expired') }}</strong>
                        </p>
                        <a class="btn btn-outline-light mt-auto" href="{{ route('student.exams.attempts.show', $attempt) }}">
                            {{ $attempt->status === 'in_progress' ? 'Open Attempt' : 'View Attempt' }}
                        </a>
                    @else
                        <div class="d-flex gap-2 mt-auto flex-wrap">
                            <a class="btn btn-outline-light" href="{{ route('student.exams.show', $exam) }}">View Exam</a>
                            <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                                @csrf<button class="btn btn-primary" type="submit">Start Exam</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card border border-light border-opacity-10 p-4 text-white-50" style="background:rgba(255,255,255,.04);border-radius:18px">No published exams are available right now.</div></div>
        @endforelse
    </div>
</div>
@endsection
