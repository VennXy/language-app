{{--
  Student Learning Dashboard — visual redesign pass 2 (reference-matched composition)
  Page-scoped only: no backend logic, routes, services, models, or shared layout changes.
  Structure: fixed sakura sidebar | compact horizontal hero | 3 stat cards |
             Progress by Level + Recent Activity | wide Continue Learning card.
--}}
@section('student_shell', true)
@extends('layouts.app')

@push('styles')
<style>
/* ================= Hero ================= */
.sk-dash .sk-hero{
  position:relative; display:flex; align-items:stretch; overflow:hidden;
  min-height:330px; margin-bottom:24px; border-radius:22px;
}
.sk-dash .sk-hero-art{position:absolute; inset:0;}
.sk-dash .sk-hero-art svg{position:absolute; inset:0; width:100%; height:100%; display:block;}
.sk-dash .sk-hero-veil{
  position:absolute; inset:0; pointer-events:none;
  background:linear-gradient(95deg,
    rgba(9,5,24,.95) 0%, rgba(9,5,24,.90) 38%,
    rgba(9,5,24,.50) 60%, rgba(9,5,24,.10) 82%, rgba(9,5,24,.32) 100%);
}
.sk-dash .sk-hero-left{
  position:relative; z-index:2; width:min(60%,640px);
  padding:32px 36px 30px; display:flex; flex-direction:column; gap:15px;
}
.sk-dash .sk-toprow{display:flex; align-items:center; gap:13px;}
.sk-dash .sk-avatar{
  flex:0 0 auto; width:52px; height:52px; border-radius:50%;
  display:grid; place-items:center; font-weight:800; font-size:1.2rem; color:#fff;
  background:linear-gradient(135deg,#8b5cf6,#ec4899);
  border:2px solid rgba(250,205,255,.55);
  box-shadow:0 0 18px rgba(236,72,153,.45);
}
.sk-dash .sk-hello{
  margin:0; font-size:clamp(1.22rem,1.9vw,1.6rem); font-weight:800;
  line-height:1.25; letter-spacing:.01em;
}
.sk-dash .sk-sub{margin:3px 0 0; font-size:.84rem; color:var(--sk-mute); line-height:1.5;}
.sk-dash .sk-level-row{display:flex; align-items:center; gap:13px;}
.sk-dash .sk-level-badge{
  flex:0 0 auto; min-width:54px; height:52px; padding:0 12px; border-radius:14px;
  display:grid; place-items:center; font-weight:800; font-size:1.08rem; color:#fff;
  background:linear-gradient(140deg,rgba(168,85,247,.55),rgba(236,72,153,.42));
  border:1px solid rgba(250,205,255,.50);
  box-shadow:0 0 16px rgba(168,85,247,.40);
}
.sk-dash .sk-level-meta span{
  display:block; font-size:.66rem; font-weight:700; letter-spacing:.16em;
  text-transform:uppercase; color:#f9a8d4;
}
.sk-dash .sk-level-meta b{display:block; margin-top:3px; font-size:.92rem; font-weight:600; color:#ede9fe;}
.sk-dash .sk-prog{
  padding:14px 16px; border-radius:14px;
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.20);
  backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px);
}
.sk-dash .sk-prog-head{
  display:flex; align-items:baseline; justify-content:space-between; gap:10px;
  font-size:.74rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
  color:var(--sk-mute);
}
.sk-dash .sk-prog-head strong{
  font-size:1.45rem; font-weight:800; letter-spacing:0; text-transform:none;
  background:linear-gradient(120deg,#e9d5ff,#f9a8d4);
  -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
}
.sk-dash .sk-prog-track{
  height:9px; margin:10px 0 9px; border-radius:99px; overflow:hidden;
  background:rgba(255,255,255,.09);
}
.sk-dash .sk-prog-fill{
  height:100%; border-radius:inherit;
  background:linear-gradient(90deg,#8b5cf6,#ec4899);
  box-shadow:0 0 12px rgba(168,85,247,.60);
  transition:width .6s ease;
}
.sk-dash .sk-prog-foot{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  font-size:.74rem; color:var(--sk-mute);
}
.sk-dash .sk-hero-cta{display:flex; flex-wrap:wrap; gap:12px;}
.sk-dash .sk-nextup{
  margin:0; font-size:.8rem; color:#fbcfe8;
  display:flex; align-items:center; gap:7px;
}
.sk-dash .sk-nextup i{color:#f472b6;}
.sk-dash .sk-quote{
  position:absolute; right:24px; bottom:22px; z-index:3; max-width:236px; margin:0;
  padding:16px 18px; border-radius:16px;
  background:rgba(15,8,34,.66);
  border:1px solid rgba(250,205,255,.30);
  backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px);
  box-shadow:0 10px 28px rgba(4,2,16,.50), 0 0 22px rgba(236,72,153,.20);
}
.sk-dash .sk-quote p{margin:0; font-size:1.04rem; font-weight:700; line-height:1.65; color:#fce7f3;}
.sk-dash .sk-quote small{
  display:block; margin-top:9px; font-size:.7rem; line-height:1.55;
  font-style:italic; color:rgba(226,220,255,.72);
}
/* ================= Stat cards (exactly 3) ================= */
.sk-dash .sk-stats{
  display:grid; grid-template-columns:repeat(3,minmax(0,1fr));
  gap:20px; margin-bottom:24px;
}
.sk-dash .sk-stat{
  display:flex; align-items:center; gap:14px; padding:17px 18px; border-radius:16px;
  transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.sk-dash .sk-stat:hover{
  transform:translateY(-4px); border-color:rgba(216,180,254,.42);
  box-shadow:0 16px 40px rgba(4,2,16,.50), 0 0 26px rgba(168,85,247,.18);
}
.sk-dash .sk-stat-ic{
  flex:0 0 auto; width:48px; height:48px; border-radius:14px;
  display:grid; place-items:center; font-size:1.32rem;
}
.sk-dash .sk-ic-flame{
  background:radial-gradient(circle at 30% 25%,rgba(251,146,60,.38),rgba(249,115,22,.14));
  border:1px solid rgba(251,146,60,.50); color:#fb923c;
  box-shadow:0 0 16px rgba(251,146,60,.35);
}
.sk-dash .sk-ic-heart{
  background:radial-gradient(circle at 30% 25%,rgba(244,114,182,.38),rgba(236,72,153,.14));
  border:1px solid rgba(244,114,182,.50); color:#f472b6;
  box-shadow:0 0 16px rgba(244,114,182,.35);
}
.sk-dash .sk-ic-gem{
  background:radial-gradient(circle at 30% 25%,rgba(167,139,250,.40),rgba(139,92,246,.14));
  border:1px solid rgba(167,139,250,.55); color:#a78bfa;
  box-shadow:0 0 16px rgba(167,139,250,.35);
}
.sk-dash .sk-stat-body{flex:1; min-width:0;}
.sk-dash .sk-stat-label{
  display:block; font-size:.66rem; font-weight:700; letter-spacing:.17em;
  text-transform:uppercase; color:var(--sk-mute);
}
.sk-dash .sk-stat-value{display:block; margin-top:3px; font-size:1.6rem; font-weight:800; line-height:1.12;}
.sk-dash .sk-stat-value em{font-size:.76rem; font-weight:600; font-style:normal; color:var(--sk-mute);}
.sk-dash .sk-stat-sub{display:block; margin-top:4px; font-size:.7rem; color:rgba(226,220,255,.60);}
.sk-dash .sk-stat-sub.is-ok{color:#86efac; font-weight:700;}
.sk-dash .sk-stat-arrow{margin-left:auto; font-size:.95rem; color:rgba(216,180,254,.55);}

/* ================= Two-column panels ================= */
.sk-dash .sk-grid{
  display:grid; grid-template-columns:minmax(0,7fr) minmax(0,5fr);
  gap:22px; margin-bottom:24px; align-items:start;
}
.sk-dash .sk-panel{padding:24px 24px 16px;}
.sk-dash .sk-panel-head{
  display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
  margin-bottom:12px;
}
.sk-dash .sk-panel-head h2{margin:0; font-size:1.02rem; font-weight:800;}
.sk-dash .sk-panel-head p{margin:4px 0 0; font-size:.74rem; color:var(--sk-mute);}
.sk-dash .sk-panel-link{
  font-size:.75rem; font-weight:700; color:#f9a8d4; text-decoration:none; white-space:nowrap;
}
.sk-dash .sk-panel-link:hover{color:#fbcfe8; text-decoration:underline;}
.sk-dash .sk-levels{list-style:none; margin:0; padding:0;}
.sk-dash .sk-level{
  display:flex; align-items:center; gap:14px; flex-wrap:nowrap;
  padding:13px 6px; border-top:1px solid rgba(255,255,255,.06);
  text-decoration:none; color:inherit;
  transition:background .2s ease, border-radius .2s ease;
}
.sk-dash .sk-level:first-child{border-top:0;}
.sk-dash a.sk-level:hover{background:rgba(255,255,255,.05); border-radius:12px;}
.sk-dash .sk-lvl-badge{
  flex:0 0 auto; width:46px; height:34px; border-radius:10px;
  display:grid; place-items:center; font-size:.8rem; font-weight:800;
  white-space:nowrap; overflow:hidden; padding:0 6px; line-height:1;
  background:rgba(168,85,247,.22); border:1px solid rgba(216,180,254,.40); color:#e9d5ff;
}
.sk-dash .sk-level.is-done .sk-lvl-badge{
  background:rgba(34,197,94,.18); border-color:rgba(134,239,172,.45); color:#86efac;
}
.sk-dash .sk-level.is-locked .sk-lvl-badge{
  background:rgba(255,255,255,.05); border-color:rgba(255,255,255,.14); color:rgba(226,220,255,.45);
}
.sk-dash .sk-lvl-mid{flex:1 1 auto; min-width:0;}
.sk-dash .sk-lvl-name{
  display:block; margin-bottom:3px;
  font-size:.85rem; font-weight:600; line-height:1.4;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.sk-dash .sk-lvl-desc{
  display:block; margin-bottom:3px;
  font-size:.72rem; line-height:1.45; color:var(--sk-mute);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.sk-dash .sk-level.is-locked .sk-lvl-name{color:rgba(226,220,255,.55);}
.sk-dash .sk-lvl-track{
  margin-top:4px;
  height:7px; border-radius:99px; overflow:hidden; background:rgba(255,255,255,.08);
}
.sk-dash .sk-lvl-fill{
  height:100%; border-radius:inherit;
  background:linear-gradient(90deg,#8b5cf6,#ec4899);
  box-shadow:0 0 10px rgba(168,85,247,.55); transition:width .5s ease;
}
.sk-dash .sk-level.is-locked .sk-lvl-fill{background:rgba(255,255,255,.14); box-shadow:none;}
.sk-dash .sk-lvl-pct{
  flex:0 0 auto; width:44px; text-align:right;
  font-size:.8rem; font-weight:800; color:#e9d5ff;
}
.sk-dash .sk-level.is-locked .sk-lvl-pct{color:rgba(226,220,255,.40);}
.sk-dash .sk-lvl-state{
  flex:0 0 auto; display:flex; align-items:center; justify-content:flex-end; gap:10px;
}
.sk-dash .sk-lvl-chev{flex:0 0 auto; font-size:.78rem; color:rgba(216,180,254,.50);}
/* Progress rows: dedicated zones so badge/description/percent/status never overlap.
   Mobile: badge + status + percent on the first line, description + bar full width below. */
@media (max-width:575.98px){
  .sk-dash .sk-level{flex-wrap:wrap; row-gap:9px; column-gap:12px; padding:14px 6px;}
  .sk-dash .sk-lvl-badge{order:1;}
  .sk-dash .sk-lvl-state{order:2; margin-left:auto;}
  .sk-dash .sk-lvl-pct{order:3;}
  .sk-dash .sk-lvl-mid{order:4; flex:1 0 100%;}
  .sk-dash .sk-lvl-name{white-space:normal; overflow:visible; text-overflow:clip;}
  .sk-dash .sk-lvl-desc{white-space:normal; overflow:visible; text-overflow:clip;}
}
/* ================= Recent activity ================= */
.sk-dash .sk-acts{list-style:none; margin:0; padding:0;}
.sk-dash .sk-act{
  display:flex; align-items:center; gap:12px;
  padding:12px 6px; border-top:1px solid rgba(255,255,255,.06);
  text-decoration:none; color:inherit;
  transition:background .2s ease;
}
.sk-dash .sk-act:first-child{border-top:0;}
.sk-dash a.sk-act:hover{background:rgba(255,255,255,.05); border-radius:12px;}
.sk-dash .sk-act-ic{
  flex:0 0 auto; width:38px; height:38px; border-radius:11px;
  display:grid; place-items:center; font-size:.95rem; color:#f9a8d4;
  background:rgba(236,72,153,.16); border:1px solid rgba(244,114,182,.35);
}
.sk-dash .sk-act-mid{flex:1; min-width:0;}
.sk-dash .sk-act-title{
  display:block; font-size:.85rem; font-weight:650;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.sk-dash .sk-act-meta{display:block; margin-top:2px; font-size:.7rem; color:var(--sk-mute);}
.sk-dash .sk-act-right{
  display:flex; flex-direction:column; align-items:flex-end; gap:4px; flex:0 0 auto;
}
.sk-dash .sk-act-time{font-size:.66rem; color:rgba(226,220,255,.50); white-space:nowrap;}
.sk-dash .sk-empty{
  padding:28px 8px; text-align:center; font-size:.8rem; color:var(--sk-mute);
}

/* ================= Continue Learning (wide bottom card) ================= */
.sk-dash .sk-continue{
  position:relative; display:flex; align-items:center; gap:22px;
  padding:22px 26px; border-radius:20px; overflow:hidden;
}
.sk-dash .sk-continue::after{
  content:""; position:absolute; right:-40px; top:-60px; width:240px; height:240px;
  border-radius:50%; pointer-events:none;
  background:radial-gradient(circle,rgba(236,72,153,.22),transparent 70%);
}
.sk-dash .sk-cont-art{
  flex:0 0 auto; width:78px; height:78px; border-radius:18px;
  display:grid; place-items:center; font-size:2rem;
  background:linear-gradient(140deg,rgba(168,85,247,.40),rgba(236,72,153,.34));
  border:1px solid rgba(250,205,255,.45);
  box-shadow:0 0 22px rgba(216,180,254,.35);
}
.sk-dash .sk-cont-mid{flex:1; min-width:0; position:relative; z-index:1;}
.sk-dash .sk-cont-kicker{
  font-size:.66rem; font-weight:700; letter-spacing:.2em;
  text-transform:uppercase; color:#f9a8d4;
}
.sk-dash .sk-cont-mid h3{margin:5px 0 4px; font-size:1.18rem; font-weight:800;}
.sk-dash .sk-cont-mid p{margin:0; font-size:.8rem; color:var(--sk-mute);}
.sk-dash .sk-cont-cta{position:relative; z-index:1; flex:0 0 auto;}

/* ================= Responsive ================= */
@media (max-width:991.98px){
  .sk-dash .sk-hero{min-height:0;}
  .sk-dash .sk-hero-left{width:100%; padding:26px 22px 24px;}
  .sk-dash .sk-hero-veil{
    background:linear-gradient(180deg,rgba(9,5,24,.93),rgba(9,5,24,.87) 55%,rgba(9,5,24,.80));
  }
  .sk-dash .sk-quote{
    position:static; max-width:none; margin:0 22px 22px;
    display:flex; flex-wrap:wrap; align-items:baseline; gap:6px 14px;
  }
  .sk-dash .sk-quote p, .sk-dash .sk-quote small{margin:0;}
  .sk-dash .sk-stats{grid-template-columns:repeat(auto-fit,minmax(215px,1fr));}
  .sk-dash .sk-grid{grid-template-columns:1fr;}
}
@media (max-width:575.98px){
  .sk-dash .sk-stats{grid-template-columns:1fr;}
  .sk-dash .sk-hero-cta .sk-btn{flex:1; justify-content:center;}
  .sk-dash .sk-toprow{align-items:flex-start;}
  .sk-dash .sk-continue{flex-direction:column; text-align:center; gap:16px; padding:22px 18px;}
  .sk-dash .sk-cont-cta{width:100%;}
  .sk-dash .sk-cont-cta .sk-btn{width:100%; justify-content:center;}
  .sk-dash .sk-panel{padding:20px 16px 12px;}
  .sk-dash .sk-lvl-pct{width:38px;}
}
</style>
@endpush
@section('content')
        {{-- HERO: compact horizontal dashboard hero with integrated artwork --}}
        <section class="sk-hero sk-glass" aria-label="Welcome back">
            <div class="sk-hero-art" aria-hidden="true">
                <svg viewBox="0 0 1200 340" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <defs>
                        <linearGradient id="skSky" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#140a33"/>
                            <stop offset=".55" stop-color="#1d1047"/>
                            <stop offset="1" stop-color="#3d1259"/>
                        </linearGradient>
                        <linearGradient id="skFuji" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#6d3bb5"/>
                            <stop offset="1" stop-color="#2a1553"/>
                        </linearGradient>
                        <radialGradient id="skMoonG">
                            <stop offset="0" stop-color="#fde68a" stop-opacity=".85"/>
                            <stop offset="1" stop-color="#fde68a" stop-opacity="0"/>
                        </radialGradient>
                        <linearGradient id="skTorii" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#f472b6"/>
                            <stop offset="1" stop-color="#be185d"/>
                        </linearGradient>
                        <linearGradient id="skGround" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#1c0f42"/>
                            <stop offset="1" stop-color="#0d0624"/>
                        </linearGradient>
                    </defs>
                    <rect width="1200" height="340" fill="url(#skSky)"/>
                    <circle cx="620" cy="42" r="1.7" fill="#fff" opacity=".75"/>
                    <circle cx="726" cy="74" r="1.3" fill="#fff" opacity=".6"/>
                    <circle cx="862" cy="34" r="1.8" fill="#fff" opacity=".8"/>
                    <circle cx="1142" cy="62" r="1.5" fill="#fff" opacity=".7"/>
                    <circle cx="648" cy="118" r="1.2" fill="#fff" opacity=".5"/>
                    <circle cx="905" cy="148" r="1.4" fill="#fff" opacity=".55"/>
                    <circle cx="1164" cy="146" r="1.3" fill="#fff" opacity=".5"/>
                    <circle cx="778" cy="160" r="1.2" fill="#fff" opacity=".45"/>
                    <circle cx="965" cy="72" r="66" fill="url(#skMoonG)"/>
                    <circle cx="965" cy="72" r="30" fill="#fef3c7" opacity=".92"/>
                    <circle cx="954" cy="64" r="5" fill="#fde68a" opacity=".55"/>
                    <circle cx="976" cy="83" r="7" fill="#fde68a" opacity=".4"/>
                    <path d="M740 110 q42 -20 84 0 q36 -12 64 8 q-32 16 -74 12 q-44 6 -74 -20Z" fill="#c084fc" opacity=".16"/>
                    <path d="M1006 178 q34 -14 68 0 q28 -8 50 8 q-26 12 -58 8 q-36 4 -60 -16Z" fill="#f9a8d4" opacity=".13"/>
                    <path d="M455 302 L700 92 L948 302 Z" fill="url(#skFuji)" opacity=".96"/>
                    <path d="M700 92 L948 302 L866 302 Z" fill="#000" opacity=".16"/>
                    <path d="M645 140 L700 92 L757 142 C743 131 730 139 716 131 C702 123 689 134 675 130 C664 127 654 134 645 140 Z" fill="#ede9fe" opacity=".9"/>
                    <rect y="288" width="1200" height="52" fill="url(#skGround)"/>
                    <ellipse cx="1068" cy="300" rx="120" ry="10" fill="#f472b6" opacity=".12"/>
                    <rect x="1014" y="178" width="13" height="124" rx="3" fill="url(#skTorii)"/>
                    <rect x="1102" y="178" width="13" height="124" rx="3" fill="url(#skTorii)"/>
                    <path d="M984 160 Q1068 146 1152 160 L1152 177 Q1068 163 984 177 Z" fill="url(#skTorii)"/>
                    <rect x="998" y="194" width="132" height="11" rx="3" fill="#db2777"/>
                    <rect x="1055" y="177" width="18" height="19" fill="#db2777"/>
                    <path d="M1200 12 C1132 24 1080 52 1042 90 C1018 114 1000 132 986 148" stroke="#2b0f52" stroke-width="7" fill="none" stroke-linecap="round"/>
                    <path d="M1112 40 q14 22 12 46" stroke="#2b0f52" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M1036 94 q-20 8 -34 28" stroke="#2b0f52" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <g fill="#f9a8d4" opacity=".92">
                        <circle cx="1114" cy="34" r="6"/><circle cx="1126" cy="38" r="6"/>
                        <circle cx="1122" cy="50" r="6"/><circle cx="1108" cy="48" r="6"/>
                        <circle cx="1054" cy="66" r="6"/><circle cx="1066" cy="70" r="6"/>
                        <circle cx="1060" cy="82" r="6"/><circle cx="1046" cy="78" r="6"/>
                        <circle cx="1004" cy="112" r="5.5"/><circle cx="1016" cy="116" r="5.5"/>
                        <circle cx="1010" cy="128" r="5.5"/><circle cx="996" cy="124" r="5.5"/>
                        <circle cx="978" cy="146" r="5"/><circle cx="990" cy="152" r="5"/>
                        <circle cx="982" cy="162" r="5"/>
                    </g>
                    <g fill="#fde68a">
                        <circle cx="1116" cy="43" r="2.6"/><circle cx="1055" cy="75" r="2.6"/>
                        <circle cx="1005" cy="121" r="2.4"/><circle cx="984" cy="154" r="2.2"/>
                    </g>
                    <ellipse cx="1028" cy="192" rx="4" ry="6" fill="#fbcfe8" opacity=".75" transform="rotate(24 1028 192)"/>
                    <ellipse cx="958" cy="232" rx="4" ry="6" fill="#fbcfe8" opacity=".6" transform="rotate(-18 958 232)"/>
                    <ellipse cx="884" cy="182" rx="3.5" ry="5.5" fill="#fbcfe8" opacity=".5" transform="rotate(40 884 182)"/>
                </svg>
            </div>
            <div class="sk-hero-veil" aria-hidden="true"></div>
            <div class="sk-hero-left">
                <div class="sk-toprow">
                    <span class="sk-avatar" aria-hidden="true">{{ mb_substr(trim(auth()->user()->name ?? 'S'), 0, 1) }}</span>
                    <div>
                        <h1 class="sk-hello">おかえりなさい、{{ auth()->user()->name }} さん</h1>
                        <p class="sk-sub">Your learning dashboard — 今日もコツコツ、一歩ずつ進めよう。</p>
                    </div>
                </div>

                <div class="sk-level-row">
                    <span class="sk-level-badge">{{ $currentLevel?->name ?? '—' }}</span>
                    <div class="sk-level-meta">
                        <span>Current level · Tier {{ $currentLevel?->tier ?? '—' }}</span>
                        <b>{{ $currentLevel?->description ?? 'Start your first lesson' }}</b>
                    </div>
                </div>

                <div class="sk-prog">
                    <div class="sk-prog-head">
                        <span>Overall progress</span>
                        <strong><span id="overall-progress-percent">{{ $overallPercent }}</span>%</strong>
                    </div>
                    <div class="sk-prog-track" role="progressbar" aria-valuenow="{{ $overallPercent }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="sk-prog-fill" style="width: {{ $overallPercent }}%"></div>
                    </div>
                    <div class="sk-prog-foot">
                        <span id="lesson-count-summary">{{ $completedLessonCount }} of {{ $totalLessonCount }} lessons completed</span>
                        <span>{{ $currentLevel?->name ?? '' }} track</span>
                    </div>
                </div>

                <div class="sk-hero-cta">
                    @if($nextLesson)
                        <a class="sk-btn sk-btn-primary" href="{{ route('user.training', ['id' => $nextLesson->id, 'type' => 'training']) }}">Continue Learning <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @else
                        <a class="sk-btn sk-btn-primary" href="{{ route('select.level') }}">Browse lessons <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                    <a class="sk-btn sk-btn-ghost" href="{{ $currentLevel ? route('levels.courses', $currentLevel->id) : route('select.level') }}">Levels &amp; Courses</a>
                </div>

                @if($nextLesson)
                    <p class="sk-nextup"><i class="bi bi-play-circle-fill" aria-hidden="true"></i> Next up: {{ $nextLesson->title }}</p>
                @endif
            </div>

            <figure class="sk-quote">
                <p lang="ja">一歩ずつ、<br>夢に近づく</p>
                <small>One step at a time,<br>closer to your dreams.</small>
            </figure>
        </section>
        {{-- STATS: exactly 3 cards — Daily Streak, Hearts, Gems --}}
        <section class="sk-stats" aria-label="Daily stats">
            <div class="sk-stat sk-glass">
                <span class="sk-stat-ic sk-ic-flame" aria-hidden="true"><i class="bi bi-fire"></i></span>
                <div class="sk-stat-body">
                    <span class="sk-stat-label">Daily Streak</span>
                    <span class="sk-stat-value"><span id="stat-daily-streak">{{ $dailyStreak }}</span> <em>days</em></span>
                    @if($streakCompletedToday)
                        <span class="sk-stat-sub is-ok">✓ Completed today</span>
                    @else
                        <span class="sk-stat-sub">Complete a lesson to extend your streak</span>
                    @endif
                </div>
                <i class="bi bi-arrow-right sk-stat-arrow" aria-hidden="true"></i>
            </div>

            <div class="sk-stat sk-glass">
                <span class="sk-stat-ic sk-ic-heart" aria-hidden="true"><i class="bi bi-heart-fill"></i></span>
                <div class="sk-stat-body">
                    <span class="sk-stat-label">Hearts</span>
                    <span class="sk-stat-value"><span id="stat-hearts">{{ $hearts }}/{{ $maxHearts }}</span> <em>hearts</em></span>
                    @if($hearts >= $maxHearts)
                        <span class="sk-stat-sub is-ok">Ready for your next lesson</span>
                    @else
                        <span class="sk-stat-sub">Hearts refill over time</span>
                    @endif
                </div>
                <i class="bi bi-arrow-right sk-stat-arrow" aria-hidden="true"></i>
            </div>

            <div class="sk-stat sk-glass">
                <span class="sk-stat-ic sk-ic-gem" aria-hidden="true"><i class="bi bi-gem"></i></span>
                <div class="sk-stat-body">
                    <span class="sk-stat-label">Gems</span>
                    <span class="sk-stat-value"><span id="stat-gems">{{ $gems }}</span> <em>gems</em></span>
                    <span class="sk-stat-sub">Earned from completed lessons</span>
                </div>
                <i class="bi bi-arrow-right sk-stat-arrow" aria-hidden="true"></i>
            </div>
        </section>
        {{-- CONTENT: Progress by Level (left) + Recent Activity (right) --}}
        <section class="sk-grid">
            <div class="sk-panel sk-glass">
                <div class="sk-panel-head">
                    <div>
                        <h2>Progress by Level</h2>
                        <p>Your journey from A1 to C2 — track every step.</p>
                    </div>
                    <a class="sk-panel-link" href="{{ route('select.level') }}">All levels <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
                <ul class="sk-levels">
                    @foreach($levelSummaries as $summary)
                        @php($rowClass = 'sk-level ' . ($summary['is_completed'] ? 'is-done' : ($summary['is_accessible'] ? '' : 'is-locked')))
                        @if($summary['is_accessible'])
                        <a class="{{ $rowClass }}" href="{{ route('levels.courses', $summary['level']->id) }}">
                        @else
                        <div class="{{ $rowClass }}">
                        @endif
                            <span class="sk-lvl-badge">{{ $summary['level']->level ?: $summary['level']->name }}</span>
                            <div class="sk-lvl-mid">
                                <span class="sk-lvl-name" title="{{ $summary['level']->name }}">{{ $summary['level']->name }}</span>
                                @if($summary['level']->description)
                                    <span class="sk-lvl-desc" title="{{ $summary['level']->description }}">{{ $summary['level']->description }}</span>
                                @endif
                                <div class="sk-lvl-track">
                                    <div class="sk-lvl-fill" style="width: {{ $summary['percent'] }}%"></div>
                                </div>
                            </div>
                            <span class="sk-lvl-pct">{{ $summary['percent'] }}%</span>
                            <span class="sk-lvl-state">
                                @if($summary['is_completed'])
                                    <span class="sk-pill sk-pill-done">Completed</span>
                                @elseif(!$summary['is_accessible'])
                                    <span class="sk-pill sk-pill-lock">Locked</span>
                                @elseif($summary['percent'] > 0)
                                    <span class="sk-pill sk-pill-prog">In progress</span>
                                @endif
                                @if($summary['is_accessible'])
                                    <i class="bi bi-chevron-right sk-lvl-chev" aria-hidden="true"></i>
                                @else
                                    <i class="bi bi-lock sk-lvl-chev" aria-hidden="true"></i>
                                @endif
                            </span>
                        @if($summary['is_accessible'])
                        </a>
                        @else
                        </div>
                        @endif
                    @endforeach
                </ul>
            </div>

            <div class="sk-panel sk-glass">
                <div class="sk-panel-head">
                    <div>
                        <h2>Recent Activity</h2>
                        <p>Your latest lesson sessions.</p>
                    </div>
                </div>
                <ul class="sk-acts">
                    @forelse($recentActivity as $activity)
                        <a class="sk-act" href="{{ route('user.training', ['id' => $activity->lesson_id, 'type' => 'training']) }}">
                            <span class="sk-act-ic" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                            <span class="sk-act-mid">
                                <span class="sk-act-title">{{ $activity->lesson_title }}</span>
                                <span class="sk-act-meta">{{ $activity->level_name }} lesson</span>
                            </span>
                            <span class="sk-act-right">
                                @if($activity->status === 'completed')
                                    <span class="sk-pill sk-pill-done">Completed</span>
                                @else
                                    <span class="sk-pill sk-pill-prog">In progress</span>
                                @endif
                                <span class="sk-act-time">{{ \Illuminate\Support\Carbon::parse($activity->updated_at)->diffForHumans() }}</span>
                            </span>
                            <i class="bi bi-chevron-right sk-lvl-chev" aria-hidden="true"></i>
                        </a>
                    @empty
                        <li class="sk-empty">No activity yet — complete a lesson and it will show up here. <i class="bi bi-flower1" aria-hidden="true"></i></li>
                    @endforelse
                </ul>
            </div>
        </section>
        {{-- CONTINUE LEARNING: wide bottom card --}}
        @php($continueLessonIndex = $nextLesson ? (int) $currentLevelLessons->search(fn ($lesson) => $lesson->id === $nextLesson->id) + 1 : 0)
        <section class="sk-continue sk-glass" aria-label="Continue learning">
            <div class="sk-cont-art" aria-hidden="true">🌸</div>
            <div class="sk-cont-mid">
                <span class="sk-cont-kicker">Continue Learning</span>
                @if($nextLesson)
                    <h3>{{ $nextLesson->title }}</h3>
                    <p>{{ $currentLevel?->name ?? '' }} · Lesson {{ $continueLessonIndex }} of {{ $currentLevelLessons->count() }}</p>
                @else
                    <h3>All caught up 🎉</h3>
                    <p>Every available lesson is complete — choose a new level to keep your streak alive.</p>
                @endif
            </div>
            <div class="sk-cont-cta">
                @if($nextLesson)
                    <a class="sk-btn sk-btn-primary" href="{{ route('user.training', ['id' => $nextLesson->id, 'type' => 'training']) }}">Start Lesson <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                @else
                    <a class="sk-btn sk-btn-primary" href="{{ route('select.level') }}">Browse lessons <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                @endif
            </div>
        </section>
@endsection
