@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Exam result: sakura stat tiles + glass review rows */
.sk-dash main .sk-tile{
  background:linear-gradient(150deg, rgba(168,85,247,.14), rgba(236,72,153,.10)) !important;
  border:1px solid rgba(192,132,252,.30); border-radius:16px;
  box-shadow:0 10px 26px rgba(4,2,16,.40), 0 0 16px rgba(139,92,246,.12);
}
.sk-dash main .sk-review-item{
  background:rgba(255,255,255,.04) !important;
  border:1px solid var(--sk-line) !important; border-radius:14px;
}
</style>
@endpush

@section('content')
<div class="container py-5" style="max-width:1000px">
    <div class="card border border-light border-opacity-10 p-4 p-md-5 text-white" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
            <div><h1 class="h2 gradient-text fw-bold">{{ $attempt->exam->title }} — Results</h1><span class="badge bg-{{ $attempt->status === 'submitted' ? 'success' : 'secondary' }}">{{ $attempt->status === 'submitted' ? 'Submitted' : 'Time expired' }}</span></div>
            <a class="btn btn-outline-light" href="{{ route('student.exams.index') }}">All Exams</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="sk-tile rounded-3 p-3 h-100" style="background:rgba(255,255,255,.05)"><div class="text-white-50">Score</div><div class="fs-3 fw-bold">{{ number_format((float) $attempt->score_percent, 2) }}%</div></div></div>
            <div class="col-md-4"><div class="sk-tile rounded-3 p-3 h-100" style="background:rgba(255,255,255,.05)"><div class="text-white-50">Correct answers</div><div class="fs-3 fw-bold">{{ $attempt->correct_count }}/25</div></div></div>
            <div class="col-md-4"><div class="sk-tile rounded-3 p-3 h-100" style="background:rgba(255,255,255,.05)"><div class="text-white-50">Result</div><div class="fs-3 fw-bold text-{{ $attempt->passed ? 'success' : 'danger' }}">{{ $attempt->passed ? 'PASS' : 'NOT PASSED' }}</div><div class="small text-white-50">Pass mark: 60%</div></div></div>
        </div>
        <p class="text-white-50">Submitted {{ $attempt->submitted_at?->format('M j, Y g:i A') }}</p>

        <h2 class="h4 mt-2 mb-3">Answer review</h2>
        <div class="d-grid gap-3">
            @foreach($attempt->questions as $question)
                @php
                    $labels = ['a' => $question->option_a, 'b' => $question->option_b, 'c' => $question->option_c, 'd' => $question->option_d];
                @endphp
                <article class="sk-review-item rounded-3 p-3 border border-light border-opacity-10" style="background:rgba(255,255,255,.04)">
                    <div class="d-flex justify-content-between gap-2"><h3 class="h6">{{ $question->question_order }}. {{ $question->question }}</h3><span class="badge bg-{{ $question->is_correct ? 'success' : 'danger' }}">{{ $question->is_correct ? 'Correct' : 'Incorrect' }}</span></div>
                    <div class="small text-white-50">Your answer: {{ $question->selected_option ? strtoupper($question->selected_option).' — '.$labels[$question->selected_option] : 'Not answered' }}</div>
                    <div class="small text-white-50">Correct answer: {{ strtoupper($question->correct_option) }} — {{ $labels[$question->correct_option] }}</div>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endsection
