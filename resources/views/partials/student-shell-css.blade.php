{{-- Shared student shell CSS: design tokens, sakura-night background, glass base, sidebar, buttons/pills (extracted verbatim from the dashboard). --}}
<style>
/* ================= Design tokens ================= */
.sk-dash{
  --sk-violet:#8b5cf6; --sk-purple:#a855f7; --sk-pink:#f472b6; --sk-magenta:#e879f9;
  --sk-text:#f5f3ff; --sk-mute:rgba(226,220,255,.64);
  --sk-line:rgba(192,132,252,.16); --sk-card:rgba(255,255,255,.045);
  --sk-side-w:252px;
  color:var(--sk-text);
  font-family:"Segoe UI",Tahoma,Geneva,Verdana,"Hiragino Kaku Gothic ProN","Yu Gothic",Meiryo,sans-serif;
}

/* ================= Sakura-night background ================= */  
.sk-dash .sk-bg{
  position:fixed; inset:0; z-index:-1; overflow:hidden; pointer-events:none;
  background:
    radial-gradient(900px 520px at 12% -8%, rgba(139,92,246,.22), transparent 62%),
    radial-gradient(760px 480px at 88% 10%, rgba(236,72,153,.14), transparent 60%),
    radial-gradient(1000px 640px at 70% 95%, rgba(109,40,217,.20), transparent 64%),
    linear-gradient(160deg,#0b0620 0%,#120a2e 45%,#170c3a 72%,#0d0624 100%);
}
.sk-dash .sk-orb{
  position:absolute; border-radius:50%; filter:blur(70px); opacity:.5;
  animation:skFloat 16s ease-in-out infinite alternate;
}
.sk-dash .sk-orb-1{width:420px;height:420px;left:-90px;top:4%;background:radial-gradient(circle,rgba(168,85,247,.40),transparent 70%);}
.sk-dash .sk-orb-2{width:380px;height:380px;right:-70px;top:36%;background:radial-gradient(circle,rgba(244,114,182,.30),transparent 70%);animation-delay:-6s;}
.sk-dash .sk-orb-3{width:520px;height:320px;left:38%;bottom:-120px;background:radial-gradient(circle,rgba(109,40,217,.34),transparent 70%);animation-delay:-11s;}
@keyframes skFloat{from{transform:translate3d(0,0,0);}to{transform:translate3d(30px,-36px,0);}}
.sk-dash .sk-petal{
  position:absolute; top:-6vh; width:13px; height:13px; opacity:0;
  background:linear-gradient(135deg,#fbcfe8,#f9a8d4);
  border-radius:70% 30% 65% 35%;
  animation:skDrift 24s linear infinite;
}
@keyframes skDrift{
  0%{transform:translateY(-6vh) rotate(0deg) translateX(0);opacity:0;}
  8%{opacity:.38;}
  100%{transform:translateY(110vh) rotate(340deg) translateX(70px);opacity:0;}
}
.sk-dash .sk-wm{
  position:absolute; right:3vw; bottom:1vh; pointer-events:none; user-select:none;
  font-size:clamp(120px,17vw,250px); font-weight:800; line-height:1;
  color:rgba(244,114,182,.05); letter-spacing:.08em;
}

/* ================= Glass base ================= */
.sk-glass{
  background:var(--sk-card);
  border:1px solid var(--sk-line);
  backdrop-filter:blur(16px) saturate(140%);
  -webkit-backdrop-filter:blur(16px) saturate(140%);
  box-shadow:0 12px 36px rgba(4,2,16,.45), 0 0 22px rgba(139,92,246,.07),
             inset 0 1px 0 rgba(255,255,255,.05);
  border-radius:18px;
}

/* ================= Shell / main ================= */
/* position:relative ONLY — never add z-index here: a stacking context on .sk-main would
   trap Bootstrap modals (z-index ~1050) below the fixed sidebar (z-900) and the drawer
   overlay (z-890), so the exit/session-ended box renders under the chrome and its
   buttons cannot be clicked. Root-level compositing keeps modals above all shell layers. */
.sk-dash .sk-main{position:relative;}
@media (min-width:992px){.sk-dash .sk-main{margin-left:var(--sk-side-w);}}
.sk-dash .sk-wrap{max-width:1280px; margin:0 auto; padding:44px 32px 56px;}
/* ================= Fixed sakura sidebar ================= */
.sk-dash .sk-side{
  position:fixed; left:0; top:0; bottom:0; width:var(--sk-side-w); z-index:900;
  display:flex; flex-direction:column; gap:16px;
  padding:28px 16px 20px;
  background:rgba(12,6,28,.78);
  backdrop-filter:blur(18px); -webkit-backdrop-filter:blur(18px);
  border-right:1px solid rgba(192,132,252,.15);
  box-shadow:8px 0 32px rgba(4,2,16,.35);
  overflow-y:auto; scrollbar-width:thin; scrollbar-color:rgba(192,132,252,.3) transparent;
}
.sk-dash .sk-side::-webkit-scrollbar{width:5px;}
.sk-dash .sk-side::-webkit-scrollbar-thumb{background:rgba(192,132,252,.3);border-radius:9px;}
.sk-dash .sk-brand{display:flex; align-items:center; gap:10px; padding:6px 8px 2px; text-decoration:none;}
.sk-dash .sk-brand-mark{
  width:38px; height:38px; border-radius:12px; display:grid; place-items:center;
  font-size:1.12rem; background:linear-gradient(135deg,rgba(168,85,247,.5),rgba(236,72,153,.45));
  border:1px solid rgba(250,205,255,.45); box-shadow:0 0 16px rgba(216,180,254,.40);
}
.sk-dash .sk-brand-name{
  display:block; font-weight:800; font-size:1.02rem; letter-spacing:.01em;
  background:linear-gradient(120deg,#e9d5ff,#f9a8d4);
  -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent;
}
.sk-dash .sk-brand-sub{display:block; font-size:.6rem; letter-spacing:.18em; color:var(--sk-mute);}
.sk-dash .sk-nav{display:flex; flex-direction:column; gap:4px;}
.sk-dash .sk-nav-a{
  display:flex; align-items:center; gap:11px; width:100%;
  padding:10px 13px; border-radius:12px; text-align:left;
  font-size:.89rem; font-weight:500; text-decoration:none;
  color:rgba(228,224,255,.72); background:transparent;
  border:1px solid transparent; font-family:inherit; cursor:pointer;
  transition:background .22s ease, color .22s ease, box-shadow .22s ease, transform .22s ease;
}
.sk-dash .sk-nav-a i{font-size:1rem; width:20px; text-align:center; color:rgba(216,180,254,.85);}
.sk-dash .sk-nav-a:hover{background:rgba(255,255,255,.06); color:#fff; transform:translateX(2px);}
.sk-dash .sk-nav-a:focus-visible{outline:2px solid rgba(216,180,254,.6); outline-offset:2px;}
.sk-dash .sk-nav-a.is-active{
  background:linear-gradient(90deg,rgba(168,85,247,.30),rgba(236,72,153,.16));
  border-color:rgba(216,180,254,.45); color:#f9a8d4; font-weight:700;
  box-shadow:0 0 18px rgba(168,85,247,.32), inset 0 0 14px rgba(244,114,182,.08);
}
.sk-dash .sk-nav-a.is-active i{color:#fbcfe8;}
.sk-dash .sk-nav-sep{margin:10px 6px 4px; border-top:1px solid rgba(255,255,255,.09);}
.sk-dash .sk-nav-form{margin:0;}
.sk-dash .sk-nav-form button:hover{color:#fff;}
.sk-dash .sk-side-foot{
  margin-top:auto; padding:12px 10px 4px; text-align:center;
  font-size:.64rem; letter-spacing:.1em; color:rgba(226,220,255,.45);
}

/* ================= Buttons / pills / badges ================= */
.sk-btn{
  display:inline-flex; align-items:center; gap:8px; padding:11px 22px;
  border-radius:999px; font-size:.88rem; font-weight:700; text-decoration:none;
  border:1px solid transparent; transition:transform .25s ease, box-shadow .25s ease, background .25s ease;
}
.sk-btn-primary{
  background:linear-gradient(120deg,#9333ea,#db2777); color:#fff;
  box-shadow:0 8px 22px rgba(147,51,234,.40), 0 0 18px rgba(236,72,153,.25);
}
.sk-btn-primary:hover{transform:translateY(-2px); color:#fff;
  box-shadow:0 12px 26px rgba(147,51,234,.50), 0 0 26px rgba(236,72,153,.35);}
.sk-btn-ghost{background:rgba(255,255,255,.05); border-color:rgba(192,132,252,.40); color:#e9d5ff;}
.sk-btn-ghost:hover{transform:translateY(-2px); background:rgba(168,85,247,.18); color:#fff;}
.sk-pill{
  font-size:.62rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  padding:3px 9px; border-radius:99px; white-space:nowrap;
}
.sk-pill-done{background:rgba(34,197,94,.16); color:#86efac; border:1px solid rgba(134,239,172,.40);}
.sk-pill-lock{background:rgba(255,255,255,.06); color:rgba(226,220,255,.55); border:1px solid rgba(255,255,255,.14);}
.sk-pill-prog{background:rgba(59,130,246,.16); color:#93c5fd; border:1px solid rgba(147,197,253,.40);}
/* ================= Responsive ================= */
@media (max-width:991.98px){
  .sk-dash .sk-wrap{padding:32px 18px 44px;}
}

/* ================= Shell utility strip (compact currencies + account) ================= */
.sk-dash .sk-utility{
  position:sticky; top:0; z-index:800;
  background:rgba(12,6,28,.74);
  backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
  border-bottom:1px solid rgba(192,132,252,.14);
}
.sk-dash .sk-utility-inner{
  max-width:1280px; margin:0 auto;
  display:flex; align-items:center; justify-content:flex-end; gap:14px;
  padding:10px 32px;
}
.sk-dash .sk-utility .student-currencies{gap:8px;}
/* Slim strip pill order: Hearts -> Gems -> Streak; avatar follows on the far right. */
.sk-dash .sk-utility .student-currencies > .heart-status{order:1;}
.sk-dash .sk-utility .student-currencies > .currency-gems{order:2;}
.sk-dash .sk-utility .student-currencies > .daily-streak-status{order:3;}
.sk-dash .sk-utility .currency-display{
  padding:5px 12px; border-radius:999px; font-size:.8rem; font-weight:700;
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.22);
}
.sk-dash .sk-utility .dropdown-toggle{gap:6px; font-size:.8rem; font-weight:700; color:rgba(228,224,255,.85);}
.sk-dash .sk-utility .dropdown-toggle .rounded-circle{width:36px !important; height:36px !important; border-color:rgba(250,205,255,.45) !important; box-shadow:0 0 12px rgba(236,72,153,.30);}
.sk-dash .sk-utility .dropdown-menu{right:0; left:auto; min-width:210px;}

/* ================= Mobile drawer ================= */
.sk-burger{
  display:none; flex:0 0 auto; align-items:center; justify-content:center;
  margin-right:auto;
  width:42px; height:42px; border-radius:12px; cursor:pointer;
  background:rgba(168,85,247,.16); border:1px solid rgba(192,132,252,.38);
  color:#fbcfe8; font-size:1.3rem;
  box-shadow:0 4px 14px rgba(4,2,16,.40);
}
.sk-side-overlay{display:none;}
@media (max-width:991.98px){
  .sk-burger{display:flex;}
  .sk-dash .sk-side{
    transform:translateX(-106%);
    transition:transform .3s ease;
    width:min(78vw,300px);
    padding-top:70px;
  }
  .sk-dash .sk-side.is-open{transform:translateX(0);}
  .sk-dash .sk-side-overlay{
    display:block; position:fixed; inset:0; z-index:890;
    background:rgba(6,3,16,.55); opacity:0; pointer-events:none; transition:opacity .3s ease;
  }
  body.sk-nav-open .sk-dash .sk-side-overlay{opacity:1; pointer-events:auto;}
  body.sk-nav-open{overflow:hidden;}
  .sk-dash .sk-utility-inner{padding:10px 18px;}
  .sk-dash .sk-utility .student-currencies{gap:6px; flex-wrap:wrap;}
}

/* ================= Student content theme (all pages inside the shell main) ================= */
.sk-dash main{color:var(--sk-text);}
.sk-dash main .text-muted{color:var(--sk-mute) !important;}
.sk-dash main .text-white-50{color:rgba(226,220,255,.60) !important;}
.sk-dash main h1, .sk-dash main h2, .sk-dash main h3, .sk-dash main h4, .sk-dash main h5{color:var(--sk-text);}
/* Buttons — Dashboard button language */
.sk-dash main .btn{border-radius:999px; font-weight:700; transition:transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease, color .2s ease;}
.sk-dash main .btn-primary{
  background:linear-gradient(120deg,#9333ea,#db2777); border:1px solid rgba(250,205,255,.35); color:#fff !important;
  box-shadow:0 8px 22px rgba(147,51,234,.35), 0 0 14px rgba(236,72,153,.22);
}
.sk-dash main .btn-primary:hover{transform:translateY(-2px); color:#fff !important;
  box-shadow:0 12px 26px rgba(147,51,234,.50), 0 0 20px rgba(236,72,153,.30);}
.sk-dash main .btn-outline-light{
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.40); color:#e9d5ff !important;
}
.sk-dash main .btn-outline-light:hover{background:rgba(168,85,247,.18); border-color:rgba(216,180,254,.60); color:#fff !important; transform:translateY(-2px);}
.sk-dash main .btn-outline-warning{border-color:rgba(251,191,36,.45); color:#fcd34d !important; background:rgba(251,191,36,.06);}
.sk-dash main .btn-outline-warning:hover{background:rgba(251,191,36,.14); color:#fde68a !important;}
.sk-dash main .btn-success{background:linear-gradient(120deg,#8b5cf6,#ec4899); border:1px solid rgba(250,205,255,.30); color:#fff !important;}
.sk-dash main .btn-light{background:rgba(255,255,255,.92); color:#1c1033 !important; border:1px solid rgba(192,132,252,.35);}
.sk-dash main .btn:disabled, .sk-dash main .btn[disabled]{opacity:.5; transform:none; box-shadow:none;}
/* Cards / panels — glass */
.sk-dash main .card{
  background:var(--sk-card); border:1px solid var(--sk-line); border-radius:18px; color:var(--sk-text);
  backdrop-filter:blur(16px) saturate(140%); -webkit-backdrop-filter:blur(16px) saturate(140%);
  box-shadow:0 12px 36px rgba(4,2,16,.45), 0 0 22px rgba(139,92,246,.07), inset 0 1px 0 rgba(255,255,255,.05);
}
.sk-dash main .card.bg-dark{background:var(--sk-card) !important; border-color:var(--sk-line) !important;}
.sk-dash main .rounded-4.bg-secondary{background:rgba(255,255,255,.05) !important; border-color:var(--sk-line) !important;}
/* Alerts */
.sk-dash main .alert{
  background:rgba(168,85,247,.10); border:1px solid rgba(192,132,252,.35); color:#f5f3ff;
  border-radius:14px; backdrop-filter:blur(10px);
}
.sk-dash main .alert-success{background:rgba(34,197,94,.10); border-color:rgba(134,239,172,.40);}
.sk-dash main .alert-danger{background:rgba(239,68,68,.10); border-color:rgba(252,165,165,.40);}
.sk-dash main .alert-info{background:rgba(59,130,246,.10); border-color:rgba(147,197,253,.40);}
/* Badges */
.sk-dash main .badge{letter-spacing:.05em; font-weight:700;}
.sk-dash main .badge.bg-success{background:linear-gradient(120deg,#22c55e,#10b981) !important;}
.sk-dash main .badge.bg-secondary{background:rgba(255,255,255,.07) !important; color:#e9d5ff !important; border:1px solid rgba(192,132,252,.30);}
.sk-dash main .badge.bg-light{background:linear-gradient(120deg,#c084fc,#f9a8d4) !important; color:#2a1148 !important;}
.sk-dash main .badge.bg-danger{background:rgba(239,68,68,.85) !important;}
.sk-dash main .badge.bg-primary{background:linear-gradient(120deg,#9333ea,#db2777) !important;}
/* Progress */
.sk-dash main .progress{background:rgba(255,255,255,.09); border-radius:99px; overflow:hidden;}
.sk-dash main .progress-bar{background:linear-gradient(90deg,#8b5cf6,#ec4899); box-shadow:0 0 12px rgba(168,85,247,.55);}
.sk-dash main .progress-bar.bg-success{background:linear-gradient(90deg,#22c55e,#4ade80) !important;}
/* Forms — no white Bootstrap forms */
.sk-dash main .form-control, .sk-dash main .form-select, .sk-dash main textarea.form-control{
  background:rgba(255,255,255,.05); border:1px solid rgba(192,132,252,.28); color:var(--sk-text);
  border-radius:12px;
}
.sk-dash main .form-control:focus, .sk-dash main .form-select:focus{
  background:rgba(255,255,255,.07); border-color:rgba(216,180,254,.65); color:var(--sk-text);
  box-shadow:0 0 0 .20rem rgba(168,85,247,.22);
}
.sk-dash main .form-control::placeholder{color:rgba(226,220,255,.45);}
.sk-dash main .form-control.bg-dark{background:rgba(255,255,255,.05) !important; color:var(--sk-text) !important;}
.sk-dash main .form-label{color:var(--sk-mute); font-size:.74rem; font-weight:700; letter-spacing:.10em; text-transform:uppercase;}
.sk-dash main .input-group .btn{border-radius:12px;}
.sk-dash main .form-check-input:checked{background-color:#a855f7; border-color:#c084fc;}
/* Settings list-group */
.sk-dash main .list-group-item{background:transparent; color:rgba(228,224,255,.80); border:0;}
/* Misc chrome */
.sk-dash main hr{border-color:rgba(255,255,255,.10) !important;}
.sk-dash main .table{color:var(--sk-text); --bs-table-bg:transparent;}
.sk-dash main .bg-dark{--bs-bg-opacity:1;}

/* ================= Footer clears the fixed sidebar ================= */
.sk-dash-footer{--sk-side-w:252px;}
@media (min-width:992px){
  .sk-dash-footer{margin-left:var(--sk-side-w);}
}

@media (prefers-reduced-motion:reduce){
  .sk-dash .sk-orb, .sk-dash .sk-petal{animation:none !important;}
  .sk-dash .sk-stat:hover, .sk-dash .sk-btn:hover, .sk-dash .sk-nav-a:hover{transform:none !important;}
}
</style>
