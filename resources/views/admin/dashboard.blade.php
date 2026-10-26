@extends('layouts.admin')

@section('admin_content')
<style>
    /* Dashboard widgets (Sakura glass) */
    .glass-widget{background:rgba(255,255,255,0.03);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(192,132,252,0.15);border-radius:16px;box-shadow:0 15px 35px rgba(0,0,0,0.25);}
    .kpi-value{font-size:2.1rem;font-weight:800;line-height:1.1;}
    .chart-card{padding:1.25rem;}
    .chart-header{display:flex;justify-content:space-between;align-items:center;}
    .chart-label{font-size:12px;font-weight:700;letter-spacing:0.5px;color:#d8b4fe;text-transform:uppercase;}
    .chart-sub{font-size:12px;color:#94a3b8;margin-top:2px;}
    .canvas-box{position:relative;width:100%;min-height:260px;padding:8px;}
    .canvas-box canvas{width:100% !important;height:auto !important;}
    .activity-list{list-style:none;margin:0;padding:0;}
    .activity-list li{padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.06);}
    .activity-list li:last-child{border-bottom:none;}
    .badge-pending{background:rgba(253,230,138,0.25);color:#fbbf24;border:1px solid rgba(253,230,138,0.4);}
    .badge-approved{background:rgba(110,231,183,0.2);color:#6EE7B7;border:1px solid rgba(110,231,183,0.35);}
    .badge-rejected{background:rgba(248,113,113,0.18);color:#F87171;border:1px solid rgba(248,113,113,0.35);}
    .badge-purple{background:rgba(192,132,252,0.2);color:#c084fc;border:1px solid rgba(192,132,252,0.3);}
    .quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .action-btn{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:14px;padding:16px;color:#fff;text-decoration:none;display:flex;align-items:center;gap:12px;font-weight:600;transition:all .25s;}
    .action-btn:hover{background:rgba(192,132,252,0.15);border-color:rgba(192,132,252,0.4);transform:translateY(-2px);color:#fff;}
    .btn-icon{font-size:1.5rem;}
    .roadmap-lessons{list-style:none;margin:6px 0 0 18px;padding:0;}
    .roadmap-lessons li{padding:3px 0;font-size:13px;color:#cbd5e1;}
</style>

@if($dashboardForAdmin ?? false)
    <!-- ADMIN BRANCH: Admin Dashboard (own exam question contributions) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold">Admin Dashboard</h2>
            <p class="text-light opacity-75">Manage your exam question contributions and review status.</p>
        </div>
        <a href="{{ route('admin.exams.index') }}" class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm">Exam Questions</a>
    </div>
    <div class="row g-4 mb-5">
        <div class="col-md-4"><div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); border-color: rgba(192, 132, 252, 0.2) !important;"><span class="text-light small text-uppercase fw-semibold opacity-75">Your Questions</span><h3 class="text-white fw-bold mt-1 mb-0">{{ $myQuestionsCount }}</h3></div></div>
        <div class="col-md-4"><div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); border-color: rgba(192, 132, 252, 0.2) !important;"><span class="text-light small text-uppercase fw-semibold opacity-75">Draft Questions</span><h3 class="text-white fw-bold mt-1 mb-0">{{ $myDraftQuestionsCount }}</h3></div></div>
        <div class="col-md-4"><div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); border-color: rgba(192, 132, 252, 0.2) !important;"><span class="text-light small text-uppercase fw-semibold opacity-75">Awaiting Review</span><h3 class="text-white fw-bold mt-1 mb-0">{{ $myPendingQuestionsCount }}</h3></div></div>
    </div>
@else
    <!-- SUPER ADMIN BRANCH -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h2 class="text-white fw-bold mb-0">Super Admin Dashboard</h2>
            <p class="text-light opacity-75 mb-0">System-wide KPIs, exam status, question review pipeline, and recent activity.</p>
        </div>
        <a href="{{ route('super.admin.settings') }}" class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm">
            <i class="bi bi-gear-fill me-2"></i> System Settings
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Users</p>
                    <h3 class="kpi-value text-white mt-0">{{ $totalUsers }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Students</p>
                    <h3 class="kpi-value text-white mt-0">{{ $totalStudents }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(110, 231, 183, 0.15); color: #6EE7B7;"><i class="bi bi-person-check-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Admins</p>
                    <h3 class="kpi-value text-white mt-0">{{ $admins->count() }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(192, 132, 252, 0.15); color: #c084fc;"><i class="bi bi-shield-lock-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Lessons</p>
                    <h3 class="kpi-value text-white mt-0">{{ $totalLessons }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;"><i class="bi bi-book-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Vocabularies</p>
                    <h3 class="kpi-value text-white mt-0">{{ $totalVocabularies }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(250, 204, 21, 0.15); color: #FCD34D;"><i class="bi bi-book-half"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Total Exams</p>
                    <h3 class="kpi-value text-white mt-0">{{ $totalExams }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(239, 68, 68, 0.15); color: #F87171;"><i class="bi bi-clipboard-check-fill"></i></div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6">
            <div class="glass-widget p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-light small text-uppercase mb-1">Pending Reviews</p>
                    <h3 class="kpi-value text-white mt-0">{{ $pendingReviews }}</h3>
                </div>
                <div class="p-3 rounded-circle" style="background: rgba(253, 230, 138, 0.18); color: #FBBF24;"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4">
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Exam Registrations</p>
                        <p class="chart-sub">New attempts per day (last 12 months)</p>
                    </div>
                </div>
                <div class="canvas-box mt-3">
                    <canvas id="registrationsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Exam Status</p>
                        <p class="chart-sub">Published / draft / closed</p>
                    </div>
                </div>
                <div class="canvas-box mt-3">
                    <canvas id="examStatusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Question Review Status</p>
                        <p class="chart-sub">Pending / approved / rejected</p>
                    </div>
                </div>
                <div class="canvas-box mt-3">
                    <canvas id="questionStatusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget chart-card">
                <div class="chart-header">
                    <div>
                        <p class="chart-label">Level Content Roadmap</p>
                        <p class="chart-sub">Lessons available per level</p>
                    </div>
                </div>
                <ul class="activity-list">
                    @forelse ($roadmap as $level)
                        <li>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-white fw-semibold">{{ $level['name'] }}</span>
                                <span class="text-light small">{{ $level['lesson_count'] }} lesson(s)</span>
                            </div>
                            @if (!empty($level['lessons']))
                                <ul class="roadmap-lessons">
                                    @foreach ($level['lessons'] as $lessonId => $lessonTitle)
                                        <li>{{ $lessonTitle }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @empty
                        <li><span class="text-light small">No levels defined yet.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4 mb-4">
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget p-4">
                <h5 class="text-white fw-bold mb-3">Recent Registrations</h5>
                <ul class="activity-list">
                    @forelse ($recentRegistrations as $reg)
                        <li>
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div>
                                    <p class="text-white fw-semibold small mb-0">{{ $reg['exam'] }}</p>
                                    <p class="text-light small mb-0">{{ $reg['student'] }}</p>
                                </div>
                                <div class="text-end">
                                    <span class="text-light small d-block">{{ $reg['date'] }}</span>
                                    <span class="badge badge-purple">{{ $reg['score'] }}</span>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li><span class="text-light small">No registrations yet.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-xl-6 col-md-12">
            <div class="glass-widget p-4">
                <h5 class="text-white fw-bold mb-3">Recent Question Activity</h5>
                <ul class="activity-list">
                    @forelse ($recentQuestionActivity as $item)
                        <li>
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge badge-{{ $item['status'] }}">{{ ucfirst($item['status']) }}</span>
                                    <div>
                                        <p class="text-white fw-semibold small mb-0">{{ $item['title'] }}</p>
                                        <p class="text-light small mb-0">Submitted by {{ $item['owner'] }} on {{ $item['submitted_at'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li><span class="text-light small">No question activity yet.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4">
        <div class="col-xl-4 col-md-6">
            <div class="glass-widget p-4">
                <h5 class="text-white fw-bold mb-3">Quick Actions</h5>
                <div class="quick-actions">
                    <a href="{{ route('admin.exams.index') }}" class="action-btn">
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
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') { return; }

        var chartData = @json($chartData);

        var initLineChart = function (canvasId, labels, data, color) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) { return; }
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'New exam attempts',
                        data: data,
                        fill: true,
                        tension: 0.35,
                        backgroundColor: color + '22',
                        borderColor: color,
                        pointBackgroundColor: color,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(255,255,255,0.07)' }, ticks: { color: '#94a3b8' } },
                        y: { grid: { color: 'rgba(255,255,255,0.07)' }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        };

        var initDoughnut = function (canvasId, labels, series) {
            var ctx = document.getElementById(canvasId);
            if (!ctx) { return; }
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{ data: series, backgroundColor: ['#94a3b8', '#6EE7B7', '#F87171'], border: false }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } }
                }
            });
        };

        initLineChart('registrationsChart', chartData.labels, chartData.series, '#c084fc');
        initDoughnut('examStatusChart', @json($examStatus['labels']), @json($examStatus['series']));
        initDoughnut('questionStatusChart', @json($questionStatus['labels']), @json($questionStatus['series']));
    })();
</script>
@endif
@endsection
