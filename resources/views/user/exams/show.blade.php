@section('student_shell', true)
@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:900px">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card border border-light border-opacity-10 p-4 p-md-5 text-white" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
            <div><h1 class="h2 gradient-text fw-bold">{{ $exam->display_title }}</h1><span class="badge bg-success">Published</span></div>
            <a class="btn btn-outline-light" href="{{ route('student.exams.index') }}">All Exams</a>
        </div>
        <dl class="row">
            <dt class="col-sm-5 text-white-50">Questions</dt><dd class="col-sm-7">{{ $exam->questions()->where('status', 'approved')->count() }}</dd>
            <dt class="col-sm-5 text-white-50">Duration</dt><dd class="col-sm-7">{{ $exam->duration_minutes }} minutes</dd>
            <dt class="col-sm-5 text-white-50">Availability</dt><dd class="col-sm-7">Available</dd>
        </dl>
    
        @if($attempt)
            <div class="alert alert-info">You already have an attempt: <strong>{{ $attempt->status === 'in_progress' ? 'In Progress' : ($attempt->status === 'submitted' ? 'Completed' : 'Expired') }}</strong>.</div>
            <a class="btn btn-outline-light align-self-start" href="{{ route('student.exams.attempts.show', $attempt) }}">{{ $attempt->status === 'in_progress' ? 'Open Attempt' : 'View Attempt' }}</a>
        @else
            <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                @csrf<button class="btn btn-outline-light align-self-start" type="submit">Start Exam</button>
            </form>
        @endif
    </div> 
</div>
@endsection
