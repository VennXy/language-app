@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Leaderboard: glass ranking rows, top-3 emphasis, current-user glow */
.sk-dash main .lb-row{
  background:var(--sk-card); border:1px solid var(--sk-line); border-radius:16px;
  backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
  transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.sk-dash main .lb-row:hover{transform:translateY(-2px); box-shadow:0 14px 34px rgba(4,2,16,.5), 0 0 18px rgba(139,92,246,.14);}
.sk-dash main .lb-row.is-top3{
  background:linear-gradient(135deg, rgba(168,85,247,.16), rgba(236,72,153,.10));
  border-color:rgba(250,205,255,.35); box-shadow:0 0 20px rgba(168,85,247,.18);
}
.sk-dash main .lb-row.is-me{border-color:rgba(216,180,254,.55); box-shadow:0 0 24px rgba(168,85,247,.35), inset 0 0 18px rgba(244,114,182,.08);}
.sk-dash main .leaderboard-rank{min-width:3rem; font-weight:800;}
</style>
@endpush

@section('content')
<div class="container py-5 text-white" style="max-width: 900px; min-height: 65vh;">
    <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">🏆 Leaderboard</h1>
            <p class="text-light opacity-75 mb-0">Compete with other learners and grow your Fire Points.</p>
        </div>
        <a href="{{ route('select.level') }}" class="btn btn-outline-light rounded-pill">Back to learning</a>
    </div>

    <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.28), rgba(219, 39, 119, 0.28)); border: 1px solid rgba(255, 255, 255, 0.12);">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-uppercase fw-semibold text-light opacity-75">Your Rank</div>
                <div class="display-6 fw-bold gradient-text">#{{ $myRank }}</div>
            </div>
            <div class="text-end">
                <div class="fs-4 fw-bold">🔥 {{ $myPoints }} Fire Points</div>
                @unless($inTopList)
                    <div class="small text-light opacity-75">Keep learning to reach the top 20.</div>
                @endunless
            </div>
        </div>
    </div>

    @php($rank = 0)
    @php($previousPoints = null)

    <div class="row g-3">
        @forelse($students as $index => $student)
            @php($rank = ($previousPoints !== null && (int) $student->streak_count === $previousPoints) ? $rank : $index + 1)
            @php($previousPoints = (int) $student->streak_count)
            @php($isMe = (int) $student->id === (int) auth()->id())
            @php($rankBadge = match ($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '#' . $rank })
            <div class="col-12">
                <div class="lb-row p-3 rounded-4 d-flex align-items-center justify-content-between {{ $isMe ? 'is-me' : '' }}{{ $rank <= 3 ? ' is-top3' : '' }}"
                     @if($isMe) style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.32), rgba(219, 39, 119, 0.32)); border: 1px solid rgba(255, 255, 255, 0.18);" @endif>
                    <div class="d-flex align-items-center gap-3">
                        <div class="leaderboard-rank fs-4 fw-bold text-center" style="min-width: 3rem;">{{ $rankBadge }}</div>
                        <div>
                            <div class="fw-bold">{{ $student->name }}</div>
                            @if($isMe)
                                <span class="badge rounded-pill" style="background: rgba(147, 51, 234, 0.6);">You</span>
                            @endif
                        </div>
                    </div>
                    <div class="fs-5 fw-bold">🔥 {{ (int) $student->streak_count }}</div>
                </div>
            </div>
        @empty
            <div class="col-12 py-5 text-center text-light opacity-75">No students on the leaderboard yet.</div>
        @endforelse
    </div>
</div>
@endsection
