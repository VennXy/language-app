
@extends('layouts.admin')

@section('admin_content')

<style>
    .glass-widget {
        background: rgba(25, 15, 45, 0.65);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(192, 132, 252, 0.2);
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        transition: transform 0.3s ease, border-color 0.3s ease;
    }

    .glass-widget:hover {
        border-color: rgba(192, 132, 252, 0.4);
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.2;
        color: #ffffff;
    }

    .chart-card {
        padding: 1.5rem;
        height: 100%;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .chart-label {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.8px;
        color: #e9d5ff;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
    }

    .chart-sub {
        font-size: 12px;
        color: #cbd5e1;
        margin-bottom: 0;
    }

    .canvas-box {
        position: relative;
        width: 100%;
        height: 280px;
    }

    .canvas-box canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .activity-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .activity-list li {
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .activity-list li:last-child {
        border-bottom: none;
    }

    .badge-purple {
        background: rgba(192, 132, 252, 0.25);
        color: #d8b4fe;
        border: 1px solid rgba(192, 132, 252, 0.4);
        padding: 5px 10px;
        border-radius: 50rem;
        font-size: 11px;
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .action-btn {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        padding: 18px;
        color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 14px;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    .action-btn:hover {
        background: rgba(192, 132, 252, 0.15);
        border-color: rgba(192, 132, 252, 0.5);
        transform: translateY(-3px);
        color: #fff;
        box-shadow: 0 8px 20px rgba(192, 132, 252, 0.2);
    }

    .btn-icon {
        font-size: 1.6rem;
        color: #c084fc;
    }

    #mainContent.ajax-loading {
        opacity: 0.45;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
</style>

@if($dashboardForAdmin ?? false)

    {{-- ADMIN DASHBOARD --}}

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold">Admin Dashboard</h2>
            <p class="text-light opacity-75">
                Manage your exam question contributions and review status.
            </p>
        </div>

        <a href="{{ route('admin.exams.index') }}"
           class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm">
            Exam Questions
        </a>
    </div>

    <div class="row g-4 mb-5">

        <div class="col-md-4">
            <div class="glass-widget p-4">
                <span class="text-light small text-uppercase fw-semibold opacity-75">
                    Your Questions
                </span>
                <h3 class="text-white fw-bold mt-1 mb-0">
                    {{ $myQuestionsCount }}
                </h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-widget p-4">
                <span class="text-light small text-uppercase fw-semibold opacity-75">
                    Draft Questions
                </span>
                <h3 class="text-white fw-bold mt-1 mb-0">
                    {{ $myDraftQuestionsCount }}
                </h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="glass-widget p-4">
                <span class="text-light small text-uppercase fw-semibold opacity-75">
                    Awaiting Review
                </span>
                <h3 class="text-white fw-bold mt-1 mb-0">
                    {{ $myPendingQuestionsCount }}
                </h3>
            </div>
        </div>

    </div>

@else

    {{-- SUPER ADMIN DASHBOARD --}}

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="text-white fw-bold mb-1">Super Admin Dashboard</h2>
            <p class="text-light opacity-75 mb-0">
                System-wide KPIs, exam registration trends, and activity oversight.
            </p>
        </div>

        <a href="{{ route('super.admin.settings') }}"
           class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm"
           style="background: linear-gradient(135deg, #c084fc 0%, #9333ea 100%); color: #fff; border: none;">
            <i class="bi bi-gear-fill me-2"></i>
            System Settings
        </a>
    </div>

    {{-- KPI CARDS --}}

    <div class="row g-4 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1 fw-semibold">
                        Total Users
                    </p>
                    <h3 class="kpi-value mb-0">{{ $totalUsers }}</h3>
                </div>
                <div class="p-3 rounded-circle"
                     style="background: rgba(99, 102, 241, 0.2); color: #818cf8;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1 fw-semibold">
                        Total Students
                    </p>
                    <h3 class="kpi-value mb-0">{{ $totalStudents }}</h3>
                </div>
                <div class="p-3 rounded-circle"
                     style="background: rgba(110, 231, 183, 0.2); color: #34d399;">
                    <i class="bi bi-person-check-fill fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1 fw-semibold">
                        Total Admins
                    </p>
                    <h3 class="kpi-value mb-0">{{ $admins->count() }}</h3>
                </div>
                <div class="p-3 rounded-circle"
                     style="background: rgba(192, 132, 252, 0.2); color: #c084fc;">
                    <i class="bi bi-shield-lock-fill fs-4"></i>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1 fw-semibold">
                        Total Exams
                    </p>
                    <h3 class="kpi-value mb-0">{{ $totalExams }}</h3>
                </div>
                <div class="p-3 rounded-circle"
                     style="background: rgba(239, 68, 68, 0.2); color: #f87171;">
                    <i class="bi bi-clipboard-check-fill fs-4"></i>
                </div>
            </div>
        </div>

    </div>

    {{-- CHARTS --}}

    <div class="row g-4 mb-4">

        <div class="col-xl-7 col-lg-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Exam Registrations</p>
                        <p class="chart-sub">New attempts per day (last 12 months)</p>
                    </div>
                </div>

                <div class="canvas-box">
                    <canvas id="registrationsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-5 col-lg-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Exam Status</p>
                        <p class="chart-sub">Published / draft / closed breakdown</p>
                    </div>
                </div>

                <div class="canvas-box">
                    <canvas id="examStatusChart"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- RECENT ACTIVITY --}}

    <div class="row g-4 mb-4">

        <div class="col-xl-6 col-md-12">
            <div class="glass-widget p-4 h-100">
                <h5 class="text-white fw-bold mb-3">Recent Registrations</h5>

                <ul class="activity-list">
                    @forelse ($recentRegistrations as $reg)
                        <li>
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <p class="text-white fw-semibold small mb-0">
                                        {{ $reg['exam'] }}
                                    </p>
                                    <p class="text-light opacity-75 small mb-0">
                                        {{ $reg['student'] }}
                                    </p>
                                </div>

                                <div class="text-end">
                                    <span class="text-light opacity-75 small d-block">
                                        {{ $reg['date'] }}
                                    </span>
                                    <span class="badge badge-purple">
                                        {{ $reg['score'] }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>
                            <span class="text-light small">No registrations yet.</span>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-xl-6 col-md-12">
            <div class="glass-widget p-4 h-100">
                <h5 class="text-white fw-bold mb-3">Recent Question Activity</h5>

                <ul class="activity-list">
                    @forelse ($recentQuestionActivity as $item)
                        <li>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge
                                    @if($item['status'] == 'approved')
                                        bg-success bg-opacity-25 text-success border border-success border-opacity-50
                                    @elseif($item['status'] == 'pending')
                                        bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50
                                    @else
                                        bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50
                                    @endif">
                                    {{ ucfirst($item['status']) }}
                                </span>

                                <div>
                                    <p class="text-white fw-semibold small mb-0">
                                        {{ $item['title'] }}
                                    </p>
                                    <p class="text-light opacity-75 small mb-0">
                                        By {{ $item['owner'] }} on {{ $item['submitted_at'] }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li>
                            <span class="text-light small">No question activity yet.</span>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>

    {{-- QUICK ACTIONS --}}

    <div class="row g-4">
        <div class="col-12">
            <div class="glass-widget p-4">
                <h5 class="text-white fw-bold mb-3">Quick Actions</h5>

                <div class="quick-actions">

                    @if(auth()->user()->role === 'super_admin')

                        <a href="{{ route('super.admin.exams.index') }}" class="action-btn">
                            <i class="btn-icon bi bi-journal-check-fill"></i>
                            <span>Review Exam Questions</span>
                        </a>

                        <a href="{{ route('super.admin.exams.index') }}" class="action-btn">
                            <i class="btn-icon bi bi-clipboard-check-fill"></i>
                            <span>Manage Exams</span>
                        </a>

                        <a href="{{ route('super.admin.exam.questions.exams') }}" class="action-btn">
                            <i class="btn-icon bi bi-patch-question-fill"></i>
                            <span>Question Review Queue</span>
                        </a>

                        <a href="{{ route('super.admin.lessons.index') }}" class="action-btn">
                            <i class="btn-icon bi bi-journal-bookmark-fill"></i>
                            <span>Manage Lessons</span>
                        </a>

                    @else

                        <a href="{{ route('admin.exams.index') }}" class="action-btn">
                            <i class="btn-icon bi bi-journal-check-fill"></i>
                            <span>Review Exam Questions</span>
                        </a>

                    @endif

                </div>
            </div>
        </div>
    </div>

@endif


{{-- CHART.JS --}}
@if(!($dashboardForAdmin ?? false))
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {

    function initializeDashboardCharts() {

        if (typeof Chart === 'undefined') {
            return;
        }

        const chartData = @json($chartData);

        const lineCanvas = document.getElementById('registrationsChart');

        if (lineCanvas) {

            if (Chart.getChart(lineCanvas)) {
                Chart.getChart(lineCanvas).destroy();
            }

            new Chart(lineCanvas, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'New exam attempts',
                        data: chartData.series,
                        fill: true,
                        tension: 0.4,
                        backgroundColor: 'rgba(192, 132, 252, 0.15)',
                        borderColor: '#c084fc',
                        pointBackgroundColor: '#c084fc',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8' }
                        },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.05)' },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        const doughnutCanvas = document.getElementById('examStatusChart');

        if (doughnutCanvas) {

            if (Chart.getChart(doughnutCanvas)) {
                Chart.getChart(doughnutCanvas).destroy();
            }

            new Chart(doughnutCanvas, {
                type: 'doughnut',
                data: {
                    labels: @json($examStatus['labels']),
                    datasets: [{
                        data: @json($examStatus['series']),
                        backgroundColor: ['#38bdf8', '#c084fc', '#f87171'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#cbd5e1',
                                padding: 15,
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        }
    }

    window.initializeDashboardCharts = initializeDashboardCharts;

    initializeDashboardCharts();

})();
</script>
@endif


@endsection


{{-- GLOBAL AJAX NAVIGATION --}}
@once
<script>
(function () {

    if (window.ajaxNavigationInitialized) {
        return;
    }

    window.ajaxNavigationInitialized = true;

    let activeController = null;

    function getMainContent() {
        return document.getElementById('mainContent');
    }

    function isInternalNavigation(link) {

        if (!link || !link.href) {
            return false;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return false;
        }

        if (link.target && link.target !== '_self') {
            return false;
        }

        if (link.hasAttribute('download')) {
            return false;
        }

        if (
            link.href.includes('logout') ||
            link.pathname.endsWith('/logout')
        ) {
            return false;
        }

        if (
            link.getAttribute('href') === '#' ||
            link.getAttribute('href')?.startsWith('javascript:')
        ) {
            return false;
        }

        return true;
    }

    function setLoading(loading) {

        const mainContent = getMainContent();

        if (!mainContent) {
            return;
        }

        mainContent.classList.toggle('ajax-loading', loading);
    }

   
function initializeBootstrapComponents(container = document) {

    if (!window.bootstrap || !container) {
        return;
    }

    // Dropdown
    container.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(element => {
        bootstrap.Dropdown.getOrCreateInstance(element);
    });

    // Tooltip
    container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
        bootstrap.Tooltip.getOrCreateInstance(element);
    });

    // Popover
    container.querySelectorAll('[data-bs-toggle="popover"]').forEach(element => {
        bootstrap.Popover.getOrCreateInstance(element);
    });

    // Collapse
    container.querySelectorAll('[data-bs-toggle="collapse"]').forEach(element => {
        bootstrap.Collapse.getOrCreateInstance(element, {
            toggle: false
        });
    });
}


    function updateActiveNavigation(url) {

        const currentPath = new URL(url, window.location.origin).pathname;

        document.querySelectorAll('#sidebar .nav-link').forEach(link => {

            const linkPath = new URL(link.href, window.location.origin).pathname;

            link.classList.toggle('active', linkPath === currentPath);

        });
    }
    

    function initializePageComponents() {

        const mainContent = getMainContent();

        if (!mainContent) {
            return;
        }

        initializeBootstrapComponents(mainContent);

        if (typeof window.initializeDashboardCharts === 'function') {
            window.initializeDashboardCharts();
        }

        document.dispatchEvent(
            new CustomEvent('ajax:page-loaded', {
                detail: {
                    container: mainContent,
                    url: window.location.href
                }
            })
        );
    }

    async function navigateTo(url, options = {}) {

        const mainContent = getMainContent();

        if (!mainContent) {
            window.location.href = url;
            return;
        }

        if (activeController) {
            activeController.abort();
        }

        activeController = new AbortController();

        const controller = activeController;

        setLoading(true);

        try {

            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                throw new Error('HTTP Error: ' + response.status);
            }

            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('text/html')) {
                throw new Error('Expected HTML but received: ' + contentType);
            }

            const html = await response.text();

            const parser = new DOMParser();

            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.getElementById('mainContent');

            if (!newContent) {
                throw new Error('The response does not contain #mainContent');
            }

            const currentContent = getMainContent();

            if (!currentContent) {
                window.location.href = url;
                return;
            }

            currentContent.innerHTML = newContent.innerHTML;

            if (doc.title) {
                document.title = doc.title;
            }

            if (options.updateHistory !== false) {
                window.history.pushState(
                    { ajaxNavigation: true, url: url },
                    '',
                    url
                );
            }

            updateActiveNavigation(url);

            initializePageComponents();

            window.scrollTo({
                top: 0,
                behavior: 'auto'
            });

        } catch (error) {

            if (error.name === 'AbortError') {
                return;
            }

            console.error('AJAX Navigation Failed:', error);

            // Fallback to normal Laravel navigation.
            window.location.href = url;

        } finally {

            if (activeController === controller) {
                activeController = null;
                setLoading(false);
            }

        }
    }

    // EVENT DELEGATION: supports existing and dynamically added links.

    document.addEventListener('click', function (event) {

        const link = event.target.closest('#sidebar .nav-link');

        if (!link) {
            return;
        }

        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        if (!isInternalNavigation(link)) {
            return;
        }

        event.preventDefault();

        navigateTo(link.href);

    });

    // BACK / FORWARD SUPPORT

    window.addEventListener('popstate', function () {

        navigateTo(window.location.href, {
            updateHistory: false
        });

    });

    // INITIAL PAGE INITIALIZATION

    document.addEventListener('DOMContentLoaded', function () {

        initializePageComponents();

    });

})();
</script>
@endonce
