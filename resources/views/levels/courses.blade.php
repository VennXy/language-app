@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Courses: glass lesson rows + sakura mode toggle */
.sk-dash main .lesson-card{
  background:var(--sk-card) !important; border:1px solid var(--sk-line) !important;
  border-radius:16px; backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
  transition:all .25s ease;
}
.sk-dash main a:hover .lesson-card{
  background:rgba(168,85,247,.10) !important; border-color:rgba(192,132,252,.45) !important;
  transform:translateX(4px); box-shadow:0 12px 30px rgba(4,2,16,.45), 0 0 18px rgba(168,85,247,.16);
}
.sk-dash main .sk-mode-toggle{background:rgba(12,6,28,.70) !important; border-color:rgba(192,132,252,.25) !important; box-shadow:inset 0 1px 0 rgba(255,255,255,.05);}
.sk-dash main .sk-mode-toggle .btn{border-radius:999px; font-weight:700;}
.sk-dash main .sk-mode-toggle .btn.text-muted{color:rgba(226,220,255,.60) !important;}
.sk-dash main .sk-mode-toggle .btn-success{box-shadow:0 0 14px rgba(168,85,247,.35);}
</style>
@endpush

@section('content')
<div class="container py-5" style="min-height: 75vh;">
    
    <!-- Header & Toggle Tabs (Training / Exam) -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
           <div class="mb-3">
    <a href="{{ route('select.level') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Levels
    </a>
</div>
            <h3 class="fw-bold text-white mb-1">Practice & Test</h3>
            <p class="text-muted small mb-0">Choose your preferred mode to practice Japanese.</p>
        </div> 
        
        <!-- Training / Exam Toggle Buttons -->
        <div class="sk-mode-toggle bg-dark p-1 rounded-pill d-flex border border-secondary align-self-start align-self-md-auto">
            <a href="{{ route('levels.courses', $level->id) }}" 
               class="btn rounded-pill px-4 py-2 {{ $type == 'training' ? 'btn-success text-white fw-bold' : 'text-muted' }}">
                Training
            </a>
            <a href="{{ route('levels.courses', $level->id) }}" 
               class="btn rounded-pill px-4 py-2 {{ $type == 'exam' ? 'btn-success text-white fw-bold' : 'text-muted' }}">
                Exam
            </a>
        </div>
    </div> 


    @if(!empty($trainingComplete) && $lessons->isNotEmpty())
        <div class="alert alert-success d-flex align-items-center justify-content-between gap-3 flex-wrap" role="alert">
            <span>
                <i class="bi bi-check-circle-fill me-1"></i>
                You’ve completed this level’s Training. Ready to take the Exams?
            </span>
            <a class="btn btn-sm btn-dark" href="{{ route('student.exams.index') }}">Take the Exams</a>
        </div>
    @endif


    <!-- Lessons / History List -->
<div class="row g-3">
    @forelse($lessons as $lesson)
        @php
            $isUnlocked = in_array((int) $lesson->id, $accessibleLessonIds, true);
            $isCompleted = in_array((int) $lesson->id, $completedLessonIds, true);
            $lessonProgress = $progressByLesson->get($lesson->id);
            $progressPercent = $isCompleted ? 100 : (int) ($lessonProgress->progress_percent ?? 0);
        @endphp
        <div class="col-12">
            @if($isUnlocked)
                <a href="{{ route('user.training', ['id' => $lesson->id, 'type' => $type]) }}" class="text-decoration-none">
            @else
                <div class="text-decoration-none lesson-locked" aria-disabled="true" aria-label="{{ $lesson->title }} is locked">
            @endif
                <div class="p-3 rounded-4 d-flex align-items-center justify-content-between lesson-card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-3" style="background: rgba(147, 51, 234, 0.2); color: #c084fc;">
                            <i class="bi bi-book fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">{{ $lesson->title }}</h5>
                            <div class="mt-2" style="min-width: min(58vw, 360px);">
                                <div class="d-flex justify-content-between align-items-center small text-light mb-1">
                                    <span>Progress</span>
                                    <span>{{ $progressPercent }}%</span>
                                </div>
                                <div class="progress bg-secondary" role="progressbar" aria-label="{{ $lesson->title }} progress" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100" style="height: 7px;">
                                    <div class="progress-bar" style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, #9333ea, #db2777);"></div>
                                </div>
                                @if($firePoints !== null && $type === 'training')
                                    <span class="small text-info d-inline-block mt-1">💎 Complete this learning to earn +{{ (int) config('gamification.learning_completion_gem_reward', 50) }} Gems</span>
                                @endif
                            </div>
                            @if($isUnlocked && $isCompleted)
                                <span class="badge bg-success text-white px-2 py-1">Completed</span>
                            @elseif($isUnlocked)
                                <span class="badge bg-secondary text-dark px-2 py-1">Score: {{ $lesson->score ?? '0' }}%</span>
                            @else
                                <span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                            @endif
                        </div>
                    </div>
                    <!-- Arrow Icon -->
                    <div class="text-white-50 fs-5">
                        <i class="bi {{ $isUnlocked ? 'bi-arrow-right' : 'bi-lock-fill' }}"></i>
                    </div>
                </div>
            @if($isUnlocked)
                </a>
            @else
                </div>
            @endif
        </div>
    @empty
        <div class="col-12 text-center py-5">
            <div class="p-5 rounded-4" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255, 255, 255, 0.1);">
                <p class="text-muted mb-0">No {{ $type }} lessons available for this level yet.</p>
            </div>
        </div>
    @endforelse
</div>

    <!-- Optional CSS for Hover Effect -->
    <style>
        a:hover .lesson-card {
            background: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(192, 132, 252, 0.4) !important;
            transform: translateX(4px);
        }
        .lesson-card:hover .arrow-icon {
            color: #c084fc !important;
        }
        .lesson-locked {
            display: block;
            cursor: not-allowed;
            opacity: 0.62;
        }
    </style>

</div>
@endsection
