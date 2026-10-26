<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SukuraLearn. - Japanese Learning App</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0d061a;
            color: #ffffff;
        }
        
        /* Navbar Styling */
        .navbar {
            background: rgba(13, 6, 26, 0.85) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 1000;
        }

        /* Glowing Pill Active Tab Styling */
        .navbar .nav-link.active {
            background: rgba(147, 51, 234, 0.2) !important;
            border: 1px solid rgba(192, 132, 252, 0.4);
            color: #f472b6 !important;
            box-shadow: 0 0 15px rgba(147, 51, 234, 0.3);
            font-weight: 600;
        }

        .navbar .nav-link:hover {
            color: #ffffff !important;
            background: rgba(192, 132, 252, 0.12);
            border-radius: 50rem;
        }

        /* Utilities */
        .gradient-text {
            background: linear-gradient(135deg, #c084fc, #f472b6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes borderGlow {
            0% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.3), inset 0 0 5px rgba(59, 130, 246, 0.1); }
            50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8), inset 0 0 10px rgba(59, 130, 246, 0.3); }
            100% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.3), inset 0 0 5px rgba(59, 130, 246, 0.1); }
        }

        @keyframes registerBorderGlow {
            0% { box-shadow: 0 0 5px rgba(219, 39, 119, 0.3), inset 0 0 5px rgba(219, 39, 119, 0.1); }
            50% { box-shadow: 0 0 20px rgba(219, 39, 119, 0.8), inset 0 0 10px rgba(219, 39, 119, 0.3); }
            100% { box-shadow: 0 0 5px rgba(219, 39, 119, 0.3), inset 0 0 5px rgba(219, 39, 119, 0.1); }
        }

        /* Shared Glass Button Base Style */
        .liquid-btn-glass {
            position: relative;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 8px 24px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border-radius: 50rem;
            overflow: hidden;
            z-index: 1;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(8px);
            transition: all 0.4s ease;
        }

        .liquid-btn-glass .btn-text {
            position: relative;
            z-index: 2;
            color: #ffffff;
        }

        .liquid-btn-glass .liquid-fill {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            transition: top 0.4s cubic-bezier(0.77, 0, 0.175, 1);
        }

        /* Login Button */
        .btn-login-glass {
            border: 1px solid rgba(59, 130, 246, 0.6);
            animation: borderGlow 3s infinite ease-in-out;
        }
        .btn-login-glass .liquid-fill {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
        }
        .btn-login-glass:hover {
            border-color: rgba(59, 130, 246, 1);
            transform: translateY(-1px);
        }
        .btn-login-glass:hover .liquid-fill {
            top: 0;
        }

        /* Register Button */
        .btn-register-glass {
            border: 1px solid rgba(219, 39, 119, 0.6);
            animation: registerBorderGlow 3s infinite ease-in-out;
        }  
        .btn-register-glass .liquid-fill {
            background: linear-gradient(135deg, #9333ea, #db2777);
        }
        .btn-register-glass:hover {
            border-color: rgba(219, 39, 119, 1);
            transform: translateY(-1px);
        }
        .btn-register-glass:hover .liquid-fill {
            top: 0;
        }

        /* Glassmorphism Dropdown Menu Styling */
        .dropdown-menu-glass {
            background: rgba(13, 6, 26, 0.75) !important; 
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(192, 132, 252, 0.2) !important; 
            border-radius: 12px !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37) !important;
            padding: 8px !important;
        }

        .dropdown-menu-glass .dropdown-item {
            color: #e2e8f0 !important;
            border-radius: 8px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }

        .dropdown-menu-glass .dropdown-item:hover {
            background: rgba(147, 51, 234, 0.2) !important; 
            color: #ffffff !important;
            transform: translateX(4px);
        }

        .dropdown-menu-glass .dropdown-item.text-danger:hover {
            background: rgba(239, 68, 68, 0.2) !important;
            color: #f87171 !important;
        }

        .dropdown-menu-glass .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }  

        .student-currencies {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
        }

        .currency-display,
        .heart-status-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .currency-icon {
            font-size: 1.25rem;
            line-height: 1;
        }

        .heart-status {
            position: relative;
        }

        .heart-status-trigger {
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
        }

        .heart-popover {
            position: absolute;
            z-index: 1100;
            top: calc(100% + 0.55rem);
            right: 0;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-5px);
            width: min(270px, calc(100vw - 1rem));
            padding: 0.75rem;
            color: #fff;
            background: rgba(18, 17, 29, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 0.8rem;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.42), 0 0 16px rgba(251, 191, 36, 0.08);
            transition: opacity 150ms ease, transform 150ms ease, visibility 150ms ease;
        }

        .heart-status:hover .heart-popover,
        .heart-status:focus-within .heart-popover {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .daily-streak-status { position: relative; }
        .daily-streak-trigger { padding: 0; border: 0; background: transparent; cursor: pointer; }
        .daily-streak-trigger.is-milestone {
            color: #fcd34d;
            text-shadow: 0 0 10px rgba(251, 191, 36, 0.45);
        }
        .daily-streak-popover {
            position: absolute;
            z-index: 1100;
            top: calc(100% + 0.55rem);
            right: 0;
            width: min(230px, calc(100vw - 1rem));
            padding: 0.7rem 0.75rem;
            color: #fff;
            background: rgba(18, 17, 29, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 0.8rem;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.42);
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-5px);
            transition: opacity 150ms ease, transform 150ms ease, visibility 150ms ease;
        }
        .daily-streak-status:hover .daily-streak-popover,
        .daily-streak-status:focus-within .daily-streak-popover {
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        .daily-streak-title { color: rgba(255,255,255,.65); font-size: .68rem; }
        .daily-streak-value { margin-top: .12rem; font-weight: 750; }
        .daily-streak-message { margin-top: .4rem; color: rgba(255,255,255,.68); font-size: .7rem; }
        .daily-streak-today { margin-top: .45rem; font-size: .68rem; }
        .daily-streak-today.is-done { color: #86efac; }
        .daily-streak-today.is-pending { color: #fcd34d; }

        .heart-popover-top,
        .heart-popover-gems {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .heart-slots {
            display: flex;
            align-items: center;
            gap: 0.08rem;
            min-width: 0;
        }

        .heart-slot {
            font-size: 1rem;
            line-height: 1;
        }

        .heart-slot.is-empty {
            color: rgba(255, 255, 255, 0.38);
        }

        .heart-popover-count {
            margin-left: 0.45rem;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .heart-countdown {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 0.65rem;
        }

        .heart-countdown-time {
            font-size: 1.15rem;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            line-height: 1.1;
        }

        .heart-countdown-label {
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.68rem;
            line-height: 1.1;
        }

        .heart-progress-track {
            height: 4px;
            margin: 0.55rem 0 0.65rem;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
        }

        .heart-progress-fill {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #fb7185, #fbbf24);
            border-radius: inherit;
            transition: width 250ms linear;
        }

        .heart-full-state {
            margin-top: 0.55rem;
            color: #fda4af;
            font-size: 0.78rem;
            font-weight: 700;
            text-align: center;
        }

        .heart-full-state[hidden],
        #heart-countdown-section[hidden] { display: none !important; }

        .heart-popover-gems {
            padding-top: 0.45rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.82rem;
        }

        .gem-refill-action {
            width: 100%;
            margin-top: 0.55rem;
            padding: 0.4rem 0.55rem;
            color: #fef3c7;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(251, 191, 36, 0.32);
            border-radius: 0.55rem;
            font-size: 0.76rem;
            font-weight: 650;
            transition: border-color 150ms ease, background-color 150ms ease, transform 150ms ease;
        }

        .gem-refill-action:not(:disabled):hover {
            background: rgba(251, 191, 36, 0.1);
            border-color: rgba(251, 191, 36, 0.65);
            transform: translateY(-1px);
        }

        .gem-refill-action:disabled {
            color: rgba(255, 255, 255, 0.48);
            border-color: rgba(255, 255, 255, 0.12);
            cursor: not-allowed;
        }

        .heart-popover-message {
            margin-top: 0.35rem;
            color: #fcd34d;
            font-size: 0.68rem;
            text-align: center;
        }

        .heart-clock {
            display: inline-block;
            margin-right: 0.25rem;
            animation: heartClockTurn 5s linear infinite;
        }

        @keyframes heartClockTurn {
            to { transform: rotate(360deg); }
        }

        @media (prefers-reduced-motion: reduce) {
            .heart-clock { animation: none; }
        }

        @media (max-width: 575.98px) {
            .student-currencies { gap: 0.5rem; }
            .heart-popover { right: 0; }
        }
    </style>
    @php($studentShell = $__env->hasSection('student_shell'))
    @if($studentShell ?? false)
        @include('partials.student-shell-css')
    @endif
    @stack('styles')
</head>
<body>

@if(Auth::check() && Auth::user()->role === 'student')
    @php($heartState = $navbarHeartState ?? ['hearts' => (int) Auth::user()->hearts, 'max_hearts' => 5, 'gems' => (int) Auth::user()->gems, 'seconds_to_next_heart' => 0, 'seconds_to_full_refill' => 0, 'regeneration_seconds' => 300, 'gem_refill_cost' => (int) config('gamification.heart_refill_gem_cost')])
    @php($dailyStreakState = $navbarDailyStreak ?? ['days' => (int) Auth::user()->daily_streak, 'completed_today' => false])
    @php($refillCost = (int) $heartState['gem_refill_cost'])
    @php($heartProgress = $heartState['hearts'] >= $heartState['max_hearts'] ? 100 : max(0, min(100, 100 * ($heartState['regeneration_seconds'] - $heartState['seconds_to_next_heart']) / max(1, $heartState['regeneration_seconds']))))
@endif

{{-- Large top navbar: never rendered on student pages (student shell flag, hard-guarded by role).
     Students keep it only on the public pages (home, kana); guests/admins see it exactly as before. --}}
@unless($studentShell ?? false || (Auth::check() && Auth::user()->role === 'student' && !request()->routeIs('home', 'kana')))
    @include('partials.app-navbar')
@endunless


@if($studentShell ?? false)
    <div class="sk-dash">
        @include('partials.student-bg')
        @include('partials.student-sidebar')
        <div class="sk-side-overlay" aria-hidden="true"></div>
        <div class="sk-main">
            @include('partials.student-utility')
            <div class="sk-wrap">
@endif

    <!-- Main Dynamic Content -->
    <main>
        @yield('content')
    </main>

@if($studentShell ?? false)
            </div><!-- /.sk-wrap -->
        </div><!-- /.sk-main -->
    </div><!-- /.sk-dash -->
@endif

    <!-- Footer Component -->
    <footer class="pt-5 pb-4 border-top border-opacity-10{{ ($studentShell ?? false) ? ' sk-dash-footer' : '' }}" style="border-color: rgba(255, 255, 255, 0.08) !important;">
        <div class="container">
            <div class="row g-4 pb-4">
                <div class="col-12 col-md-5">
                    <a href="{{ route('home') }}" class="fw-bold fs-4 gradient-text text-decoration-none">SukuraLearn.</a>
                    <p class="text-muted small mt-2 mb-0">Learn Japanese step by step, from your first words to advanced fluency.</p>
                    <p class="text-muted small mt-2 mb-0">一歩ずつ、日本語を学びましょう。</p>
                </div>
                <div class="col-6 col-md-3">
                    <h6 class="text-white fw-bold mb-3">Learning</h6>
                    @auth
                        <a href="{{ route('select.level') }}" class="d-block text-muted text-decoration-none small mb-2">Levels and Training</a>
                    @else
                        <a href="{{ route('login') }}" class="d-block text-muted text-decoration-none small mb-2">Sign in to learn</a>
                    @endauth
                </div>
                <div class="col-6 col-md-4">
                    <h6 class="text-white fw-bold mb-3">Account</h6>
                    @auth
                        <a href="{{ route('profile.edit') }}" class="d-block text-muted text-decoration-none small mb-2">My Profile</a>
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="d-block text-muted text-decoration-none small mb-2">Dashboard</a>
                        @elseif(Auth::user()->role === 'super_admin')
                            <a href="{{ route('super.admin.dashboard') }}" class="d-block text-muted text-decoration-none small mb-2">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="d-block text-muted text-decoration-none small mb-2">Login</a>
                        <a href="{{ route('register') }}" class="d-block text-muted text-decoration-none small mb-2">Register</a>
                    @endauth
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border-top border-secondary border-opacity-25 pt-3">
                <small class="text-muted">&copy; {{ date('Y') }} SukuraLearn. All rights reserved.</small>
                <small class="text-muted">Made for Japanese learners.</small>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar Active Link Switching
        const navLinks = document.querySelectorAll('.navbar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            }); 
        });
    </script>
    @if(Auth::check() && Auth::user()->role === 'student')
        <script>
            (function () {
                const currencies = document.getElementById('student-currencies');
                if (!currencies) return;

                const maxHearts = Number(currencies.dataset.maxHearts || 5);
                const countdown = document.getElementById('heart-next-countdown');
                const countdownSection = document.getElementById('heart-countdown-section');
                const fullState = document.getElementById('heart-full-state');
                const fullCount = document.getElementById('heart-full-count');
                const progressTrack = document.getElementById('heart-progress-track');
                const progressFill = document.getElementById('heart-progress-fill');
                const heartSlots = document.getElementById('heart-slots');
                const popoverCount = document.getElementById('heart-popover-count');
                const heartCount = document.getElementById('navbar-heart-count');
                const gemButton = document.getElementById('gem-heart-refill-button');
                const gemTotal = document.getElementById('navbar-gem-total');
                const gemPopoverBalance = document.getElementById('navbar-gem-count');
                const regenerationInterval = Math.max(1, Number(currencies.dataset.heartInterval || 300));
                let secondsToNext = Math.max(0, Number(currencies.dataset.heartSeconds || 0));
                let hearts = Number((heartCount.textContent || '0').split('/')[0]);
                let gems = Number(gemTotal.textContent || 0);
                let refillCost = Number(@json($heartState['gem_refill_cost']));
                let refreshing = false;

                function formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60).toString().padStart(2, '0');
                    const remainder = (seconds % 60).toString().padStart(2, '0');
                    return minutes + ':' + remainder;
                }

                function renderCountdown() {
                    const isFull = hearts >= maxHearts;
                    const progress = isFull ? 100 : Math.max(0, Math.min(100, 100 * (regenerationInterval - secondsToNext) / regenerationInterval));
                    heartCount.textContent = hearts + '/' + maxHearts;
                    popoverCount.textContent = String(hearts);
                    heartSlots.setAttribute('aria-label', hearts + ' of ' + maxHearts + ' hearts');
                    heartSlots.querySelectorAll('.heart-slot').forEach(function (slot, index) {
                        const filled = index < hearts;
                        slot.classList.toggle('is-filled', filled);
                        slot.classList.toggle('is-empty', !filled);
                        slot.textContent = filled ? '❤️' : '♡';
                    });
                    fullState.hidden = !isFull;
                    fullCount.textContent = maxHearts + '/' + maxHearts;
                    countdownSection.hidden = isFull;
                    if (!isFull) {
                        countdown.textContent = formatTime(secondsToNext);
                        progressFill.style.width = progress + '%';
                        progressTrack.setAttribute('aria-valuenow', String(Math.round(progress)));
                    }
                    if (gemButton) {
                        gemButton.disabled = isFull;
                        gemButton.textContent = '💎 ' + refillCost.toLocaleString() + ' · Refill Hearts';
                    }
                }

                async function refreshFromServer() {
                    if (refreshing) return;
                    refreshing = true;
                    try {
                        const response = await fetch(currencies.dataset.heartStatusUrl, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin'
                        });
                        if (!response.ok) throw new Error('Heart state refresh failed.');
                        const state = await response.json();
                        hearts = Number(state.hearts);
                        secondsToNext = Math.max(0, Number(state.seconds_to_next_heart));
                        gems = Number(state.gems);
                        refillCost = Number(state.gem_refill_cost);
                        if (gemTotal) gemTotal.textContent = String(gems);
                        if (gemPopoverBalance) gemPopoverBalance.textContent = String(gems);
                        renderCountdown();
                    } catch (error) {
                        secondsToNext = 5;
                    } finally {
                        refreshing = false;
                    }
                }

                renderCountdown();
                window.setInterval(function () {
                    if (hearts >= maxHearts) return;
                    if (secondsToNext <= 0) {
                        refreshFromServer();
                        return;
                    }
                    secondsToNext -= 1;
                    renderCountdown();
                }, 1000);
            })();
        </script>
    @endif
    @if($studentShell ?? false)
        <script>
            // Student shell sidebar: desktop collapse/expand + mobile slide-in drawer.
            (function () {
                var side = document.querySelector('.sk-dash .sk-side');
                if (!side) return;

                var toggle = document.querySelector('.sk-side-toggle');
                var burger = document.querySelector('.sk-burger');
                var overlay = document.querySelector('.sk-side-overlay');
                var closeBtn = document.querySelector('.sk-side-close');
                var desktop = window.matchMedia('(min-width: 992px)');
                var storeKey = 'sk-side-collapsed';

                // --- Desktop collapse / expand (persisted across pages) ---
                function setCollapsed(collapsed) {
                    document.body.classList.toggle('sk-side-collapsed', collapsed);
                    if (toggle) toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                    try { localStorage.setItem(storeKey, collapsed ? '1' : '0'); } catch (e) { /* storage unavailable */ }
                }
                var stored = null;
                try { stored = localStorage.getItem(storeKey); } catch (e) { /* storage unavailable */ }
                if (stored !== null) setCollapsed(stored === '1');
                if (toggle) {
                    toggle.addEventListener('click', function () {
                        setCollapsed(!document.body.classList.contains('sk-side-collapsed'));
                    });
                }

                // --- Mobile drawer (burger / backdrop / close button / Esc) ---
                function setDrawer(open) {
                    side.classList.toggle('is-open', open);
                    document.body.classList.toggle('sk-nav-open', open);
                    if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
                if (burger) burger.addEventListener('click', function () {
                    setDrawer(!side.classList.contains('is-open'));
                });
                if (overlay) overlay.addEventListener('click', function () { setDrawer(false); });
                if (closeBtn) closeBtn.addEventListener('click', function () { setDrawer(false); });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') setDrawer(false);
                });
                side.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () { setDrawer(false); });
                });
                function syncBreakpoint() {
                    if (desktop.matches) setDrawer(false); // drawer state is mobile-only
                }
                if (desktop.addEventListener) desktop.addEventListener('change', syncBreakpoint);
                else if (desktop.addListener) desktop.addListener(syncBreakpoint);
            })();
        </script>
    @endif
    @stack('scripts')
</body>
</html>

