{{-- Extracted verbatim from layouts/app.blade.php: global top navbar (brand, links, currencies, account dropdown). Rendered only when the student-shell flag is false. --}}
   <!-- Navbar Component -->  
<header class="sticky-top w-full">
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container max-w-7xl">
            <!-- Brand / Logo -->
            <a class="navbar-brand fw-extrabold fs-3 gradient-text" href="{{ url('/') }}">NihongoBloom</a>
            
            <!-- Mobile Toggle Button (Hamburger) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Collapse Wrapper -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center Navigation Links (hidden on Login & Register pages) -->
                <ul class="navbar-nav mx-auto my-2 my-lg-0 gap-1 align-items-lg-center">
                    @unless(request()->routeIs('login', 'register'))
                    <!-- Home Link -->
                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link px-4 py-2 rounded-pill small fw-medium text-light {{ request()->routeIs('home') ? 'active bg-white bg-opacity-10 text-white' : 'opacity-75' }}">
                            <i class="bi bi-house me-2"></i> Home
                        </a>
                    </li>

                    <!-- Methods Link -->
                    <li class="nav-item">
                        <a href="#methods" class="nav-link px-4 py-2 rounded-pill small fw-medium text-light opacity-75">
                            <i class="bi bi-mortarboard me-2"></i> Methods
                        </a>
                    </li>

                    <!-- Kana Chart Link -->
                    <li class="nav-item">
                        <a href="{{ route('kana') }}" class="nav-link px-4 py-2 rounded-pill small fw-medium text-light {{ request()->routeIs('kana') ? 'active bg-white bg-opacity-10 text-white shadow-sm' : 'opacity-75' }}">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Kana Chart
                        </a>
                    </li>
                    @endunless
                </ul>

                <!-- Right Side: Navbar (Login / Register / User Profile) -->
                <div class="d-flex align-items-center justify-content-center gap-2 mt-3 mt-lg-0">
                    @guest
                        <!-- Login & Register Buttons for Guests -->
                        <a href="{{ route('login') }}" class="liquid-btn-glass btn-login-glass">
                            <span class="btn-text">Login</span>
                            <div class="liquid-fill"></div>
                        </a>

                        <a href="{{ route('register') }}" class="liquid-btn-glass btn-register-glass">
                            <span class="btn-text">Register</span>
                            <div class="liquid-fill"></div>
                        </a>
                    @endguest

                    @auth
                        <!-- Profile Dropdown for Logged-in Users -->
                        @if(Auth::user()->role === 'student')
                            <div class="student-currencies" id="student-currencies"
                                 data-heart-status-url="{{ route('student.hearts.status') }}"
                                 data-heart-seconds="{{ $heartState['seconds_to_next_heart'] }}"
                                 data-heart-full-seconds="{{ $heartState['seconds_to_full_refill'] }}"
                                 data-heart-interval="{{ $heartState['regeneration_seconds'] }}"
                                 data-max-hearts="{{ $heartState['max_hearts'] }}">
                                <div class="daily-streak-status">
                                    <button type="button" id="daily-streak-trigger" class="currency-display daily-streak-trigger {{ $dailyStreakState['days'] >= 7 ? 'is-milestone' : '' }}" aria-label="Daily streak, {{ $dailyStreakState['days'] }} days" aria-describedby="daily-streak-popover">
                                        <span class="currency-icon">🔥</span><span id="daily-streak-count">{{ $dailyStreakState['days'] }}</span>
                                    </button>
                                    <div class="daily-streak-popover" id="daily-streak-popover" role="status">
                                        <div class="daily-streak-title">Daily Streak</div>
                                        <div class="daily-streak-value">🔥 <span id="daily-streak-value">{{ $dailyStreakState['days'] }}</span> days</div>
                                        <div class="daily-streak-message">Keep learning every day!</div>
                                        <div id="daily-streak-today" class="daily-streak-today {{ $dailyStreakState['completed_today'] ? 'is-done' : 'is-pending' }}">
                                            {{ $dailyStreakState['completed_today'] ? '✓ Today’s learning completed' : 'Learn today to continue your streak' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="heart-status">
                                    <button type="button" class="heart-status-trigger" aria-label="Hearts and regeneration status" aria-describedby="heart-popover">
                                        <span class="currency-icon">❤️</span><span id="navbar-heart-count">{{ $heartState['hearts'] }}/{{ $heartState['max_hearts'] }}</span>
                                    </button>
                                    <div class="heart-popover" id="heart-popover" role="status">
                                        <div class="heart-popover-top">
                                            <div class="heart-slots" id="heart-slots" aria-label="{{ $heartState['hearts'] }} of {{ $heartState['max_hearts'] }} hearts">
                                                @for($heartSlot = 1; $heartSlot <= $heartState['max_hearts']; $heartSlot++)
                                                    <span class="heart-slot {{ $heartSlot <= $heartState['hearts'] ? 'is-filled' : 'is-empty' }}" aria-hidden="true">{{ $heartSlot <= $heartState['hearts'] ? '❤️' : '♡' }}</span>
                                                @endfor
                                            </div>
                                            <span class="heart-popover-count"><span id="heart-popover-count">{{ $heartState['hearts'] }}</span>/{{ $heartState['max_hearts'] }}</span>
                                        </div>
                                        <div class="heart-full-state" id="heart-full-state" @if($heartState['hearts'] < $heartState['max_hearts']) hidden @endif>❤️ Hearts Full <span class="text-white-50" id="heart-full-count">{{ $heartState['max_hearts'] }}/{{ $heartState['max_hearts'] }}</span></div>
                                        <div id="heart-countdown-section" @if($heartState['hearts'] >= $heartState['max_hearts']) hidden @endif>
                                            <div class="heart-countdown">
                                                <i class="bi bi-clock heart-clock" aria-hidden="true"></i>
                                                <div>
                                                    <strong class="heart-countdown-time" id="heart-next-countdown">{{ gmdate('i:s', $heartState['seconds_to_next_heart']) }}</strong>
                                                    <div class="heart-countdown-label">Next Heart</div>
                                                </div>
                                            </div>
                                            <div class="heart-progress-track" id="heart-progress-track" role="progressbar" aria-label="Progress to next heart" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $heartProgress }}">
                                                <span class="heart-progress-fill" id="heart-progress-fill" style="width: {{ $heartProgress }}%"></span>
                                            </div>
                                        </div>
                                        <div class="heart-popover-gems"><span>💎 <span id="navbar-gem-count">{{ $heartState['gems'] }}</span></span></div>
                                        @if($heartState['hearts'] < $heartState['max_hearts'])
                                            <form method="POST" action="{{ route('student.hearts.refill') }}" id="gem-heart-refill-form">
                                                @csrf
                                                @if(request()->routeIs('user.training'))
                                                    <input type="hidden" name="lesson_id" value="{{ request()->route('id') }}">
                                                @endif
                                                <button type="submit" class="gem-refill-action" id="gem-heart-refill-button">💎 {{ number_format($refillCost) }} <span aria-hidden="true">·</span> Refill Hearts</button>
                                            </form>
                                        @endif
                                        @if(session('gem_refill_error'))
                                            <div class="heart-popover-message">{{ session('gem_refill_error') }}</div>
                                        @elseif(session('gem_refill_success'))
                                            <div class="heart-popover-message text-success">{{ session('gem_refill_success') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <span class="currency-display" aria-label="Gems"><span class="currency-icon">💎</span><span id="navbar-gem-total">{{ $heartState['gems'] }}</span></span>
                            </div>
                        @endif
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="rounded-circle overflow-hidden border border-secondary" style="width: 50px; height: 50px;">
                                    @if(Auth::user()->profile_photo)
                                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ Auth::user()->name }}" alt="Default Avatar" class="w-100 h-100 object-fit-cover">
                                    @endif
                                </div>
                            </a>
                            
                            <!-- dropdown-menu-glass class -->
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-glass text-small shadow dropdown-menu-end" aria-labelledby="dropdownUser1">
                                {{-- Public Home Page account menu: intentionally only Dashboard + Logout.
                                     Dashboard resolves to the correct dashboard for the current role. --}}
                                @if(Auth::user()->role === 'student')
                                    <li><a class="dropdown-item py-2" href="{{ route('student.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> My Dashboard</a></li>
                                @elseif(Auth::user()->role === 'admin')
                                    <li><a class="dropdown-item py-2" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                @elseif(Auth::user()->role === 'super_admin')
                                    <li><a class="dropdown-item py-2" href="{{ route('super.admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                                @endif
                                <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger py-2"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>
