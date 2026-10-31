<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NihongoBloom</title>
    
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: url("{{ asset('storage/images/sakura.png') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        .admin-wrapper-container {
            position: relative;
            width: 100%;
            min-height: 100vh;
        }

        /* ----------------------------------------------------
           1. GLASS SIDEBAR CORE STYLING
        ---------------------------------------------------- */
        .glass-sidebar {
            background: rgba(18, 13, 28, 0.88) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1), transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.4);
        }

        .glass-topbar, .glass-card {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(192, 132, 252, 0.15) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
            border-radius: 16px !important;
        }

        /* ----------------------------------------------------
           2. SECTION HEADERS (VISUAL HIERARCHY)
        ---------------------------------------------------- */
        .sidebar-section-title {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(216, 180, 254, 0.6);
            padding: 12px 14px 4px 14px;
            font-weight: 700;
            white-space: nowrap;
            transition: opacity 0.2s ease;
        }

        /* ----------------------------------------------------
           3. COLLAPSED SIDEBAR (DESKTOP ICON-ONLY MODE)
        ---------------------------------------------------- */
        .glass-sidebar.collapsed {
            width: 75px !important;
        }

        .glass-sidebar.collapsed .brand-block span,
        .glass-sidebar.collapsed .brand-block h4,
        .glass-sidebar.collapsed .nav-text,
        .glass-sidebar.collapsed .sidebar-section-title {
            opacity: 0;
            visibility: hidden;
            display: none !important;
        }

        .glass-sidebar.collapsed .sidebar-header {
            justify-content: center !important;
            padding: 18px 0;
        }

        .glass-sidebar.collapsed .nav-link {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            margin: 0 auto 6px auto;
            width: 48px;
            height: 48px;
            border-radius: 12px;
        }

        .glass-sidebar.collapsed .nav-link i.nav-ic {
            margin-right: 0 !important;
            font-size: 1.25rem;
        }

        /* ----------------------------------------------------
           4. CUSTOM FLOATING TOOLTIP ON COLLAPSE
        ---------------------------------------------------- */
        .glass-sidebar.collapsed .nav-link {
            position: relative;
        }

        .glass-sidebar.collapsed .nav-link::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 60px;
            top: 50%;
            transform: translateY(-50%) scale(0.85);
            background: rgba(25, 15, 45, 0.95);
            color: #ffffff;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            border-radius: 8px;
            border: 1px solid rgba(192, 132, 252, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1060;
        }

        .glass-sidebar.collapsed .nav-link:hover::after {
            opacity: 1;
            transform: translateY(-50%) scale(1);
        }

        /* ----------------------------------------------------
           5. NAVIGATION LINKS & GRADIENT ACCENT (ACTIVE STATE)
        ---------------------------------------------------- */
        .glass-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            background: transparent;
            border-radius: 10px;
            padding: 9px 14px;
            margin-bottom: 4px;
            font-size: 0.88rem;
            transition: background-color 0.2s ease, color 0.2s ease;
            font-weight: 500;
            white-space: nowrap;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .glass-sidebar .nav-link:hover {
            background: rgba(192, 132, 252, 0.16);
            color: #ffffff;
        }

        /* Active State with Pink/Purple Gradient Accent Indicator */
        .glass-sidebar .nav-link.active {
            background: rgba(192, 132, 252, 0.12);
            color: #ffffff;
            border: 1px solid rgba(192, 132, 252, 0.25);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .glass-sidebar .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 6px;
            bottom: 6px;
            width: 4px;
            background: linear-gradient(180deg, #f472b6 0%, #c084fc 100%);
            border-radius: 0 4px 4px 0;
        }

        .glass-sidebar.collapsed .nav-link.active::before {
            top: 4px;
            bottom: 4px;
        }

        .glass-sidebar .nav-link i.nav-ic {
            transition: transform 0.2s ease;
            font-size: 1.05rem;
        }

        .glass-sidebar .nav-link:hover i.nav-ic {
            transform: scale(1.1);
        }

        /* Color Accents for Icons */
        .nav-ic-dashboard   { color: #f472b6; }
        .nav-ic-admins      { color: #60a5fa; }
        .nav-ic-lessons     { color: #fbbf24; }
        .nav-ic-vocab       { color: #a78bfa; }
        .nav-ic-categories  { color: #2dd4bf; }
        .nav-ic-review      { color: #38bdf8; }
        .nav-ic-exams       { color: #fb923c; }
        .nav-ic-settings    { color: #c084fc; }
        .nav-ic-logout      { color: #f87171; }
        .nav-ic-home        { color: #4ade80; }

        /* ----------------------------------------------------
           6. MAIN CONTENT ADJUSTMENTS
        ---------------------------------------------------- */
        .main-content {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: 260px;
            min-height: 100vh;
            position: relative;
        }

        .main-content.expanded {
            margin-left: 75px !important;
        }

        /* Responsive Mobile Styles */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 1045;
        }

        .sidebar-overlay.show { display: block; }

        @media (max-width: 991px) {
            .glass-sidebar { transform: translateX(-100%); }
            .glass-sidebar.mobile-show { transform: translateX(0); }
            .main-content, .main-content.expanded {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .mobile-close-btn { display: flex !important; }
        }

        .mobile-close-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            color: #ffffff;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .mobile-close-btn:hover { background: rgba(255, 255, 255, 0.2); }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 14px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 8px;
        }

        .brand-block h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            color: #f3a6c8;
        }

        .brand-block span {
            display: block;
            margin-top: 2px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.7rem;
        }

        .sidebar-toggle {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .sidebar-toggle:hover { background: rgba(255, 255, 255, 0.2); }

        .dashboard-brand {
            color: #f3a6c8;
            font-size: 0.95rem;
        }
        /* Glassmorphism Table Styling for Dark Theme */
.glass-table {
    color: #ffffff !important;
    background: rgba(25, 15, 45, 0.4) !important; /* နောက်ခံအမှောင်ဖျော့ဖျော့ */
    backdrop-filter: blur(10px);
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.glass-table th {
    background: rgba(192, 132, 252, 0.15) !important; /* ခေါင်းစဉ်အရောင် (Purple tint) */
    color: #f3e8ff !important;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    border-bottom: 1px solid rgba(192, 132, 252, 0.3) !important;
    padding: 14px 16px;
}

.glass-table td {
    background: transparent !important;
    color: #ffffff !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    padding: 14px 16px;
    vertical-align: middle;
}

/* Hover effect when mouse over table rows */
.glass-table tbody tr:hover {
    background: rgba(192, 132, 252, 0.08) !important;
}
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-wrapper-container">
        <!-- Sidebar -->
        <nav id="sidebar" class="glass-sidebar">
            <div class="sidebar-header">
                <div class="brand-block">
                    <h4>Nihongobloom</h4>
                    <span>
                        {{ auth()->user()->role === 'super_admin' ? 'Super Admin Portal' : 'Admin Portal' }}
                    </span>
                </div>
                <button type="button" class="mobile-close-btn" id="sidebarCloseBtn" title="Close Sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="px-2">
                <ul class="nav flex-column">
                    
                    <!-- MAIN GROUP -->
                    <div class="sidebar-section-title">Main</div>
                    <li class="nav-item">
                        <a href="{{ auth()->user()->role === 'super_admin' ? route('super.admin.dashboard') : route('admin.dashboard') }}"
                           class="nav-link {{ request()->routeIs('super.admin.dashboard', 'admin.dashboard') ? 'active' : 'text-white' }}"
                           data-tooltip="Dashboard">
                            <i class="bi bi-speedometer2 me-2 nav-ic nav-ic-dashboard"></i> 
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    @if(auth()->user()->role === 'super_admin')
                    <!-- CONTENT MANAGEMENT GROUP -->
                    <div class="sidebar-section-title mt-2">Content Management</div>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.admins.index') }}" 
                           class="nav-link {{ request()->routeIs('super.admin.admins.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Admin Management">
                            <i class="bi bi-shield-lock-fill me-2 nav-ic nav-ic-admins"></i> 
                            <span class="nav-text">Admin Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.lessons.index') }}" 
                           class="nav-link {{ request()->routeIs('super.admin.lessons.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Lecture Management">
                            <i class="bi bi-book-fill me-2 nav-ic nav-ic-lessons"></i> 
                            <span class="nav-text">Lecture Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.training.index') }}"
                           class="nav-link {{ request()->routeIs('super.admin.training.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Vocabulary Management">
                            <i class="bi bi-journal-richtext me-2 nav-ic nav-ic-vocab"></i> 
                            <span class="nav-text">Vocabulary Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.categories.index') }}" 
                           class="nav-link {{ request()->routeIs('super.admin.categories.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Category Management">
                            <i class="bi bi-folder me-2 nav-ic nav-ic-categories"></i> 
                            <span class="nav-text">Category Management</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->role === 'admin')
                    <div class="sidebar-section-title mt-2">Exam Portal</div>
                    <li class="nav-item">
                        <a href="{{ route('admin.exams.index') }}"
                           class="nav-link {{ request()->routeIs('admin.exams.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Exam Questions">
                            <i class="bi bi-journal-text me-2 nav-ic nav-ic-review"></i> 
                            <span class="nav-text">Exam Questions</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->role === 'super_admin')
                    <div class="sidebar-section-title mt-2">Exams & Reviews</div>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.exam.questions.exams') }}"
                           class="nav-link {{ request()->routeIs('super.admin.exam.questions.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Exam Question Review">
                            <i class="bi bi-clipboard-check me-2 nav-ic nav-ic-review"></i> 
                            <span class="nav-text">Exam Question Review</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.exams.index') }}"
                           class="nav-link {{ request()->routeIs('super.admin.exams.*') ? 'active' : 'text-white' }}"
                           data-tooltip="Exam Management">
                            <i class="bi bi-journal-check me-2 nav-ic nav-ic-exams"></i> 
                            <span class="nav-text">Exam Management</span>
                        </a>
                    </li>

                    <!-- SYSTEM & SETTINGS GROUP -->
                    <div class="sidebar-section-title mt-2">System & Settings</div>
                    <li class="nav-item">
                        <a href="{{ route('super.admin.settings') }}"
                           class="nav-link {{ request()->routeIs('super.admin.settings*') ? 'active' : 'text-white' }}"
                           data-tooltip="Settings">
                            <i class="bi bi-gear-fill me-2 nav-ic nav-ic-settings"></i> 
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('logout') }}" class="nav-link text-white"
                           data-tooltip="Logout"
                           onclick="event.preventDefault(); document.getElementById('admin-sidebar-logout-form').submit();">
                            <i class="bi bi-box-arrow-right me-2 nav-ic nav-ic-logout"></i> 
                            <span class="nav-text">Logout</span>
                        </a>
                        <form id="admin-sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('home') }}" class="nav-link text-white" data-tooltip="Back to Home">
                            <i class="bi bi-house-door-fill me-2 nav-ic nav-ic-home"></i> 
                            <span class="nav-text">Back to Home</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main id="mainContent" class="main-content px-4 py-4">
            <div class="d-flex align-items-center justify-content-between mb-4 glass-topbar p-3">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle" type="button" title="Toggle Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <span class="fw-bold dashboard-brand d-none d-sm-inline">
                         NihongoBloom Management
                    </span>
                </div>
            </div>

            @yield('admin_content')
        </main>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        sidebarToggle.addEventListener('click', function () {
            if (window.innerWidth <= 991) {
                sidebar.classList.toggle('mobile-show');
                sidebarOverlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        sidebarCloseBtn.addEventListener('click', function () {
            sidebar.classList.remove('mobile-show');
            sidebarOverlay.classList.remove('show');
        });

        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('mobile-show');
            sidebarOverlay.classList.remove('show');
        });
    </script>
</body>
</html>