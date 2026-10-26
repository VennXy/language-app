{{-- SakuraLearn student navigation rail (shared shell). Pure navigation: active state from route checks. --}}
<aside class="sk-side" aria-label="Student navigation">
    <button class="sk-side-close" type="button" aria-label="Close navigation"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    <a class="sk-brand" href="{{ route('home') }}">
        <span class="sk-brand-mark" aria-hidden="true">🌸</span>
        <span class="sk-brand-text">
            <span class="sk-brand-name">NihongoBloom</span>
            <small class="sk-brand-sub">SAKURA STUDY</small>
        </span>
    </a>     
    <nav class="sk-nav">
        <a class="sk-nav-a sk-ic-violet {{ request()->routeIs('student.dashboard') ? 'is-active' : '' }}" href="{{ route('student.dashboard') }}" data-tip="Dashboard" @if(request()->routeIs('student.dashboard')) aria-current="page" @endif><i class="bi bi-speedometer2" aria-hidden="true"></i><span class="sk-nav-label">Dashboard</span></a>
        <a class="sk-nav-a sk-ic-blue {{ request()->routeIs('select.level', 'user.training', 'levels.*') ? 'is-active' : '' }}" href="{{ route('select.level') }}" data-tip="Training" @if(request()->routeIs('select.level', 'user.training', 'levels.*')) aria-current="page" @endif><i class="bi bi-mortarboard" aria-hidden="true"></i><span class="sk-nav-label">Training</span></a>
        <a class="sk-nav-a sk-ic-cyan {{ request()->routeIs('student.exams.*') ? 'is-active' : '' }}" href="{{ route('student.exams.index') }}" data-tip="Exams" @if(request()->routeIs('student.exams.*')) aria-current="page" @endif><i class="bi bi-clipboard2-check" aria-hidden="true"></i><span class="sk-nav-label">Exams</span></a>
        <a class="sk-nav-a sk-ic-amber {{ request()->routeIs('student.leaderboard.*') ? 'is-active' : '' }}" href="{{ route('student.leaderboard.index') }}" data-tip="Leaderboard" @if(request()->routeIs('student.leaderboard.*')) aria-current="page" @endif><i class="bi bi-trophy" aria-hidden="true"></i><span class="sk-nav-label">Leaderboard</span></a>
        <a class="sk-nav-a sk-ic-pink {{ request()->routeIs('student.bookmarks.*') ? 'is-active' : '' }}" href="{{ route('student.bookmarks.index') }}" data-tip="Bookmarks" @if(request()->routeIs('student.bookmarks.*')) aria-current="page" @endif><i class="bi bi-bookmark-star" aria-hidden="true"></i><span class="sk-nav-label">Bookmarks</span></a>
        <a class="sk-nav-a sk-ic-green {{ request()->routeIs('student.mistakes.*') ? 'is-active' : '' }}" href="{{ route('student.mistakes.index') }}" data-tip="Review Mistakes" @if(request()->routeIs('student.mistakes.*')) aria-current="page" @endif><i class="bi bi-arrow-repeat" aria-hidden="true"></i><span class="sk-nav-label">Review Mistakes</span></a>
        <div class="sk-nav-sep" role="separator"></div>
        <a class="sk-nav-a sk-ic-purple {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.edit') }}" data-tip="Settings" @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="bi bi-gear" aria-hidden="true"></i><span class="sk-nav-label">Settings</span></a>
        <a class="sk-nav-a sk-ic-sky" href="{{ route('home') }}" data-tip="Back to Home"><i class="bi bi-house-door" aria-hidden="true"></i><span class="sk-nav-label">Back to Home</span></a>
    </nav>
    <div class="sk-side-actions">
        <button class="sk-side-toggle" type="button" aria-expanded="true" aria-label="Collapse sidebar">
            <i class="bi bi-chevron-left" aria-hidden="true"></i><span class="sk-nav-label">Collapse</span>
        </button>
        <p class="sk-side-foot">🌸 一歩ずつ · step by step</p>
    </div>
</aside>