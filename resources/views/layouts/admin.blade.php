<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SakuraLearn</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Dashboard Background with Sakura Image & Dark Overlay */
        body {
            min-height: 100vh;
            background: 
                        url("{{ asset('storage/images/sakura.png') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            margin: 0;
        }

        /* Sidebar သီးသန့် မူလပုံစံ (ပို၍မှောင်ပြီး ပုံစံကျသော Glass Style) */
        .glass-sidebar {
            background: rgba(18, 13, 28, 0.75) !important;
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

        /* Topbar နှင့် Cards များအတွက် Style */
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
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
        }

        .glass-sidebar .nav-link:hover {
            background: rgba(244, 114, 182, 0.2);
            color: #ffffff;
            transform: translateX(4px);
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

        /* Custom Icon Colors */
        .icon-dashboard { color: #f472b6; }
        .icon-admin { color: #60a5fa; }
        .icon-lesson { color: #34d399; }
        .icon-logout { color: #f87171; }

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
        .text {
            color: pink;
        }
    </style>
</head>
<body>

    <!-- Backdrop Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid p-0">
        <div class="d-flex">
            <!-- Sidebar -->
            <nav id="sidebar" class="glass-sidebar">
                <div class="sidebar-header">
                    <div class="brand-block">
                  <span> <h4 style="color: #f3a6c8;">SakuraLearn</h4></span>
                  
                        
                    
                       
                        <span>
                            {{ auth()->user()->role === 'super_admin' ? 'Super Admin Portal' : 'Admin Portal' }}
                        </span>
                    </div>
                    <!-- Close (X) button for mobile/touch screens -->
                    <button type="button" class="mobile-close-btn" id="sidebarCloseBtn" title="Close Sidebar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="px-3">
                    <ul class="nav flex-column gap-2">
                        <!-- Dashboard Link -->
                        <li class="nav-item">
                            <a href="#" class="nav-link px-3 active" title="Dashboard">
                                <i class="bi bi-speedometer2 icon-dashboard me-2"></i> <span class="nav-text">Dashboard</span>
                            </a>
                        </li>

                        <!-- Super Admin Menu -->
                        @if(auth()->user()->role === 'super_admin')
                            <li class="nav-item">
                                <a href="{{ route('super.admin.admins.index') }}" class="nav-link px-3" title="Admin Management">
                                    <i class="bi bi-shield-lock-fill icon-admin me-2"></i> <span class="nav-text">Admin Management</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link px-3" title="Lesson Management">
                                    <i class="bi bi-book-fill icon-lesson me-2"></i> <span class="nav-text">Lesson Management</span>
                                </a>
                            </li>
                        @endif

                        <!-- Standard Admin Menu -->
                        @if(auth()->user()->role === 'admin')
                            <li class="nav-item">
                                <a href="#" class="nav-link px-3" title="Quiz Management">
                                    <i class="bi bi-patch-question-fill icon-lesson me-2"></i> <span class="nav-text">Quiz Management</span>
                                </a>
                            </li>
                        @endif

                        <!-- Logout -->
                        <li class="nav-item mt-4 pt-4 border-top border-secondary border-opacity-25">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="nav-link text-danger w-100 text-start border-0 bg-transparent px-3" title="Logout">
                                    <i class="bi bi-box-arrow-right icon-logout me-2"></i> <span class="nav-text">Logout</span>
                                </button>
                            </form>
                        </li>
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
                        <span class="fw-bold dashboard-brand">
                            SakuraLearn Management
                        </span>
                    </div>
                </div>

                <!-- Yield Content -->
                @yield('admin_content')
            </main>
        </div>
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