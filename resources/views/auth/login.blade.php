@extends('layouts.app')

@section('content')
<div class="glass-auth-wrapper">
    <!-- Pure CSS Blurred Radial Background Effects -->
    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="glass-card-container">
        <div class="glass-card">
            
            <!-- Brand & Heading -->
            <div class="text-center mb-4">
                <h2 class="brand-logo">NihongoBloom<span>.</span></h2>
                <h4 class="auth-title">Welcome Back! 🌸</h4>
                <p class="auth-subtitle">Sign in to continue your learning journey</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label custom-label">Email Address</label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text custom-input-icon">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input id="email" type="email" class="form-control custom-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@example.com">
                    </div>
                    @error('email')
                        <span class="invalid-feedback d-block mt-1" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label custom-label">Password</label>
                    <div class="input-group custom-input-group">
                        <span class="input-group-text custom-input-icon">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input id="password" type="password" class="form-control custom-input border-0 @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                        <button class="btn custom-toggle-btn" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block mt-1" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check custom-checkbox">
                        <input class="form-check-input shadow-none" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-light small" for="remember">
                            Remember Me
                        </label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="text-decoration-none small custom-link" href="{{ route('password.request') }}">
                            Forgot Password?
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn custom-gradient-btn">
                        Log In →
                    </button>
                </div>

                <!-- Register Link -->
                <div class="text-center mt-3">
                    <p class="text-muted small mb-0">Don't have an account? <a href="{{ route('register') }}" class="text-decoration-none custom-link fw-semibold">Sign up</a></p>
                </div>
            </form>

        </div>
    </div>
</div>

<style>
    .glass-auth-wrapper {
        min-height: 100vh;
        background: linear-gradient(135deg, #0B0618 0%, #21123B 100%);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
    }
    .bg-glow-1 {
        position: absolute;
        width: 450px;
        height: 450px;
        background: rgba(139, 92, 246, 0.18);
        filter: blur(80px);
        top: 10%;
        left: 15%;
        border-radius: 50%;
        pointer-events: none;
    }
    .bg-glow-2 {
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(232, 121, 249, 0.12);
        filter: blur(90px);
        bottom: 10%;
        right: 15%;
        border-radius: 50%;
        pointer-events: none;
    }
    .glass-card-container {
        width: 100%;
        max-width: 460px;
        position: relative;
        z-index: 2;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .brand-logo {
        font-size: 34px;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -1px;
        margin-bottom: 6px;
    }
    .brand-logo span {
        color: #E879F9;
    }
    .auth-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 20px;
        margin-bottom: 6px;
    }
    .auth-subtitle {
        color: #c4b5fd;
        font-size: 13px;
        margin-bottom: 0;
    }
    .custom-label {
        color: #e2e8f0;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 6px;
    }
    .custom-input-group {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .custom-input-group:focus-within {
        border-color: #8B5CF6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
    }
    .custom-input-icon {
        background: transparent;
        border: none;
        color: #94a3b8;
        padding-left: 16px;
    }
    .custom-input {
        background: transparent !important;
        border: none !important;
        color: #ffffff !important;
        height: 48px;
        font-size: 14px;
        box-shadow: none !important;
    }
    .custom-input::placeholder {
        color: #64748b;
    }
    .custom-toggle-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        padding-right: 16px;
        box-shadow: none !important;
    }
    .custom-checkbox .form-check-input {
        background-color: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.2);
        cursor: pointer;
    }
    .custom-checkbox .form-check-input:checked {
        background-color: #8B5CF6;
        border-color: #8B5CF6;
    }
    .custom-link {
        color: #E879F9;
        transition: color 0.2s;
    }
    .custom-link:hover {
        color: #f472b6;
        text-decoration: underline !important;
    }
    .custom-gradient-btn {
        background: linear-gradient(135deg, #8B5CF6 0%, #E879F9 100%);
        border: none;
        border-radius: 999px;
        height: 50px;
        color: #ffffff;
        font-weight: 600;
        font-size: 15px;
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        transition: opacity 0.2s, transform 0.2s;
    }
    .custom-gradient-btn:hover {
        opacity: 0.92;
        transform: translateY(-1px);
        color: #ffffff;
    }
    @media (max-width: 576px) {
        .glass-card {
            padding: 24px;
        }
    }
</style>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>
@endsection