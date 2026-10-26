<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NihongoBloom</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Dashboard Background with Sakura Image & Dark Overlay */
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

        /* Container for fixing layout overlapping */
        .admin-wrapper-container {
            position: relative;
            width: 100%;
            min-height: 100vh;
        }

        .glass-sidebar {
            background: rgba(18, 13, 28, 0.85) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            overflow-y: auto;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.4);
        }

        .glass-topbar, .glass-card {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(192, 132, 252, 0.15) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
        }

        /* Collapsed Sidebar (Desktop) */
        .glass-sidebar.collapsed {
            width: 80px !important;
        }

        .glass-sidebar.collapsed .brand-block,
        .glass-sidebar.collapsed .nav-text {
            display: none !important;
        }

        .glass-sidebar.collapsed .sidebar-header {
            justify-content: center !important;
            padding: 24px 0;
        }

        .glass-sidebar.collapsed .nav-link {
            text-align: center;
            padding: 12px 0;
        }

        .glass-sidebar.collapsed .nav-link i {
            margin-right: 0 !important;
            font-size: 1.3rem;
        }

        /* Navigation Links */
        .glass-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            background: transparent;
            border-radius: 12px;
            padding: 12px 16px;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            font-weight: 500;
            white-space: nowrap;
        }

        .glass-sidebar .nav-link:hover {
            background: rgba(192, 132, 252, 0.16);
            color: #ffffff;
        }

        /* Active item stays visually distinct, even on hover */
        .glass-sidebar .nav-link.active:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: inset 3px 0 0 #f472b6, 0 8px 25px rgba(0,0,0,0.2);
        }

        /* Keyboard users: visible focus ring that contrasts the sidebar */
        .glass-sidebar .nav-link:focus-visible {
            outline: 2px solid #e9d5ff;
            outline-offset: 2px;
        }

        .glass-sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: inset 3px 0 0 #f472b6, 0 8px 25px rgba(0,0,0,0.2);
        }

        /* Topbar & Cards Border Radius */
        .glass-topbar, .glass-card {
            border-radius: 18px !important;
        }

        /* Glass Table Styling */
        .glass-table {
            color: #ffffff;
            background: transparent !important;
        }

        .glass-table th, .glass-table td {
            background: transparent !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            padding: 14px 16px;
        }

        /* Main Content Transition & Margins */
        .main-content {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            position: relative;
        }

        .main-content.expanded {
           margin-left: 80px !important;
            width: calc(100% - 80px) !important;
        }

        /* Mobile Backdrop Overlay */
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

        .sidebar-overlay.show {
            display: block;
        }

        /* Responsive Breakpoints for Mobile / Touch Screens */
        @media (max-width: 991px) {
            .glass-sidebar {
                transform: translateX(-100%);
            }
            .glass-sidebar.mobile-show {
                transform: translateX(0);
            }
            .main-content, .main-content.expanded {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .mobile-close-btn {
                display: flex !important;
            }
        }

        .mobile-close-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50%;
            color: #ffffff;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mobile-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 15px;
        }

        .brand-block h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .brand-block span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
        }

        /* Topbar Toggle Button */
        .sidebar-toggle {
            width: 40px;
            height: 40px;
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

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .dashboard-brand {
            color: #f3a6c8;
        }

        /* Sidebar Icon micro-interaction: subtle brightness + small nudge; label stays put */
        .glass-sidebar .nav-link i.nav-ic {
            transition: transform 0.18s ease, filter 0.18s ease, color 0.18s ease;
        }
        .glass-sidebar .nav-link:hover i.nav-ic,
        .glass-sidebar .nav-link:focus-visible i.nav-ic {
            filter: brightness(1.2);
            transform: translateX(3px);
        }

        /* Respect reduced-motion: keep color/background feedback, drop movement */
        @media (prefers-reduced-motion: reduce) {
            .glass-sidebar .nav-link {
                transition: background-color 0.01ms ease, color 0.01ms ease;
            }
            .glass-sidebar .nav-link:hover,
            .glass-sidebar .nav-link:focus-visible {
                transform: none;
            }
            .glass-sidebar .nav-link:hover i.nav-ic,
            .glass-sidebar .nav-link:focus-visible i.nav-ic {
                transform: none;
                filter: none;
            }
        }

        /* Sidebar Icon Accents (SakuraLearn purple/pink theme + distinguishable accents) */
        .glass-sidebar .nav-ic-dashboard  { color: #f472b6; } /* pink    – Dashboard */
        .glass-sidebar .nav-ic-admins     { color: #60a5fa; } /* blue    – Admin Management */
        .glass-sidebar .nav-ic-lessons    { color: #fbbf24; } /* amber   – Lecture Management */
        .glass-sidebar .nav-ic-vocab      { color: #a78bfa; } /* violet  – Vocabulary Management */
        .glass-sidebar .nav-ic-categories { color: #2dd4bf; } /* teal    – Category Management */
        .glass-sidebar .nav-ic-review     { color: #38bdf8; } /* cyan    – Exam Question Review */
        .glass-sidebar .nav-ic-exams      { color: #fb923c; } /* orange  – Exam Management */
        .glass-sidebar .nav-ic-settings   { color: #c084fc; } /* purple  – Settings */
        .glass-sidebar .nav-ic-logout     { color: #f87171; } /* rose    – Logout */
        .glass-sidebar .nav-ic-home       { color: #4ade80; } /* green   – Back to Home Page */
    </style>
</head>
<body>

    <!-- Backdrop Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-wrapper-container">
        <!-- Sidebar -->
        <nav id="sidebar" class="glass-sidebar">
            <div class="sidebar-header">
                <div class="brand-block">
                    <span><h4 style="color: #f3a6c8;">Nihongobloom</h4></span>
                    <span>
                        {{ auth()->user()->role === 'super_admin' ? 'Super Admin Portal' : 'Admin Portal' }}
                    </span>
                </div>
                <!-- Close  button for mobile/touch screens -->
                <button type="button" class="mobile-close-btn" id="sidebarCloseBtn" title="Close Sidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

           <div class="px-3">
                <ul class="nav flex-column">
                    <!-- Dashboard Link -->
                    <li class="nav-item mb-2">
                        <a href="{{ auth()->user()->role === 'super_admin' ? route('super.admin.dashboard') : route('admin.dashboard') }}"
                           class="nav-link {{ request()->routeIs('super.admin.dashboard', 'admin.dashboard') ? 'active bg-purple' : 'text-white' }}">
                            <i class="bi bi-speedometer2 me-2 nav-ic nav-ic-dashboard"></i> <span class="nav-text">Dashboard</span>
                        </a>
                    </li>

                    @if(auth()->user()->role === 'super_admin')
                    <!-- Admin Management Link -->
                    <li class="nav-item mb-2">
                        <a href="{{ route('super.admin.admins.index') }}" 
                           class="nav-link {{ request()->routeIs('super.admin.admins.*') ? 'active bg-purple' : 'text-white' }}">
                            <i class="bi bi-shield-lock-fill me-2 nav-ic nav-ic-admins"></i> <span class="nav-text">Admin Management</span>
                        </a>
                     </li>
              <!-- Lesson Management Link --> 
<li class="nav-item mb-2">
    <a href="{{ route('super.admin.lessons.index') }}" 
       class="nav-link {{ request()->routeIs('super.admin.lessons.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-book-fill me-2 nav-ic nav-ic-lessons"></i> <span class="nav-text">Lecture Management</span>
    </a>
</li>
<li class="nav-item mb-2">
    <a href="{{ route('super.admin.training.index') }}"
       class="nav-link {{ request()->routeIs('super.admin.training.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-journal-richtext me-2 nav-ic nav-ic-vocab"></i> <span class="nav-text">Vocabulary Management</span>
    </a>
</li>
   <li class="nav-item mb-2">
    <a href="{{ route('super.admin.categories.index') }}" 
    class="nav-link {{ request()->routeIs('super.admin.categories.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-folder me-2 nav-ic nav-ic-categories"></i> <span class="nav-text">Category Management</span>
    </a>
</li>
                    @endif
@if(auth()->user()->role === 'admin')
<li class="nav-item mb-2">
    <a href="{{ route('admin.exams.index') }}"
       class="nav-link {{ request()->routeIs('admin.exams.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-journal-text me-2 nav-ic nav-ic-review"></i> <span class="nav-text">Exam Questions</span>
    </a>
</li>
@endif
@if(auth()->user()->role === 'super_admin')
<li class="nav-item mb-2">
    <a href="{{ route('super.admin.exam.questions.exams') }}"
       class="nav-link {{ request()->routeIs('super.admin.exam.questions.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-clipboard-check me-2 nav-ic nav-ic-review"></i> <span class="nav-text">Exam Question Review</span>
    </a>
</li>
<li class="nav-item mb-2">
    <a href="{{ route('super.admin.exams.index') }}"
       class="nav-link {{ request()->routeIs('super.admin.exams.*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-journal-check me-2 nav-ic nav-ic-exams"></i> <span class="nav-text">Exam Management</span>
    </a>
</li>

<!-- Settings Link (Super Admin) — single entry point for settings-related options -->
<li class="nav-item mb-2">
    <a href="{{ route('super.admin.settings') }}"
       class="nav-link {{ request()->routeIs('super.admin.settings*') ? 'active bg-purple' : 'text-white' }}">
        <i class="bi bi-gear-fill me-2 nav-ic nav-ic-settings"></i> <span class="nav-text">Settings</span>
    </a>
</li>

<!-- Logout (Super Admin) -->
<li class="nav-item mb-2">
    <a href="{{ route('logout') }}" class="nav-link text-white"
       onclick="event.preventDefault(); document.getElementById('admin-sidebar-logout-form').submit();">
        <i class="bi bi-box-arrow-right me-2 nav-ic nav-ic-logout"></i> <span class="nav-text">Logout</span>
    </a>
    <form id="admin-sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>


<!-- Back to Home Page (public website) -->
<li class="nav-item mb-2">
    <a href="{{ route('home') }}" class="nav-link text-white">
        <i class="bi bi-house-door-fill me-2 nav-ic nav-ic-home"></i> <span class="nav-text">Back to Home Page</span>
    </a>
</li>
@endif
    </ul>
    </div>
 </nav>

        <!-- Main Content Area -->
        <main id="mainContent" class="main-content px-4 py-4">
            <!-- Top Navbar with Toggle Button -->
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

            <!-- Yield Content -->
            @yield('admin_content')
        </main>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle & Overlay Close Script -->
    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Toggle Sidebar open/close
        sidebarToggle.addEventListener('click', function () {
            if (window.innerWidth <= 991) {
                sidebar.classList.toggle('mobile-show');
                sidebarOverlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        // Close sidebar when clicking the (X) button on mobile
        sidebarCloseBtn.addEventListener('click', function () {
            sidebar.classList.remove('mobile-show');
            sidebarOverlay.classList.remove('show');
        });

        // Close sidebar when clicking outside on the overlay background
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('mobile-show');
            sidebarOverlay.classList.remove('show');
        });
    </script>
</body>
</html>
