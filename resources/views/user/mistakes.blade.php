@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Review Mistakes: glass cards, focused answer zone, sakura option controls */
.sk-dash main .mistake-question{box-shadow:0 14px 36px rgba(4,2,16,.5), 0 0 20px rgba(139,92,246,.10);}
.sk-dash main .answer-zone{
  background:rgba(6,3,16,.55) !important; border-color:rgba(192,132,252,.25) !important;
  border-radius:16px; min-height:70px;
}
.sk-dash main .option-btn{
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.35); color:#e9d5ff;
  border-radius:14px;
}
.sk-dash main .option-btn:hover{background:rgba(168,85,247,.18); border-color:rgba(216,180,254,.55); color:#fff;}
.sk-dash main .image-option-card{transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;}
.sk-dash main .image-option-card:hover{transform:translateY(-3px); box-shadow:0 14px 32px rgba(4,2,16,.5);}
.sk-dash main .image-option-card.is-selected,
.sk-dash main .image-option-card.border-primary{
  border-color:#c084fc !important; background:rgba(168,85,247,.14) !important;
  box-shadow:0 0 18px rgba(168,85,247,.40);
}
.sk-dash main .mistake-check:not(:disabled){box-shadow:0 8px 22px rgba(147,51,234,.35);}
</style>
@endpush

@section('content')
<div class="container py-5 text-white" style="max-width: 900px; min-height: 65vh;" id="mistake-review">
    <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Review Mistakes</h1>
            <p class="text-light opacity-75 mb-0">Answer a saved question correctly to clear it from this list.</p>
        </div>
        <a href="{{ route('select.level') }}" class="btn btn-outline-light rounded-pill">Back to learning</a>
    </div>

    @if(session('review_feedback.correct'))
        <div class="alert alert-success">Correct. The mistake has been cleared from your review list.</div>
    @endif

    <div class="row g-3">
        @forelse($mistakes as $mistake)
            @php
                $vocabulary = $mistake['vocabulary'];
                $feedback = session('review_feedback');
                $optionList = $vocabulary->options ? array_map('trim', explode(',', $vocabulary->options)) : [];
                $optionImages = json_decode($vocabulary->image_url ?? '', true);
                $optionImages = is_array($optionImages) ? $optionImages : [];
            @endphp
            <div class="col-12">
                <div class="card bg-dark text-white border-secondary p-4 shadow-lg rounded-4 mistake-question"
                     data-question-type="{{ $vocabulary->question_type }}">
                    <div class="small text-info mb-2">{{ $vocabulary->lesson?->title ?? 'Lesson' }}</div>

                    @if(($feedback['vocabulary_id'] ?? null) === $vocabulary->id && !($feedback['correct'] ?? false))
                        <div class="alert alert-danger">Not quite. Try again.</div>
                    @endif

                    <form method="POST" action="{{ route('student.mistakes.answer', $vocabulary->id) }}">
                        @csrf
                        <input type="hidden" name="answer" value="" class="mistake-answer">

                        @if($vocabulary->question_type === 'image_selection')
                            <div class="mb-4 text-center">
                                <h2 class="h4 text-white fw-bold">{{ $vocabulary->question }}</h2>
                            </div>
                            <div class="row justify-content-center mb-4 g-3">
                                @foreach($optionList as $index => $option)
                                    @php
                                        $imagePath = $optionImages[$option] ?? null;
                                        $imageSrc = $imagePath
                                            ? (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : asset('storage/' . ltrim($imagePath, '/')))
                                            : null;
                                    @endphp
                                    <div class="col-12 col-sm-6 col-lg-4">
                                        <div class="card bg-dark text-white border border-secondary rounded-4 p-2 p-sm-3 text-center option-card-item image-option-card shadow-sm h-100"
                                             style="cursor: pointer;" data-option="{{ $option }}" role="button" tabindex="0"
                                             aria-label="Choose {{ $option }}">
                                            <div class="mb-3 p-2 bg-black bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center image-option-frame">
                                                @if($imageSrc)
                                                    <img src="{{ $imageSrc }}" alt="{{ $option }}" class="img-fluid image-option-img" loading="lazy">
                                                @else
                                                    <span class="text-muted small">Image unavailable</span>
                                                @endif
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center px-2">
                                                <span class="text-info fw-bold fs-5">{{ $option }}</span>
                                                <span class="badge bg-secondary text-white rounded-circle px-2 py-1">{{ $index + 1 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="d-flex align-items-center bg-secondary bg-opacity-25 p-3 rounded-4 mb-4 border border-secondary">
                                <button type="button" class="btn btn-primary rounded-circle me-3 p-3 d-flex align-items-center justify-content-center shadow"
                                        style="width: 50px; height: 50px;" data-pronounce="{{ $vocabulary->question }}" aria-label="Play pronunciation">🔊</button>
                                <div>
                                    <div class="fs-3 fw-bold text-warning mb-0">{{ $vocabulary->question }}</div>
                                    @if($vocabulary->question_type === 'translation_tiles')
                                        <small class="text-muted">Make the English sentence:</small>
                                    @else
                                        <small class="text-muted">Select the words in order:</small>
                                    @endif
                                </div>
                            </div>

                            <div class="answer-zone p-3 bg-black bg-opacity-50 rounded-4 mb-4 border border-secondary d-flex flex-wrap gap-2 align-items-center" style="min-height: 70px;">
                                <span class="text-muted placeholder-text">Select words below...</span>
                            </div>
                            <div class="options-pool d-flex flex-wrap gap-2 mb-4">
                                @foreach($optionList as $option)
                                    <button type="button"
                                            class="btn btn-outline-light option-btn {{ $vocabulary->question_type === 'translation_tiles' ? 'translation-tile' : '' }} py-2 px-3 rounded-3 shadow-sm"
                                            data-text="{{ $option }}">{{ $option }}</button>
                                @endforeach
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary rounded-pill px-4 mistake-check" disabled>CHECK</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="fs-1">🎉</div>
                <p class="text-light opacity-75 mb-0">No questions need review right now.</p>
            </div>
        @endforelse
    </div>
</div>

<script>
(function () {
    'use strict';

    document.querySelectorAll('#mistake-review .mistake-question').forEach(function (card) {
        const form = card.querySelector('form');
        const answerField = card.querySelector('.mistake-answer');
        const checkButton = card.querySelector('.mistake-check');
        const answerZone = card.querySelector('.answer-zone');
        const optionsPool = card.querySelector('.options-pool');
        const questionType = card.dataset.questionType;

        function updateAnswer() {
            if (questionType === 'image_selection') {
                answerField.value = card.dataset.selected || '';
            } else {
                answerField.value = Array.from(answerZone.querySelectorAll('.selected-chip'))
                    .map(function (chip) { return chip.textContent.trim(); })
                    .join(questionType === 'translation_tiles' ? ' ' : ' ');
            }
            checkButton.disabled = !answerField.value.trim();
        }

        function addTile(button) {
            if (!answerZone || button.disabled || button.hidden) return;
            const placeholder = answerZone.querySelector('.placeholder-text');
            if (placeholder) placeholder.remove();

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-light text-dark py-2 px-3 rounded-3 selected-chip shadow-sm';
            chip.textContent = button.dataset.text || button.textContent.trim();
            chip.addEventListener('click', function () {
                button.hidden = false;
                button.disabled = false;
                button.style.display = '';
                chip.remove();
                if (!answerZone.querySelector('.selected-chip')) {
                    answerZone.innerHTML = '<span class="text-muted placeholder-text">Select words below...</span>';
                }
                updateAnswer();
            });
            answerZone.appendChild(chip);
            button.hidden = true;
            button.disabled = true;
            button.style.display = 'none';
            updateAnswer();
        }

        if (optionsPool) {
            optionsPool.querySelectorAll('.option-btn').forEach(function (button) {
                button.addEventListener('click', function () { addTile(button); });
            });
        }

        card.querySelectorAll('.option-card-item').forEach(function (option) {
            function selectOption() {
                card.querySelectorAll('.option-card-item').forEach(function (item) {
                    item.classList.remove('border-primary', 'bg-secondary', 'bg-opacity-25', 'is-selected');
                });
                option.classList.add('border-primary', 'bg-secondary', 'bg-opacity-25', 'is-selected');
                card.dataset.selected = option.dataset.option || '';
                updateAnswer();
            }
            option.addEventListener('click', selectOption);
            option.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectOption();
                }
            });
        });

        card.querySelectorAll('[data-pronounce]').forEach(function (button) {
            button.addEventListener('click', function () {
                const text = button.dataset.pronounce || '';
                if (!text || !('speechSynthesis' in window)) return;
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'ja-JP';
                window.speechSynthesis.speak(utterance);
            });
        });

        form.addEventListener('submit', function (event) {
            updateAnswer();
            if (!answerField.value.trim()) event.preventDefault();
        });
    });
})();
</script>
@endsection
