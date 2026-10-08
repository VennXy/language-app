{{-- SakuraLearn student navigation rail (shared shell). Pure navigation: active state from route checks. --}}
<aside class="sk-side" aria-label="Student navigation">
    <a class="sk-brand" href="{{ route('home') }}">
        <span class="sk-brand-mark" aria-hidden="true">🌸</span>
        <span>
            <span class="sk-brand-name">SakuraLearn</span>
            <small class="sk-brand-sub">SAKURA STUDY</small>
        </span>
    </a>
    <nav class="sk-nav">
        <a class="sk-nav-a {{ request()->routeIs('student.dashboard') ? 'is-active' : '' }}" href="{{ route('student.dashboard') }}" @if(request()->routeIs('student.dashboard')) aria-current="page" @endif><i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard</a>
        <a class="sk-nav-a {{ request()->routeIs('select.level', 'user.training', 'levels.*') ? 'is-active' : '' }}" href="{{ route('select.level') }}" @if(request()->routeIs('select.level', 'user.training', 'levels.*')) aria-current="page" @endif><i class="bi bi-mortarboard" aria-hidden="true"></i> Training</a>
        <a class="sk-nav-a {{ request()->routeIs('student.exams.*') ? 'is-active' : '' }}" href="{{ route('student.exams.index') }}" @if(request()->routeIs('student.exams.*')) aria-current="page" @endif><i class="bi bi-clipboard2-check" aria-hidden="true"></i> Exams</a>
        <a class="sk-nav-a {{ request()->routeIs('student.leaderboard.*') ? 'is-active' : '' }}" href="{{ route('student.leaderboard.index') }}" @if(request()->routeIs('student.leaderboard.*')) aria-current="page" @endif><i class="bi bi-trophy" aria-hidden="true"></i> Leaderboard</a>
        <a class="sk-nav-a {{ request()->routeIs('student.bookmarks.*') ? 'is-active' : '' }}" href="{{ route('student.bookmarks.index') }}" @if(request()->routeIs('student.bookmarks.*')) aria-current="page" @endif><i class="bi bi-bookmark-star" aria-hidden="true"></i> Bookmarks</a>
        <a class="sk-nav-a {{ request()->routeIs('student.mistakes.*') ? 'is-active' : '' }}" href="{{ route('student.mistakes.index') }}" @if(request()->routeIs('student.mistakes.*')) aria-current="page" @endif><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Review Mistakes</a>
        <div class="sk-nav-sep" role="separator"></div>
        <a class="sk-nav-a {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif><i class="bi bi-gear" aria-hidden="true"></i> Settings</a>
        <a class="sk-nav-a" href="{{ route('home') }}"><i class="bi bi-house-door" aria-hidden="true"></i> Back to Home</a>
    </nav>
    <p class="sk-side-foot">🌸 一歩ずつ · step by step</p>
</aside>