@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Exam attempt: focused question area, sakura answer choices, gradient timer */
.sk-dash main .sk-q-area{
  background:rgba(6,3,16,.50) !important; border:1px solid var(--sk-line);
  border-radius:16px; box-shadow:inset 0 1px 0 rgba(255,255,255,.04);
}
.sk-dash main #answer-form label{
  background:rgba(255,255,255,.04); border:1px solid rgba(192,132,252,.25);
  border-radius:14px; transition:all .18s ease;
}
.sk-dash main #answer-form label:hover{border-color:rgba(216,180,254,.50); background:rgba(168,85,247,.08);}
.sk-dash main #answer-form label:has(input:checked){
  background:linear-gradient(135deg, rgba(168,85,247,.30), rgba(236,72,153,.22)) !important;
  border-color:rgba(216,180,254,.65) !important;
  box-shadow:0 0 16px rgba(168,85,247,.28);
}
.sk-dash main #exam-timer{
  background:linear-gradient(120deg,#e9d5ff,#f9a8d4);
  -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
}
</style>
@endpush

@section('content')
<div class="container py-5" style="max-width:1000px">
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card border border-light border-opacity-10 p-4 p-md-5 text-white" style="background:rgba(255,255,255,.04);backdrop-filter:blur(14px);border-radius:18px">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
            <div><h1 class="h2 gradient-text fw-bold mb-1">{{ $attempt->exam->title }}</h1><div class="text-white-50">Question {{ $currentQuestion->question_order }} of 25</div></div>
            <div class="text-end"><div class="small text-white-50">Time remaining</div><div class="fs-4 fw-bold" id="exam-timer" role="timer" aria-live="off">--:--</div></div>
        </div>

        <div class="progress mb-2" style="height:8px;background:rgba(255,255,255,.12)"><div class="progress-bar" style="width:{{ ($answeredCount / 25) * 100 }}%"></div></div>
        <div class="d-flex justify-content-between small text-white-50 mb-4"><span>Answered: <span id="answered-count">{{ $answeredCount }}</span>/25</span><span>Pass mark: 60%</span></div>

        <section class="sk-q-area rounded-3 p-4 mb-4" style="background:rgba(255,255,255,.05)">
            <h2 class="h4 mb-4">{{ $currentQuestion->question }}</h2>
            <form id="answer-form" method="POST" action="{{ route('student.exams.attempts.answer', $attempt) }}">
                @csrf
                <input type="hidden" name="question_id" value="{{ $currentQuestion->id }}">
                <div class="d-grid gap-3">
                    @foreach(['a' => $currentQuestion->option_a, 'b' => $currentQuestion->option_b, 'c' => $currentQuestion->option_c, 'd' => $currentQuestion->option_d] as $letter => $option)
                        <label class="d-flex align-items-center gap-3 rounded-3 border border-light border-opacity-25 p-3" style="cursor:pointer;background:{{ $currentQuestion->selected_option === $letter ? 'rgba(130,80,220,.22)' : 'rgba(255,255,255,.03)' }}">
                            <input class="form-check-input m-0" type="radio" name="selected_option" value="{{ $letter }}" @checked($currentQuestion->selected_option === $letter)>
                            <span class="badge rounded-pill bg-light text-dark text-uppercase">{{ $letter }}</span><span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary mt-3" type="submit">Save Answer</button>
            </form>
        </section>

        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-4">
            @if($currentQuestion->question_order > 1)
                <a class="btn btn-outline-light" href="{{ route('student.exams.attempts.show', ['attempt' => $attempt, 'question' => $currentQuestion->question_order - 1]) }}">Previous</a>
            @else <span></span> @endif
            @if($currentQuestion->question_order < 25)
                <a class="btn btn-outline-light" href="{{ route('student.exams.attempts.show', ['attempt' => $attempt, 'question' => $currentQuestion->question_order + 1]) }}">Next</a>
            @endif
        </div>

        <nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Question navigation">
            @foreach($questions as $question)
                <a class="btn btn-sm {{ $question->id === $currentQuestion->id ? 'btn-primary' : ($question->selected_option !== null ? 'btn-success' : 'btn-outline-light') }}" aria-label="Question {{ $question->question_order }}{{ $question->selected_option !== null ? ', answered' : ', unanswered' }}" href="{{ route('student.exams.attempts.show', ['attempt' => $attempt, 'question' => $question->question_order]) }}">{{ $question->question_order }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('student.exams.attempts.submit', $attempt) }}" onsubmit="return confirm('Submit your exam now? You cannot change your answers afterward.');">
            @csrf
            <button class="btn btn-outline-light" type="submit">Submit Exam</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const expiry = @json($expiresAtMs);
    const serverNow = @json($serverNowMs);
    const startedAt = performance.now();
    const timer = document.getElementById('exam-timer');
    let submitted = false;
    const submitForm = document.createElement('form');
    submitForm.method = 'POST';
    submitForm.action = @json(route('student.exams.attempts.submit', $attempt));
    submitForm.innerHTML = @json(csrf_field());
    document.body.appendChild(submitForm);

    function updateTimer() {
        const remaining = Math.max(0, expiry - (serverNow + (performance.now() - startedAt)));
        const seconds = Math.ceil(remaining / 1000);
        timer.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
        if (seconds <= 0 && !submitted) {
            submitted = true;
            submitForm.submit();
        }
    }
    updateTimer();
    window.setInterval(updateTimer, 500);

    document.querySelectorAll('#answer-form input[type="radio"]').forEach((choice) => {
        choice.addEventListener('change', () => document.getElementById('answer-form').requestSubmit());
    });
});
</script>
@endsection
