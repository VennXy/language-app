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
    </style>
    @stack('styles')
</head>
<body>
   <!-- Navbar Component -->  
<header class="sticky-top w-full">
    <nav class="navbar navbar-expand-lg navbar-dark py-3">
        <div class="container max-w-7xl">
            <!-- Brand / Logo -->
            <a class="navbar-brand fw-extrabold fs-3 gradient-text" href="{{ url('/') }}">SakuraLearn</a>
            
            <!-- Mobile Toggle Button (Hamburger) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Collapse Wrapper -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Center Navigation Links -->
                <ul class="navbar-nav mx-auto my-2 my-lg-0 gap-1 align-items-lg-center">
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
                                <!-- Super Admin Dashboard Link -->
                                @if(Auth::user()->role === 'super_admin')
                                    <li>
                                        <a class="dropdown-item py-2" href="{{ route('super.admin.admins.index') }}" style="color: #c084fc;">
                                            <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                                @endif

                                <li><a class="dropdown-item py-2" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> My Profile</a></li>
                                <li><a class="dropdown-item py-2" href="{{ route('profile.access') }}"><i class="bi bi-shield-lock me-2"></i> Manage Access</a></li>
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

    <!-- Main Dynamic Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer Component -->
    <footer class="py-4 border-top border-opacity-10" style="border-color: rgba(255, 255, 255, 0.08) !important;">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="fw-extrabold fs-4 gradient-text">SukuraLearn.</div>
            <nav class="d-flex flex-wrap justify-content-center gap-4 small opacity-75">
                <a href="#courses" class="text-decoration-none text-light">Courses</a>
                <a href="#methods" class="text-decoration-none text-light">Methods</a>
                <a href="#community" class="text-decoration-none text-light">Community</a>
                <a href="#" class="text-decoration-none text-light">Privacy</a>
                <a href="#" class="text-decoration-none text-light">Terms</a>
            </nav>
            <small class="text-muted">&copy; <span id="currentYear"></span> SukuraLearn. Made with <i class="bi bi-heart-fill text-danger"></i> in Tokyo.</small>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();

        // Navbar Active Link Switching
        const navLinks = document.querySelectorAll('.navbar .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>