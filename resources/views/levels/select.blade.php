@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Levels & Courses: glass level cards, sakura hover, locked states */
.sk-dash main .level-card{
  background:var(--sk-card); border:1px solid var(--sk-line); border-radius:18px;
  backdrop-filter:blur(16px) saturate(140%); -webkit-backdrop-filter:blur(16px) saturate(140%);
  box-shadow:0 12px 36px rgba(4,2,16,.45), 0 0 22px rgba(139,92,246,.07), inset 0 1px 0 rgba(255,255,255,.05);
  transition:all .3s ease-in-out;
}
.sk-dash main .level-card-link:hover .level-card{
  transform:translateY(-8px); background:rgba(168,85,247,.10);
  border-color:rgba(216,180,254,.45);
  box-shadow:0 16px 40px rgba(4,2,16,.5), 0 0 26px rgba(168,85,247,.22);
}
.sk-dash main .level-card-locked .level-card{background:rgba(255,255,255,.02); border-color:rgba(255,255,255,.05); box-shadow:none;}
</style>
@endpush

@section('content')
<div class="container py-5" style="min-height: 75vh;">
    <!-- Header Title -->
    <div class="text-center mb-5">
        <h2 class="fw-extrabold display-5 mb-3 gradient-text">Choose Your Japanese Level</h2>
        <p class="text-muted">Select your current proficiency to get started with tailored courses.</p>
        @if(Auth::user()->role === 'student')
            <a href="{{ route('student.exams.index') }}" class="btn btn-outline-light rounded-pill px-4 mt-2">
                <i class="bi bi-journal-check me-2"></i>Exams
            </a>
        @endif
    </div>

    <!-- Levels Grid -->
    <div class="row g-4 justify-content-center">
        @forelse($levels as $level)
            @php($isUnlocked = in_array((int) $level->id, $accessibleLevelIds, true))
            @php($isCompleted = in_array((int) $level->id, $completedLevelIds, true))
            <div class="col-md-4 col-sm-6">
                @if($isUnlocked)
                    <a href="{{ route('levels.courses', $level->id) }}" class="text-decoration-none level-card-link">
                @else
                    <div class="text-decoration-none level-card-locked" aria-disabled="true" aria-label="{{ $level->name }} is locked">
                @endif
                    <div class="level-card p-4 h-100 rounded-4 position-relative">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge px-3 py-2 rounded-pill fs-5" style="background: rgba(147, 51, 234, 0.2); color: #c084fc;">
                                {{ $level->name }}
                            </span>
                            @if($isUnlocked)
                                @if($isCompleted)
                                    <span class="badge bg-success text-white me-2"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                                @else
                                    <span class="small fw-semibold text-success me-2">Start Learning</span>
                                    <i class="bi bi-arrow-right fs-4 text-white-50 arrow-icon"></i>
                                @endif
                            @else
                                <span class="badge bg-secondary text-white"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                            @endif
                        </div>
                        <h4 class="fw-bold mb-2 text-white">{{ $level->title ?? $level->name }}</h4>
                        <p class="text-muted small mb-0">{{ $level->description }}</p>
                    </div>
                @if($isUnlocked)
                    </a>
                @else
                    </div>
                @endif
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted">No levels found in the database.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Custom CSS for Hover Effect -->
<style>
    .level-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease-in-out;
    }

    .level-card-link:hover .level-card {
        transform: translateY(-8px);
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(192, 132, 252, 0.4);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .level-card-link:hover .arrow-icon {
        color: #c084fc !important;
        transform: translateX(4px);
        transition: 0.3s ease;
    }
    
    .arrow-icon {
        transition: 0.3s ease;
    }

    .level-card-locked {
        cursor: not-allowed;
        opacity: 0.45;
        filter: grayscale(0.65);
        pointer-events: none;
    }

    .level-card-locked .level-card {
        background: rgba(255, 255, 255, 0.015);
        border-color: rgba(255, 255, 255, 0.04);
    }
</style>
@endsection
