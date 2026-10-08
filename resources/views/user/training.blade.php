@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Training: focused learning workspace — dashboard-matched answer controls, subtle hearts */
.sk-hearts-pill{
  display:inline-flex; align-items:center; gap:6px;
  padding:5px 14px; border-radius:999px; font-weight:700; font-size:1rem;
  color:#fda4af !important;
  background:rgba(244,114,182,.10); border:1px solid rgba(244,114,182,.35);
  box-shadow:0 0 14px rgba(244,114,182,.18);
}
.sk-dash main .skip-btn{
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.35); color:#e9d5ff !important;
}
.sk-dash main .skip-btn:hover{background:rgba(168,85,247,.15); border-color:rgba(216,180,254,.55); color:#fff !important;}
.sk-dash main .check-btn:not(:disabled){
  background:linear-gradient(120deg,#9333ea,#db2777); border:1px solid rgba(250,205,255,.35);
  color:#fff !important; box-shadow:0 8px 22px rgba(147,51,234,.35), 0 0 14px rgba(236,72,153,.22);
}
.sk-dash main .check-btn:not(:disabled):hover{transform:translateY(-2px); color:#fff !important;}
.sk-dash main .answer-zone{
  background:rgba(6,3,16,.55) !important; border-color:rgba(192,132,252,.25) !important;
  border-radius:16px; min-height:70px;
}
.sk-dash main .option-btn{
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.35); color:#e9d5ff;
  border-radius:14px;
}
.sk-dash main .option-btn:hover{background:rgba(168,85,247,.18); border-color:#c084fc !important; color:#fff;}
.sk-dash main .image-option-card.is-selected{
  border-color:#c084fc !important; background-color:rgba(168,85,247,.16) !important;
  box-shadow:0 0 18px rgba(168,85,247,.40) !important;
}
.sk-dash main .btn-secondary{
  background:linear-gradient(120deg,#9333ea,#db2777); border:1px solid rgba(250,205,255,.30); color:#fff !important;
}
.sk-dash main .btn-outline-secondary{
  border-color:rgba(192,132,252,.40); color:#e9d5ff !important; background:rgba(255,255,255,.05);
}
.sk-dash main .btn-danger{
  background:linear-gradient(120deg,#e11d48,#be123c); border:1px solid rgba(252,165,165,.35); color:#fff !important;
}
</style>
@endpush

@section('content')
<div class="container py-5 text-white" style="max-width: 700px;" id="training-session"
     data-hearts="{{ $lessonState['hearts'] }}"
     data-session-ended="{{ $lessonState['session_ended'] ? '1' : '0' }}"
     data-lesson-completed="{{ $lessonState['lesson_completed'] ? '1' : '0' }}">
    <!-- Top Progress Bar, Hearts & Header (Duolingo Gaming Style) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <button type="button" class="text-secondary text-decoration-none fs-4 bg-transparent border-0" data-bs-toggle="modal" data-bs-target="#leaveLessonModal" aria-label="Leave lesson">✕</button>
        
        <!-- Progress Bar -->
        <div class="progress flex-grow-1 mx-3 bg-secondary" style="height: 10px; border-radius: 5px;">
            @php
                $progressTotal = $isTeaching ? $learningItems->count() : count($lesson->vocabularies);
                $progressCurrent = $isTeaching ? (int) ($lessonState['learning_item_index'] ?? 0) : (int) $lessonState['next_index'];
            @endphp
            <div id="progress-bar" class="progress-bar training-progress-fill" style="width: {{ $progressTotal ? min(100, ($progressCurrent / $progressTotal) * 100) : 0 }}%; transition: width 0.4s ease;"></div>
        </div>
        <span class="small text-light text-nowrap" id="question-counter">@if($isTeaching) Vocabulary {{ min($lessonState['learning_item_index'] + 1, $learningItems->count()) }} / {{ $learningItems->count() }}@else Question {{ min($lessonState['next_index'] + 1, count($lesson->vocabularies)) }} / {{ count($lesson->vocabularies) }}@endif</span>
        
        <!-- Hearts Counter -->
        <div class="sk-hearts-pill fw-bold fs-5">
            ❤️ <span id="heart-count">{{ $lessonState['hearts'] }}</span>
        </div>
    </div>

    @if($isTeaching && $currentLearningItem)
        <div class="card bg-dark text-white border-secondary p-4 p-sm-5 shadow-lg mb-4 rounded-4">
            <div class="text-uppercase small fw-bold text-info mb-3">LEARN · Vocabulary {{ $lessonState['learning_item_index'] + 1 }} / {{ $learningItems->count() }}</div>
            @if($currentLearningItem->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentLearningItem->image_path))
                <div class="text-center mb-4">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentLearningItem->image_path) }}" alt="{{ $currentLearningItem->japanese }}" class="img-fluid rounded-3" style="max-height: 220px; object-fit: contain;">
                </div>
            @endif
            <h1 class="text-center fw-bold text-warning mb-3">{{ $currentLearningItem->japanese }}</h1>
            <p class="text-center fs-4 mb-2">{{ $currentLearningItem->reading }}</p>
            <p class="text-center text-light fs-5 mb-4">{{ $currentLearningItem->english }}</p>
            <div class="text-center mb-4">
                <button type="button" class="btn btn-outline-light rounded-circle" data-pronounce="{{ $currentLearningItem->japanese }}" aria-label="Hear Japanese pronunciation">🔊</button>
            </div>
            @if($currentLearningItem->audio_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($currentLearningItem->audio_path))
                <div class="text-center mb-4">
                    <audio controls preload="none" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentLearningItem->audio_path) }}">Audio playback is not supported by this browser.</audio>
                </div>
            @endif
            <form method="POST" action="{{ route('user.lesson.learning.complete', ['lesson' => $lesson->id, 'itemId' => $currentLearningItem->id]) }}" class="d-grid mt-2">
                @csrf
                <button type="submit" class="btn btn-primary rounded-pill py-3 fw-bold">Got it →</button>
            </form>
        </div>
    @else
    @forelse($lesson->vocabularies as $index =>$vocabulary)
        <div class="card bg-dark text-white border-secondary p-4 shadow-lg mb-4 question-card {{ $index !== $lessonState['next_index'] ? 'd-none' : '' }}" 
             id="question-box-{{ $index }}" 
             data-index="{{ $index }}"
             data-vocabulary-id="{{ $vocabulary->id }}"
             data-question-type="{{ $vocabulary->question_type }}"
             data-correct="{{ trim($vocabulary->correct_answer) }}">

            @if(auth()->user()->role === 'student')
                <form method="POST" action="{{ route('student.bookmarks.toggle', $vocabulary->id) }}" class="text-end mb-3">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill">
                        {{ in_array((int) $vocabulary->id, $bookmarkedVocabularyIds, true) ? '★ Remove bookmark' : '☆ Bookmark' }}
                    </button>
                </form>
            @endif
            
            <!-- 1. IMAGE SELECTION TYPE (ပုံရွေးချယ်ခိုင်းသည့်အခါ အသံခလုတ်နှင့် hover လုံးဝမပါစေရန်) -->
            @if($vocabulary->question_type == 'image_selection')
                <div class="mb-4 text-center">
                    <h3 class="text-white fw-bold">{{ $vocabulary->question }}</h3>
                </div>

                <div class="row justify-content-center mb-4 g-3">
                    @php
                        $optionList = $vocabulary->options ? explode(',', $vocabulary->options) : [];
                        $optionImages = json_decode($vocabulary->image_url ?? '', true);
                        $optionImages = is_array($optionImages) ? $optionImages : [];
                    @endphp

                    @foreach($optionList as $optIndex =>$option)
                        @php
                            $option = trim($option);
                            $imagePath = $optionImages[$option] ?? null;
                            $imageSrc = $imagePath
                                ? (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://']) ? $imagePath : asset('storage/' . ltrim($imagePath, '/')))
                                : null;
                        @endphp
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card bg-dark text-white border border-secondary rounded-4 p-2 p-sm-3 text-center option-card-item image-option-card shadow-sm h-100"
                                 style="cursor: pointer;"
                                 data-option="{{ $option }}"
                                 role="button" tabindex="0" aria-label="Choose {{ $option }}">
                                
                                <!-- ပုံပြမည့်နေရာ -->
                                <div class="mb-3 p-2 bg-black bg-opacity-25 rounded-3 d-flex align-items-center justify-content-center image-option-frame">
                                    @if($imageSrc)
                                        <img src="{{ $imageSrc }}"
                                             alt="{{ $option }}"
                                             class="img-fluid image-option-img"
                                             loading="lazy">
                                    @else
                                        <span class="text-muted small">Image unavailable</span>
                                    @endif
                                </div>

                                <!-- အောက်ခြေမှ စာသားနှင့် နံပါတ်ကတ်ပြား -->
                                <div class="d-flex justify-content-between align-items-center px-2">
                                    <span class="text-info fw-bold fs-5">{{ trim($option) }}</span>
                                    <span class="badge bg-secondary text-white rounded-circle px-2 py-1">{{ $optIndex + 1 }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- 2. အခြား မေးခွန်းအမျိုးအစားများ (စကားလုံးတစ်လုံးချင်းစီ ခွဲထုတ်ပေးသည့် ပုံစံအဟောင်းအတိုင်း ပြန်လည်ထည့်သွင်းထားသည်) -->
                <div class="d-flex align-items-center bg-secondary bg-opacity-25 p-3 rounded-4 mb-4 border border-secondary">
                    <button type="button" class="btn btn-primary rounded-circle me-3 p-3 d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px;" data-pronounce="{{ $vocabulary->question }}" aria-label="Play pronunciation">
                        🔊
                    </button>
                    <div>
                        <div class="japanese-sentence fs-3 fw-bold text-warning mb-0" id="sentence-container-{{ $index }}" data-question="{{ $vocabulary->question }}">
                            <!-- JavaScript ဖြင့် ဂျပန်စကားလုံးများကို အဓိပ္ပာယ်နှင့်တကွ ခွဲထုတ်ပေးမည် -->
                        </div>
                        @if($vocabulary->question_type === 'translation_tiles')
                            <small class="text-muted">Make the English sentence:</small>
                        @else
                            <small class="text-muted">Hover over individual Japanese words for audio & English meaning</small>
                        @endif
                    </div>
                </div>

                <!-- Answer Dropzone -->
                <div class="answer-zone p-3 bg-black bg-opacity-50 rounded-4 mb-4 border border-secondary d-flex flex-wrap gap-2 align-items-center" style="min-height: 70px;" id="answer-zone-{{ $index }}" data-question-index="{{ $index }}">
                    <span class="text-muted placeholder-text">Select words below...</span>
                </div>

                <!-- Options Pool -->
                <div class="d-flex flex-wrap gap-2 mb-4" id="options-pool-{{ $index }}">
                    @if($vocabulary->options)
                        @php
                            $answerOptions = explode(',', $vocabulary->options);
                            if ($vocabulary->question_type === 'translation_tiles') {
                                shuffle($answerOptions);
                            }
                        @endphp
                        @foreach($answerOptions as $option)
                            <button type="button"
                                    class="btn btn-outline-light option-btn {{ $vocabulary->question_type === 'translation_tiles' ? 'translation-tile' : '' }} py-2 px-3 rounded-3 shadow-sm"
                                    data-text="{{ trim($option) }}"
                                    @if($vocabulary->question_type === 'translation_tiles')
                                        data-tile-index="{{ $loop->index }}"
                                    @endif>
                                {{ trim($option) }}
                            </button>
                        @endforeach
                    @endif
                </div>
            @endif

            @if(($vocabulary->learning_stage ?? null) === 'listening' && $vocabulary->learning_audio_path)
                <div class="text-center mb-4">
                    <audio controls preload="none" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($vocabulary->learning_audio_path) }}">Audio playback is not supported by this browser.</audio>
                </div>
            @endif

            <!-- Result Feedback Alert -->
            <div id="feedback-{{ $index }}" class="alert d-none mb-3"></div>

            <!-- Bottom Action Bar -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-pill skip-btn" data-action="skip" data-question-index="{{ $index }}">SKIP</button>
                <button type="button" class="btn btn-secondary px-5 py-2 rounded-pill check-btn" id="check-btn-{{ $index }}" data-action="check" data-question-index="{{ $index }}" disabled>CHECK</button>
            </div>

        </div>
    @empty
        <div class="text-center text-muted py-5">
            <h4>No training questions found for this lesson.</h4>
        </div>
    @endforelse
    @endif
</div>

<!-- Gaming Congratulation Modal -->
<div class="modal fade" id="congratsModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-success shadow-lg text-center p-4 rounded-4" style="border: 3px solid #28a745;">
            <div class="modal-body">
                <div style="font-size: 60px;" class="mb-3">🏆🎉</div>
                <h2 class="text-success fw-bold mb-3">AMAZING JOB!</h2>
                <p class="text-light fs-5 mb-4">You have successfully completed this lesson with gaming precision!</p>
                <div class="rounded-4 border border-warning px-4 py-3 mb-4 d-inline-block">
                    <div class="text-warning fs-3 fw-bold">🔥 +<span id="fire-awarded">0</span></div>
                    <div class="text-light">Streak: <span id="fire-total">0</span></div>
                    <div class="text-info fs-5 fw-bold">💎 +<span id="gem-awarded">0</span> Gems</div>
                </div>
                <div class="d-grid">
                    <a href="{{ route('levels.courses', $lesson->level_id) }}" class="btn btn-success btn-lg rounded-pill py-3 fw-bold shadow">CLAIM REWARD & CONTINUE 🚀</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveLessonModal" tabindex="-1" aria-labelledby="leaveLessonTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-secondary rounded-4 shadow-lg">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold" id="leaveLessonTitle">Pause this lesson?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Stay in lesson"></button>
            </div>
            <div class="modal-body">Your progress will be saved. You can continue from where you left off anytime.</div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Stay</button>
                <a href="{{ route('levels.courses', $lesson->level_id) }}" class="btn btn-danger rounded-pill px-4" data-end-training>Pause &amp; Exit</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sessionEndedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="sessionEndedTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border-danger rounded-4 shadow-lg text-center p-3">
            <div class="modal-body">
                <div class="fs-1 mb-2">💔</div>
                <h2 class="text-danger fw-bold" id="sessionEndedTitle">Session ended</h2>
                <p class="text-light" id="sessionEndedMessage">You are out of hearts. Your lesson was not completed.</p>
                @if(session('gem_refill_error'))
                    <div class="alert alert-warning mb-0">{{ session('gem_refill_error') }}</div>
                @elseif(session('gem_refill_success'))
                    <div class="alert alert-success mb-0">{{ session('gem_refill_success') }}</div>
                @endif
                <div class="d-grid gap-2 mt-4">
                    @if(auth()->user()->role === 'student' && (int) $lessonState['hearts'] === 0)
                        <form method="POST" action="{{ route('student.hearts.refill') }}">
                            @csrf
                            <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
                            @php($refillCost = max(0, (int) config('gamification.heart_refill_gem_cost')))
                            <button type="submit" class="btn btn-primary rounded-pill py-2 w-100">💎 {{ number_format($refillCost) }} · Refill Hearts</button>
                        </form>
                    @else
                        <a href="{{ route('user.training', ['id' => $lesson->id, 'restart' => 1]) }}" class="btn btn-primary rounded-pill py-2">Restart lesson</a>
                    @endif
                    <a href="{{ route('levels.courses', $lesson->level_id) }}" class="btn btn-outline-light rounded-pill py-2" data-end-training>Return to lessons</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Tooltip Effect -->
<style>
.training-progress-fill {
    background: linear-gradient(90deg, #9333ea, #db2777);
}
.question-card:not(.d-none) {
    animation: questionEnter 240ms ease-out;
}
.option-btn {
    transition: transform 140ms ease, background-color 140ms ease, border-color 140ms ease;
    cursor: pointer;
}
.translation-tile {
    pointer-events: auto !important;
    position: relative;
    z-index: 2;
    user-select: none;
    touch-action: manipulation;
}
.translation-tile:disabled {
    opacity: 0.45;
    cursor: default;
}
.selected-chip { cursor: pointer; }
.option-btn:hover {
    transform: translateY(-2px);
    border-color: #c084fc;
}
@keyframes questionEnter {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.word-tooltip {
    position: relative;
    display: inline-block;
    border-bottom: 1px dashed #ffc107;
    transition: color 0.2s;
    margin-right: 8px;
    cursor: pointer;
}
.word-tooltip:hover {
    color: #fff;
}
.image-option-frame {
    height: 140px;
}
.image-option-img {
    width: 100%;
    height: 120px;
    object-fit: contain;
}
.image-option-card {
    min-width: 0;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}
.image-option-card.is-selected {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.16) !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
}
@media (max-width: 575.98px) {
    .image-option-frame {
        height: 160px;
    }
    .image-option-img {
        height: 140px;
    }
}
.word-tooltip:hover::after {
    content: attr(data-mean);
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #212529;
    color: #fff;
    padding: 4px 10px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #6c757d;
    white-space: nowrap;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.5);
    z-index: 10;
}
</style>

<!-- JavaScript for Interactivity -->
<script>
(function () {
    'use strict';

    function initTraining() {
        const trainingSession = document.getElementById('training-session');
        if (!trainingSession) return;

        const totalQuestions = Number('{{ count($lesson->vocabularies) }}');
        let currentQuestionIndex = Number('{{ $lessonState['next_index'] }}');
        let currentHearts = Number(trainingSession.dataset.hearts || 0);
        let requestPending = false;
        let trainingEnded = trainingSession.dataset.sessionEnded === '1';
        let lessonCompleted = trainingSession.dataset.lessonCompleted === '1';
        let allowNavigation = false;
        let hasStarted = @json(!$isTeaching && $lessonState['next_index'] > 0);

        const selectedTileIndexes = new Map();
        const selectedTileAnswers = new Map();

        const wordDictionary = {
            "みず": "water",
            "です": "is / it is",
            "すし": "sushi",
            "ください": "please",
            "これは": "this is",
            "本": "book",
            "猫": "cat",
            "犬": "dog"
        };

        function speakText(text) {
            if (!text || !('speechSynthesis' in window)) return;
            window.speechSynthesis.cancel();
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'ja-JP';
            window.speechSynthesis.speak(utterance);
        }

        window.speakText = speakText;

        function renderInteractiveSentence(sentence, index) {
            const container = document.getElementById('sentence-container-' + index);
            if (!container || !sentence) return;

            container.replaceChildren();

            sentence.trim().split(/\s+/).forEach(function (word) {
                const span = document.createElement('span');
                span.className = 'word-tooltip';
                span.textContent = word;
                span.dataset.mean = wordDictionary[word] || 'word';
                span.addEventListener('mouseenter', function () {
                    speakText(word);
                });
                span.addEventListener('click', function () {
                    speakText(word);
                });
                container.appendChild(span);
            });
        }

        function setHearts(hearts) {
            currentHearts = Number(hearts);
            const el = document.getElementById('heart-count');
            if (el) el.textContent = String(currentHearts);
            const navbarEl = document.getElementById('navbar-heart-count');
            if (navbarEl) navbarEl.textContent = '❤️ ' + currentHearts + '/5';
        }

        function selectImageOption(cardElement, selectedValue, questionIndex) {
            if (requestPending || trainingEnded || lessonCompleted) return;

            hasStarted = true;
            const questionBox = document.getElementById('question-box-' + questionIndex);
            if (!questionBox) return;

            questionBox.querySelectorAll('.option-card-item').forEach(function (card) {
                card.classList.remove('border-primary', 'bg-secondary', 'bg-opacity-25', 'is-selected');
            });

            cardElement.classList.add('border-primary', 'bg-secondary', 'bg-opacity-25', 'is-selected');
            questionBox.dataset.selected = selectedValue;

            const checkBtn = document.getElementById('check-btn-' + questionIndex);
            if (checkBtn) {
                checkBtn.disabled = false;
                checkBtn.classList.remove('btn-secondary');
                checkBtn.classList.add('btn-success');
            }
        }

        function selectOption(button, index) {
            if (requestPending || trainingEnded || lessonCompleted) return;

            hasStarted = true;

            const answerZone = document.getElementById('answer-zone-' + index);
            const checkBtn = document.getElementById('check-btn-' + index);
            if (!answerZone || !checkBtn) return;

            const placeholder = answerZone.querySelector('.placeholder-text');
            if (placeholder) placeholder.remove();

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-light text-dark py-2 px-3 rounded-3 m-1 selected-chip shadow-sm';
            chip.textContent = button.dataset.text || button.textContent.trim();
            chip.dataset.sourceOption = button.dataset.text || '';

            chip.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                button.style.display = '';
                chip.remove();

                if (!answerZone.querySelector('.selected-chip')) {
                    answerZone.innerHTML = '<span class="text-muted placeholder-text">Select words below...</span>';
                    checkBtn.classList.remove('btn-success', 'btn-danger');
                    checkBtn.classList.add('btn-secondary');
                    checkBtn.disabled = true;
                }
            });

            answerZone.appendChild(chip);
            button.style.display = 'none';

            checkBtn.classList.remove('btn-secondary', 'btn-danger');
            checkBtn.classList.add('btn-success');
            checkBtn.disabled = false;
        }

        function selectTranslationTile(tileElement, questionIndex, tileIndex) {
            if (requestPending || trainingEnded || lessonCompleted) return;
            if (!tileElement || tileElement.disabled || tileElement.hidden) return;

            const questionBox = document.getElementById('question-box-' + questionIndex);
            const answerZone = document.getElementById('answer-zone-' + questionIndex);
            const optionsPool = document.getElementById('options-pool-' + questionIndex);

            if (!questionBox || !answerZone || !optionsPool) return;

            const numericIndex = Number(tileIndex);
            if (!Number.isInteger(numericIndex)) return;

            let selected = selectedTileIndexes.get(questionIndex) || [];
            if (selected.includes(numericIndex)) return;

            selected = selected.concat(numericIndex);
            selectedTileIndexes.set(questionIndex, selected);
            hasStarted = true;

            renderTranslationTileAnswer(questionIndex);
        }

        function removeTranslationTile(questionIndex, tileIndex) {
            if (requestPending || trainingEnded || lessonCompleted) return;

            const numericIndex = Number(tileIndex);
            const selected = (selectedTileIndexes.get(questionIndex) || [])
                .filter(function (value) {
                    return Number(value) !== numericIndex;
                });

            selectedTileIndexes.set(questionIndex, selected);
            renderTranslationTileAnswer(questionIndex);
        }

        function renderTranslationTileAnswer(questionIndex) {
            const answerZone = document.getElementById('answer-zone-' + questionIndex);
            const optionsPool = document.getElementById('options-pool-' + questionIndex);
            const checkButton = document.getElementById('check-btn-' + questionIndex);

            if (!answerZone || !optionsPool || !checkButton) return;

            const selected = selectedTileIndexes.get(questionIndex) || [];
            const selectedSet = new Set(selected.map(Number));
            const answerParts = [];

            optionsPool.querySelectorAll('.translation-tile').forEach(function (tile) {
                const index = Number(tile.dataset.tileIndex);
                const isSelected = selectedSet.has(index);

                tile.hidden = isSelected;
                tile.disabled = isSelected;
                tile.style.display = isSelected ? 'none' : '';
            });

            answerZone.replaceChildren();

            selected.forEach(function (tileIndex) {
                const sourceTile = Array.from(
                    optionsPool.querySelectorAll('.translation-tile')
                ).find(function (tile) {
                    return Number(tile.dataset.tileIndex) === Number(tileIndex);
                });

                if (!sourceTile) return;

                const value = sourceTile.dataset.text || sourceTile.textContent.trim();
                const chip = document.createElement('button');

                chip.type = 'button';
                chip.className = 'btn btn-light text-dark py-2 px-3 rounded-3 selected-chip shadow-sm';
                chip.dataset.tileIndex = String(tileIndex);
                chip.textContent = value;
                chip.title = 'Click to remove';

                chip.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    removeTranslationTile(questionIndex, tileIndex);
                });

                answerZone.appendChild(chip);
                answerParts.push(value);
            });

            if (answerParts.length === 0) {
                const placeholder = document.createElement('span');
                placeholder.className = 'text-muted placeholder-text';
                placeholder.textContent = 'Select words below...';
                answerZone.appendChild(placeholder);
            }

            const answer = answerParts.join(' ');
            selectedTileAnswers.set(questionIndex, answer);

            checkButton.disabled = answerParts.length === 0;
            checkButton.classList.toggle('btn-success', answerParts.length > 0);
            checkButton.classList.toggle('btn-secondary', answerParts.length === 0);
        }

        async function checkAnswer(index, total) {
            if (requestPending || trainingEnded || lessonCompleted) return;

            const questionBox = document.getElementById('question-box-' + index);
            if (!questionBox) return;

            let answer = '';

            if (questionBox.dataset.questionType === 'translation_tiles') {
                answer = selectedTileAnswers.get(index) || '';
            } else if (questionBox.querySelector('.option-card-item')) {
                answer = questionBox.dataset.selected || '';
            } else {
                answer = Array.from(
                    document.querySelectorAll('#answer-zone-' + index + ' .selected-chip')
                ).map(function (chip) {
                    return chip.textContent.trim();
                }).join(' ');
            }

            if (!answer.trim()) return;

            const checkButton = document.getElementById('check-btn-' + index);
            const skipButton = questionBox.querySelector('.skip-btn');
            const feedback = document.getElementById('feedback-' + index);
            const correctAnswer = (questionBox.dataset.correct || '').trim();

            requestPending = true;
            checkButton.disabled = true;
            if (skipButton) skipButton.disabled = true;

            if (feedback) {
                feedback.className = 'alert alert-info mb-3';
                feedback.textContent = 'Checking your answer…';
            }

            try {
                const response = await fetch(
                    @json(route('user.lesson.answer', $lesson->id)),
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            vocabulary_id: questionBox.dataset.vocabularyId,
                            answer: answer
                        })
                    }
                );

                const result = await response.json();

                if (typeof result.hearts === 'number') {
                    setHearts(result.hearts);
                }

                if (response.ok && result.correct) {
                    if (feedback) {
                        feedback.className = 'alert alert-success mb-3';
                        feedback.textContent = 'Correct! Great job! 🎉';
                    }

                    currentQuestionIndex = index + 1;
                    updateProgress(currentQuestionIndex, total);

                    if (result.lesson_completed) {
                        lessonCompleted = true;
                        trainingEnded = true;
                        const progression = result.progression || {};
                        const rewardAmount = document.getElementById('fire-awarded');
                        const fireTotal = document.getElementById('fire-total');
                        const gemAwarded = document.getElementById('gem-awarded');
                        const gemBalance = document.getElementById('navbar-gem-total');
                        const popoverGemBalance = document.getElementById('navbar-gem-count');
                        const dailyStreakTrigger = document.getElementById('daily-streak-trigger');
                        const dailyStreakCount = document.getElementById('daily-streak-count');
                        const dailyStreakValue = document.getElementById('daily-streak-value');
                        const dailyStreakToday = document.getElementById('daily-streak-today');
                        if (rewardAmount) rewardAmount.textContent = Number(progression.fire_awarded || 0);
                        if (fireTotal) fireTotal.textContent = Number(progression.streak_count || 0);
                        if (gemAwarded) gemAwarded.textContent = Number(progression.gems_awarded || 0);
                        if (gemBalance) gemBalance.textContent = Number(progression.gems_balance || 0);
                        if (popoverGemBalance) popoverGemBalance.textContent = Number(progression.gems_balance || 0);
                        if (dailyStreakTrigger && Number.isFinite(Number(progression.daily_streak))) {
                            const dailyStreak = Number(progression.daily_streak);
                            dailyStreakCount.textContent = dailyStreakValue.textContent = String(dailyStreak);
                            dailyStreakTrigger.classList.toggle('is-milestone', dailyStreak >= 7);
                            dailyStreakTrigger.setAttribute('aria-label', 'Daily streak, ' + dailyStreak + ' days');
                            const completedToday = Boolean(progression.learning_completed_today);
                            dailyStreakToday.textContent = completedToday
                                ? '✓ Today’s learning completed'
                                : 'Learn today to continue your streak';
                            dailyStreakToday.classList.toggle('is-done', completedToday);
                            dailyStreakToday.classList.toggle('is-pending', !completedToday);
                        }
                        const navbarFire = document.getElementById('navbar-fire-count');
                        if (navbarFire) navbarFire.textContent = '🔥 ' + Number(progression.streak_count || 0);
                        const modalElement = document.getElementById('congratsModal');
                        if (modalElement && window.bootstrap) {
                            new bootstrap.Modal(modalElement).show();
                        }
                    } else if (result.session_ended) {
                        trainingEnded = true;
                        setTimeout(function () {
                            showSessionEnded('questions_finished');
                        }, 500);
                    } else {
                        setTimeout(function () {
                            nextQuestion(index, total);
                        }, 600);
                    }
                } else if (response.status === 422 && result.correct === false) {
                    if (feedback) {
                        feedback.className = 'alert alert-danger mb-3';
                        feedback.textContent = 'Incorrect. Correct answer is: "' + correctAnswer + '"';
                    }

                    if (result.session_ended) {
                        trainingEnded = true;
                        setTimeout(function () {
                            showSessionEnded('hearts');
                        }, 500);
                    } else {
                        checkButton.disabled = false;
                        if (skipButton) skipButton.disabled = false;
                    }
                } else {
                    throw new Error(result.message || 'Could not verify this answer.');
                }
            } catch (error) {
                if (feedback) {
                    feedback.className = 'alert alert-danger mb-3';
                    feedback.textContent = error.message || 'Could not verify this answer. Please try CHECK again.';
                }
                checkButton.disabled = false;
                if (skipButton) skipButton.disabled = false;
            } finally {
                requestPending = false;
            }
        }

        async function skipQuestion(index, total) {
            if (requestPending || trainingEnded || lessonCompleted) return;

            const questionBox = document.getElementById('question-box-' + index);
            if (!questionBox) return;

            const skipButton = questionBox.querySelector('.skip-btn');
            const checkButton = document.getElementById('check-btn-' + index);

            hasStarted = true;
            requestPending = true;

            if (skipButton) skipButton.disabled = true;
            if (checkButton) checkButton.disabled = true;

            try {
                const response = await fetch(
                    @json(route('user.lesson.skip', $lesson->id)),
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            vocabulary_id: questionBox.dataset.vocabularyId
                        })
                    }
                );

                const result = await response.json();

                if (!response.ok || !result.accepted) {
                    throw new Error(result.message || 'This question could not be skipped.');
                }

                setHearts(result.hearts);
                currentQuestionIndex = index + 1;
                updateProgress(currentQuestionIndex, total);

                if (result.session_ended) {
                    trainingEnded = true;
                    showSessionEnded(result.end_reason || 'questions_finished');
                } else {
                    nextQuestion(index, total);
                }
            } catch (error) {
                const feedback = document.getElementById('feedback-' + index);
                if (feedback) {
                    feedback.className = 'alert alert-danger mb-3';
                    feedback.textContent = error.message;
                    feedback.classList.remove('d-none');
                }

                if (skipButton) skipButton.disabled = false;
                if (checkButton) checkButton.disabled = false;
            } finally {
                requestPending = false;
            }
        }

        function nextQuestion(index, total) {
            const currentBox = document.getElementById('question-box-' + index);
            if (currentBox) currentBox.classList.add('d-none');

            const nextIndex = index + 1;
            const nextBox = document.getElementById('question-box-' + nextIndex);

            if (nextBox) {
                nextBox.classList.remove('d-none');
                updateQuestionCounter(nextIndex);
            } else if (!lessonCompleted) {
                trainingEnded = true;
                showSessionEnded('questions_finished');
            }
        }

        function updateProgress(current, total) {
            const progressBar = document.getElementById('progress-bar');
            if (progressBar) {
                const percentage = total > 0
                    ? Math.min(100, (current / total) * 100)
                    : 0;
                progressBar.style.width = percentage + '%';
            }
            updateQuestionCounter(current);
        }

        function updateQuestionCounter(current) {
            const label = document.getElementById('question-counter');
            if (label) {
                label.textContent =
                    'Question ' + Math.min(current + 1, totalQuestions) +
                    ' / ' + totalQuestions;
            }
        }

        function showSessionEnded(reason) {
            trainingEnded = true;

            const message = document.getElementById('sessionEndedMessage');
            if (message) {
                message.textContent = reason === 'questions_finished'
                    ? 'You reached the end without answering every question correctly. Your lesson was not completed.'
                    : 'You are out of hearts. Your lesson was not completed.';
            }

            const modalElement = document.getElementById('sessionEndedModal');
            if (modalElement && window.bootstrap) {
                new bootstrap.Modal(modalElement).show();
            }
        }

        // Make the functions available to existing inline integrations if any remain.
        window.speakText = speakText;
        window.checkAnswer = checkAnswer;
        window.skipQuestion = skipQuestion;

        @if(!$isTeaching)
        @foreach($lesson->vocabularies as $index => $vocabulary)
            @if($vocabulary->question_type !== 'image_selection')
                renderInteractiveSentence({!! json_encode($vocabulary->question) !!}, {{ $index }});
            @endif
        @endforeach
        @endif

        // Pronunciation buttons
        trainingSession.querySelectorAll('[data-pronounce]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                speakText(button.dataset.pronounce || '');
            });
        });

        // Translation and normal answer options
        trainingSession.querySelectorAll('.translation-tile').forEach(function (tile) {
            tile.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const questionBox = tile.closest('.question-card');
                if (!questionBox) return;

                selectTranslationTile(
                    tile,
                    Number(questionBox.dataset.index),
                    Number(tile.dataset.tileIndex)
                );
            });
        });

        trainingSession.querySelectorAll('.option-btn:not(.translation-tile)').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const questionBox = button.closest('.question-card');
                if (!questionBox) return;

                selectOption(button, Number(questionBox.dataset.index));
            });
        });

        trainingSession.querySelectorAll('.option-card-item').forEach(function (card) {
            card.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const questionBox = card.closest('.question-card');
                if (!questionBox) return;

                selectImageOption(
                    card,
                    card.dataset.option || '',
                    Number(questionBox.dataset.index)
                );
            });

            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    card.click();
                }
            });
        });

        trainingSession.querySelectorAll('.check-btn').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                checkAnswer(
                    Number(button.dataset.questionIndex),
                    totalQuestions
                );
            });
        });

        trainingSession.querySelectorAll('.skip-btn').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                skipQuestion(
                    Number(button.dataset.questionIndex),
                    totalQuestions
                );
            });
        });

        trainingSession.querySelectorAll('[data-end-training]').forEach(function (link) {
            link.addEventListener('click', function () {
                allowNavigation = true;
            });
        });
        // Modal actions (End Session / Return to lessons) live outside #training-session —
        // bind them too so their navigation is not blocked by the beforeunload guard.
        document.querySelectorAll('.modal [data-end-training]').forEach(function (link) {
            link.addEventListener('click', function () {
                allowNavigation = true;
            });
        });

        updateProgress(currentQuestionIndex, totalQuestions);

        if (trainingEnded) {
            showSessionEnded(@json($lessonState['end_reason'] ?? 'hearts'));
        }

        window.addEventListener('beforeunload', function (event) {
            if (!allowNavigation && hasStarted && !lessonCompleted && !trainingEnded) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTraining, { once: true });
    } else {
        initTraining();
    }
})();
</script>
@endsection
