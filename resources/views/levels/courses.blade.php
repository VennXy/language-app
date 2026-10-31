
@extends('layouts.app')

@section('student_shell', true)

@push('styles')
<style>
/* =========================================
   SAKURA LEARNING PATH
========================================= */

.sk-path-page {
    --path-purple: #9333ea;
    --path-pink: #db2777;
    --path-light: #c084fc;
    --path-dark: #100b20;
    --path-card: rgba(25, 15, 45, 0.78);
    --path-border: rgba(192, 132, 252, 0.22);

    min-height: 80vh;
    padding: 35px 15px 70px;
    color: #fff;
}

.sk-path-header {
    max-width: 760px;
    margin: 0 auto 35px;
    padding: 24px;
    border-radius: 22px;
    background: linear-gradient(120deg, #581c87, #9333ea, #be185d);
    box-shadow: 0 12px 35px rgba(0, 0, 0, .3);
    position: relative;
    overflow: hidden;
}

.sk-path-header::after {
    content: "";
    position: absolute;
    width: 180px;
    height: 180px;
    right: -55px;
    top: -75px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}

.sk-path-header h2 {
    font-size: 1.65rem;
    font-weight: 800;
    margin-bottom: 6px;
}

.sk-path-header p {
    color: rgba(255,255,255,.8);
    margin-bottom: 0;
}

.sk-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #fff;
    text-decoration: none;
    font-size: .9rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.sk-back-btn:hover {
    color: #f5d0fe;
}

/* Training / Exam selector */

.sk-mode-toggle {
    display: inline-flex;
    gap: 5px;
    padding: 5px;
    border-radius: 50px;
    background: rgba(12, 6, 28, .7);
    border: 1px solid var(--path-border);
}

.sk-mode-toggle .btn {
    border-radius: 50px;
    padding: 9px 23px;
    font-weight: 700;
    color: rgba(255,255,255,.65);
}

.sk-mode-toggle .btn.active {
    color: #fff;
    background: linear-gradient(120deg, #9333ea, #db2777);
    border: none;
    box-shadow: 0 4px 15px rgba(147,51,234,.35);
}

/* Path container */

.sk-path-container {
    max-width: 760px;
    margin: 0 auto;
    padding: 15px 0 40px;
    position: relative;
}

.sk-path-title {
    text-align: center;
    margin-bottom: 35px;
}

.sk-path-title h4 {
    font-size: 1.1rem;
    font-weight: 800;
    color: #e9d5ff;
}

.sk-path-title p {
    color: rgba(255,255,255,.55);
    font-size: .85rem;
}

/* Vertical connecting line */

.sk-path-track {
    position: relative;
    padding: 0;
}

.sk-path-track::before {
    content: "";
    position: absolute;
    top: 35px;
    bottom: 65px;
    left: 50%;
    transform: translateX(-50%);
    width: 7px;
    border-radius: 20px;
    background: repeating-linear-gradient(
        to bottom,
        rgba(192,132,252,.35) 0px,
        rgba(192,132,252,.35) 12px,
        transparent 12px,
        transparent 22px
    );
}

/* Each lesson row */

.sk-path-step {
    min-height: 145px;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    z-index: 1;
}

.sk-path-step:nth-child(odd) .sk-path-node-wrap {
    transform: translateX(-72px);
}

.sk-path-step:nth-child(even) .sk-path-node-wrap {
    transform: translateX(72px);
}

.sk-path-node-wrap {
    width: 160px;
    text-align: center;
    transition: transform .3s ease;
}

/* Lesson node */

.sk-path-node {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    text-decoration: none;
    border: 7px solid rgba(255,255,255,.12);
    transition: all .25s ease;
}

.sk-path-node i {
    font-size: 2rem;
}

/* Unlocked */

.sk-path-node.unlocked {
    color: #fff;
    background: linear-gradient(145deg, #a855f7, #7e22ce);
    border-color: rgba(216,180,254,.28);
    box-shadow:
        0 7px 0 #581c87,
        0 12px 25px rgba(147,51,234,.3);
}

.sk-path-node.unlocked:hover {
    transform: translateY(-5px) scale(1.06);
    color: #fff;
    box-shadow:
        0 10px 0 #581c87,
        0 16px 30px rgba(147,51,234,.4);
}

/* Completed */

.sk-path-node.completed {
    color: #fff;
    background: linear-gradient(145deg, #ec4899, #be185d);
    border-color: rgba(249,168,212,.3);
    box-shadow:
        0 7px 0 #831843,
        0 12px 25px rgba(219,39,119,.25);
}

/* Locked */

.sk-path-node.locked {
    color: #64748b;
    background: #273444;
    border-color: rgba(148,163,184,.1);
    box-shadow: 0 7px 0 #1e293b;
    cursor: not-allowed;
}

/* Node labels */

.sk-node-label {
    display: inline-block;
    margin-top: 16px;
    font-size: .85rem;
    font-weight: 800;
    color: #fff;
    max-width: 160px;
    overflow-wrap: anywhere;
}

.sk-node-status {
    display: block;
    margin-top: 4px;
    font-size: .72rem;
    color: rgba(255,255,255,.55);
}

.sk-node-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    padding: 4px 13px;
    border-radius: 9px;
    background: #f0abfc;
    color: #581c87;
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .5px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0,0,0,.2);
}

/* Progress */

.sk-path-progress {
    max-width: 760px;
    margin: 0 auto 30px;
    padding: 18px 20px;
    background: var(--path-card);
    border: 1px solid var(--path-border);
    border-radius: 16px;
    backdrop-filter: blur(14px);
}

.sk-path-progress .progress {
    height: 9px;
    background: rgba(255,255,255,.1);
    border-radius: 20px;
}

.sk-path-progress .progress-bar {
    background: linear-gradient(90deg, #9333ea, #db2777);
    border-radius: 20px;
}

/* Completed message */

.sk-complete-message {
    max-width: 760px;
    margin: 0 auto 25px;
    padding: 16px 20px;
    border: 1px solid rgba(74,222,128,.3);
    border-radius: 15px;
    background: rgba(22,163,74,.12);
    color: #bbf7d0;
}

/* Empty state */

.sk-empty-state {
    max-width: 760px;
    margin: 0 auto;
    padding: 55px 20px;
    text-align: center;
    background: var(--path-card);
    border: 1px dashed var(--path-border);
    border-radius: 20px;
}

.sk-empty-state i {
    font-size: 3rem;
    color: #c084fc;
    display: block;
    margin-bottom: 15px;
}

/* Mobile */

@media (max-width: 576px) {
    .sk-path-page {
        padding: 20px 10px 50px;
    }

    .sk-path-header {
        padding: 20px;
        border-radius: 17px;
    }

    .sk-path-header h2 {
        font-size: 1.35rem;
    }

    .sk-path-step {
        min-height: 132px;
    }

    .sk-path-step:nth-child(odd) .sk-path-node-wrap {
        transform: translateX(-42px);
    }

    .sk-path-step:nth-child(even) .sk-path-node-wrap {
        transform: translateX(42px);
    }

    .sk-path-node {
        width: 75px;
        height: 75px;
    }

    .sk-path-node i {
        font-size: 1.7rem;
    }

    .sk-node-label {
        font-size: .78rem;
    }

    .sk-path-track::before {
        width: 5px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .sk-path-node,
    .sk-path-node-wrap {
        transition: none;
    }
}
</style>
@endpush



@section('content')

<div class="sk-path-page">

    {{-- Header --}}
    <div class="sk-path-header">

        <a href="{{ route('select.level') }}" class="sk-back-btn">
            <i class="bi bi-arrow-left"></i>
            Back to Levels
        </a>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

            <div>
                <h2>
                    <i class="bi bi-stars me-2"></i>
                    A1 – C2 Learning Journey
                </h2>

                <p>
                    Keep learning, complete your lessons and unlock the next level!
                </p>
            </div>

            {{-- Training / Exam --}}


        </div>
    </div>


    {{-- Overall Progress --}}
    <div class="sk-path-progress">

        <div class="d-flex justify-content-between align-items-center mb-2">

            <strong>
                <i class="bi bi-graph-up-arrow me-2 text-info"></i>
                Your Learning Progress
            </strong>

            <strong class="text-info">
                {{ $overallProgress }}%
            </strong>

        </div>

        <div class="progress">
            <div class="progress-bar"
                 role="progressbar"
                 style="width: {{ $overallProgress }}%"
                 aria-valuenow="{{ $overallProgress }}"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>

        <div class="d-flex justify-content-between mt-2 small text-white-50">
            <span>{{ $completedCount }} Completed</span>
            <span>{{ $totalLessons }} Total Lessons</span>
        </div>

    </div>


    {{-- Single Learning Path --}}
    <div class="sk-path-container">

        <div class="sk-path-title">
            <h4>
                <i class="bi bi-signpost-split me-2"></i>
                Your English Learning Path
            </h4>

            <p>
                Complete each lesson to unlock the next step of your journey.
            </p>
        </div>


        @forelse($pathLevels as $path)

            @php
                $level = $path['level'];
                $lessons = $path['lessons'];

                $isLevelUnlocked = $path['isLevelUnlocked'];
                $isLevelCompleted = $path['isLevelCompleted'];

                $accessibleLessonIds = $path['accessibleLessonIds'];
                $completedLessonIds = $path['completedLessonIds'];

                $levelProgress = $path['overallProgress'];
                $levelCompletedCount = $path['completedCount'];
                $levelTotalLessons = $path['totalLessons'];

                $levelAccent = [
                    'A1' => '#a855f7',
                    'A2' => '#3b82f6',
                    'B1' => '#06b6d4',
                    'B2' => '#f59e0b',
                    'C1' => '#ec4899',
                    'C2' => '#10b981',
                ][$level->level] ?? '#a855f7';
            @endphp


            {{-- Each Level Section --}}
            <section id="level-{{ $level->id }}"
                     class="mb-5"
                     style="scroll-margin-top: 30px;">

                {{-- Level Header --}}
                <div class="sk-path-header"
                     style="background:linear-gradient(120deg, {{ $levelAccent }}, #581c87, #be185d);">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                        <div>

                            <div class="d-flex align-items-center gap-2 mb-2">

                                <span class="badge bg-light text-dark px-3 py-2">
                                    {{ $level->level }}
                                </span>

                                @if($isLevelCompleted)

                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Completed
                                    </span>

                                @elseif($isLevelUnlocked)

                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-unlock-fill me-1"></i>
                                        Unlocked
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        <i class="bi bi-lock-fill me-1"></i>
                                        Locked
                                    </span>

                                @endif

                            </div>

                            <h2 class="mb-1">
                                {{ $level->title ?? $level->name }}
                            </h2>

                            <p>
                                {{ $level->description }}
                            </p>

                        </div>

                        <div class="text-end">

                            <div class="fw-bold fs-4">
                                {{ $levelProgress }}%
                            </div>

                            <small>
                                {{ $levelCompletedCount }} / {{ $levelTotalLessons }} Lessons
                            </small>

                        </div>

                    </div>

                </div>


                {{-- Level Progress --}}
                <div class="sk-path-progress">

                    <div class="progress">
                        <div class="progress-bar"
                             style="width: {{ $levelProgress }}%">
                        </div>
                    </div>

                </div>


                {{-- Lessons --}}
                @if($lessons->isNotEmpty())

                    <div class="sk-path-track">

                        @foreach($lessons as $lesson)

                            @php
                                $isUnlocked = in_array(
                                    (int) $lesson->id,
                                    $accessibleLessonIds,
                                    true
                                );

                                $isCompleted = in_array(
                                    (int) $lesson->id,
                                    $completedLessonIds,
                                    true
                                );

                                $lessonProgress = $progressByLesson->get(
                                    $lesson->id
                                );

                                $progressPercent = $isCompleted
                                    ? 100
                                    : (int) ($lessonProgress->progress_percent ?? 0);

                                $nodeClass = $isCompleted
                                    ? 'completed'
                                    : ($isUnlocked ? 'unlocked' : 'locked');

                                $nodeIcon = $isCompleted
                                    ? 'bi-check-lg'
                                    : ($isUnlocked ? 'bi-star-fill' : 'bi-lock-fill');

                                $nodeStatus = $isCompleted
                                    ? 'Completed'
                                    : ($isUnlocked ? 'Ready to Learn' : 'Locked');
                            @endphp


                            <div class="sk-path-step">

                                <div class="sk-path-node-wrap">

                                    @if($isUnlocked)

                                        <a href="{{ route('user.training', [
                                            'id' => $lesson->id,
                                            'type' => $type
                                        ]) }}"
                                           class="sk-path-node {{ $nodeClass }}"
                                           aria-label="Open {{ $lesson->title }}">

                                            @if($loop->first && !$isCompleted)
                                                <span class="sk-node-badge">START</span>
                                            @endif

                                            <i class="bi {{ $nodeIcon }}"></i>

                                        </a>

                                    @else

                                        <div class="sk-path-node locked"
                                             aria-disabled="true"
                                             title="Complete previous lessons and levels to unlock">

                                            <i class="bi bi-lock-fill"></i>

                                        </div>

                                    @endif


                                    <div class="sk-node-label">
                                        {{ $lesson->title }}
                                    </div>


                                    <span class="sk-node-status">

                                        @if($isCompleted)

                                            <i class="bi bi-check-circle-fill text-success me-1"></i>

                                        @elseif($isUnlocked)

                                            <i class="bi bi-play-circle-fill text-info me-1"></i>

                                        @else

                                            <i class="bi bi-lock-fill me-1"></i>

                                        @endif

                                        {{ $nodeStatus }}

                                    </span>


                                    @if($isUnlocked && !$isCompleted && $progressPercent > 0)

                                        <div class="progress mt-2 mx-auto"
                                             style="height:5px; max-width:100px; background:rgba(255,255,255,.12);">

                                            <div class="progress-bar"
                                                 style="width:{{ $progressPercent }}%; background:#c084fc;">
                                            </div>

                                        </div>

                                    @endif

                                </div>

                            </div>

                        @endforeach

                    </div>


                    {{-- Level Completion Message --}}
                    @if($isLevelCompleted)

                        <div class="sk-complete-message">

                            <i class="bi bi-trophy-fill me-2"></i>

                            <strong>
                                {{ $level->level }} Completed!
                            </strong>

                            <div class="small mt-1">
                                Congratulations! You have completed this level.
                            </div>

                        </div>

                    @elseif($path['trainingComplete'] && $isLevelUnlocked)

                        <div class="sk-complete-message">

                            <i class="bi bi-award-fill me-2"></i>

                            <strong>Training Completed!</strong>

                            <div class="small mt-1">
                                Great job! You are ready for the Exams.
                            </div>

                            <a href="{{ route('student.exams.index') }}"
                               class="btn btn-success btn-sm rounded-pill px-3 mt-3">

                                Take Exams
                                <i class="bi bi-arrow-right ms-1"></i>

                            </a>

                        </div>

                    @endif

                @else

                    <div class="sk-empty-state">

                        <i class="bi bi-journal-x"></i>

                        <h5 class="fw-bold text-white">
                            No Lessons Available
                        </h5>

                        <p class="text-white-50 mb-0">
                            No lessons have been added to {{ $level->level }} yet.
                        </p>

                    </div>

                @endif

            </section>

        @empty

            <div class="sk-empty-state">

                <i class="bi bi-journal-x"></i>

                <h5 class="fw-bold text-white">
                    No Levels Available
                </h5>

                <p class="text-white-50 mb-0">
                    No learning levels were found.
                </p>

            </div>

        @endforelse

    </div>

</div>

@endsection
