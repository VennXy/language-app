@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* ============ Training · Choose Your Japanese Level (page-scoped styles) ============ */
/* Calmer ambient on this page only: no petals, softer orbs/watermark */
.sk-dash .sk-bg .sk-petal{display:none}
.sk-dash .sk-bg .sk-orb{opacity:.22;filter:blur(90px)}
.sk-dash .sk-bg .sk-wm{color:rgba(244,114,182,.03)}

/* Header typography */
.lvl-h{font-size:clamp(2rem,1.1rem + 3vw,3.25rem);font-weight:800;letter-spacing:-.02em;line-height:1.12;margin:0 0 .9rem}
.lvl-sub{max-width:640px;margin:0 auto 1.35rem;color:var(--sk-mute);font-size:1.06rem;line-height:1.65}
.lvl-exams{border-color:rgba(192,132,252,.40);color:#e9d5ff;background:rgba(147,51,234,.10);font-weight:600;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);transition:border-color .2s,background .2s,color .2s,box-shadow .2s}
.lvl-exams:hover,.lvl-exams:focus-visible{border-color:rgba(192,132,252,.75);background:rgba(147,51,234,.20);color:#fff;box-shadow:0 0 18px rgba(139,92,246,.25)}

/* Equal-height card shells */
.sk-dash main .level-card-link,.sk-dash main .level-card-locked{display:flex;flex-direction:column;height:100%}

/* Glass level card */
.sk-dash main .level-card{--lvl-rgb:167,139,250;display:flex;flex-direction:column;position:relative;overflow:hidden;background:linear-gradient(165deg,rgba(255,255,255,.065),rgba(255,255,255,.020) 55%,rgba(255,255,255,.035));border:1px solid rgba(192,132,252,.20);border-radius:20px;backdrop-filter:blur(18px) saturate(150%);-webkit-backdrop-filter:blur(18px) saturate(150%);box-shadow:0 12px 32px rgba(4,2,16,.42),inset 0 1px 0 rgba(255,255,255,.07);transition:transform .25s ease,border-color .25s ease,box-shadow .25s ease,background .25s ease}
.sk-dash main .level-card::before{content:"";position:absolute;top:0;left:16px;right:16px;height:1px;z-index:0;background:linear-gradient(90deg,transparent,rgba(var(--lvl-rgb),.45),transparent)}
.sk-dash main .level-card::after{content:"";position:absolute;top:-72px;right:-72px;width:180px;height:180px;border-radius:50%;z-index:0;pointer-events:none;background:radial-gradient(circle,rgba(var(--lvl-rgb),.13),transparent 68%)}
.sk-dash main .level-card > *:not(.level-step){position:relative;z-index:1}

/* Restrained accent palette (violet · blue · cyan · amber · pink · green) */
.sk-dash main .lvl-violet{--lvl-rgb:167,139,250}
.sk-dash main .lvl-blue{--lvl-rgb:96,165,250}
.sk-dash main .lvl-cyan{--lvl-rgb:34,211,238}
.sk-dash main .lvl-amber{--lvl-rgb:251,191,36}
.sk-dash main .lvl-pink{--lvl-rgb:244,114,182}
.sk-dash main .lvl-green{--lvl-rgb:52,211,153}

/* Badge · title · description · decorative step */
.sk-dash main .level-badge{background:rgba(var(--lvl-rgb),.14);color:rgb(var(--lvl-rgb));border:1px solid rgba(var(--lvl-rgb),.35);font-weight:700;letter-spacing:.02em}
.sk-dash main .level-current{background:linear-gradient(135deg,#8b5cf6,#a855f7 60%,#c026d3);border:1px solid rgba(192,132,252,.55);font-weight:700;letter-spacing:.02em;box-shadow:0 6px 16px rgba(139,92,246,.35)}
.sk-dash main .level-title{font-size:1.18rem;font-weight:700;letter-spacing:.01em;line-height:1.35}
.sk-dash main .level-desc{color:rgba(226,220,255,.70);line-height:1.6}
.sk-dash main .level-step{position:absolute;top:54px;right:16px;z-index:0;font-size:2.4rem;font-weight:800;line-height:1;color:rgba(255,255,255,.055);user-select:none}
.sk-dash main .arrow-icon{transition:transform .25s ease,color .25s ease}

/* Hover / focus (unlocked) */
.sk-dash main .level-card-link:hover .level-card,.sk-dash main .level-card-link:focus-visible .level-card{transform:translateY(-6px);border-color:rgba(var(--lvl-rgb),.55);background:linear-gradient(165deg,rgba(255,255,255,.085),rgba(var(--lvl-rgb),.05) 55%,rgba(255,255,255,.035));box-shadow:0 18px 40px rgba(4,2,16,.50),0 0 24px rgba(var(--lvl-rgb),.16),inset 0 1px 0 rgba(255,255,255,.08)}
.sk-dash main .level-card-link:hover .arrow-icon,.sk-dash main .level-card-link:focus-visible .arrow-icon{color:rgb(var(--lvl-rgb))!important;transform:translateX(4px)}
.sk-dash main .level-card-link:focus-visible{outline:none}

/* Primary action card (first unlocked, not completed — A1 for new learners) */
.sk-dash main .level-card-link.is-primary .level-card{border-color:rgba(168,85,247,.55);background:linear-gradient(165deg,rgba(168,85,247,.17),rgba(139,92,246,.05) 55%,rgba(255,255,255,.03));box-shadow:0 16px 40px rgba(4,2,16,.48),0 0 0 1px rgba(168,85,247,.22),0 0 26px rgba(168,85,247,.18),inset 0 1px 0 rgba(255,255,255,.09)}
.sk-dash main .level-card-link.is-primary:hover .level-card{border-color:rgba(216,180,254,.70);box-shadow:0 20px 46px rgba(4,2,16,.52),0 0 0 1px rgba(168,85,247,.32),0 0 32px rgba(168,85,247,.26),inset 0 1px 0 rgba(255,255,255,.10)}
.sk-dash main .level-cta{display:flex;align-items:center;justify-content:center;gap:.55rem;margin:1.2rem 0 0;margin-top:auto;padding:.82rem 1.1rem;border-radius:14px;text-align:center;color:#fff;font-size:.95rem;font-weight:700;background:linear-gradient(135deg,#8b5cf6,#a855f7 60%,#c026d3);box-shadow:0 10px 24px rgba(139,92,246,.42);transition:transform .2s ease,box-shadow .2s ease}
.sk-dash main .level-card-link:hover .level-cta{transform:translateY(-2px);box-shadow:0 14px 30px rgba(139,92,246,.55)}

/* Locked: readable but clearly locked (behavior unchanged) */
.sk-dash main .level-card-locked{cursor:not-allowed;opacity:.62;filter:grayscale(.5) brightness(.92);pointer-events:none}
.sk-dash main .level-card-locked .level-card{background:rgba(255,255,255,.020);border-color:rgba(255,255,255,.07);box-shadow:inset 0 1px 0 rgba(255,255,255,.04)}
.sk-dash main .level-card-locked .level-card::after{display:none}
.sk-dash main .level-card-locked .level-badge{background:rgba(255,255,255,.06);color:rgba(226,220,255,.65);border-color:rgba(255,255,255,.12)}

/* Respect reduced motion */
@media (prefers-reduced-motion:reduce){
  .sk-dash main .level-card,.sk-dash main .level-cta,.sk-dash main .arrow-icon,.lvl-exams{transition:none!important}
  .sk-dash main .level-card-link:hover .level-card,.sk-dash main .level-card-link:focus-visible .level-card,.sk-dash main .level-card-link:hover .level-cta,.sk-dash main .level-card-link:hover .arrow-icon{transform:none!important}
}
</style>
@endpush

@section('content')
<div class="container py-5" style="min-height: 75vh;">


    <!-- Header Title -->
    <div class="text-center mb-5">
        <h2 class="lvl-h gradient-text">Choose Your Japanese Level</h2>
        <p class="lvl-sub">Select your current proficiency to get started with tailored courses.</p>
        @if(Auth::user()->role === 'student')
            <a href="{{ route('student.exams.index') }}" class="btn btn-outline-light rounded-pill px-4 mt-2 lvl-exams">
                <i class="bi bi-journal-check me-2"></i>Exams
            </a>
        @endif
    </div>

    <!-- Levels Grid -->
    <div class="row g-4 justify-content-center">
        @php($primaryClaimed = false)
        @forelse($levels as $level)
            @php($isUnlocked = in_array((int) $level->id, $accessibleLevelIds, true))
            @php($isCompleted = in_array((int) $level->id, $completedLevelIds, true))
            @php($isPrimary = $isUnlocked && !$isCompleted && !$primaryClaimed)
            @php($primaryClaimed = $primaryClaimed || $isPrimary)
            @php($lvlAccent = ['lvl-violet','lvl-blue','lvl-cyan','lvl-amber','lvl-pink','lvl-green'][($loop->index ?? 0) % 6])
            <div class="col-12 col-md-6 col-lg-4">
                @if($isUnlocked)
                    <a href="{{ route('levels.courses', $level->id) }}" class="text-decoration-none level-card-link{{ $isPrimary ? ' is-primary' : '' }}">
                @else
                    <div class="text-decoration-none level-card-locked" aria-disabled="true" aria-label="{{ $level->name }} is locked">
                @endif
                    <div class="level-card {{ $lvlAccent }} p-4 h-100 position-relative">
                        <div class="level-card-top d-flex align-items-center justify-content-between mb-3">
                            <span class="badge level-badge px-3 py-2 rounded-pill fs-5">{{ $level->name }}</span>
                            @if($isUnlocked)
                                @if($isCompleted)
                                    <span class="badge bg-success text-white me-2"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                                @elseif($isPrimary)
                                    <span class="badge level-current text-white me-2"><i class="bi bi-bookmark-star-fill me-1"></i>Current Level</span>
                                @else
                                    <span class="small fw-semibold text-success me-2">Start Learning</span>
                                    
                                    <i class="bi bi-arrow-right fs-4 text-white-50 arrow-icon"></i>
                                @endif
                            @else
                                <span class="badge bg-secondary text-white"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                            @endif
                        </div>
                        <h4 class="level-title fw-bold mb-2 text-white">{{ $level->title ?? $level->name }}</h4>
                        <p class="level-desc small mb-0">{{ $level->description }}</p>
                        @if($isPrimary)
                            <span class="level-cta">Start Learning <i class="bi bi-arrow-right" aria-hidden="true"></i>
                         
                        </span>
                       
                        @endif
                        <span class="level-step" aria-hidden="true">{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
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
@endsection
