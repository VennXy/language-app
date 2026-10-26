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
                <h4 class="auth-title">Create Your Account 🌸</h4>
                <p class="auth-subtitle">Start your Japanese learning journey</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- First Name & Last Name (Responsive Row) -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label custom-label">First Name</label>
                        <input id="first_name" type="text" class="form-control custom-input-single @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus placeholder="John">
                        @error('first_name')
                            <span class="invalid-feedback d-block mt-1" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label custom-label">Last Name</label>
                        <input id="last_name" type="text" class="form-control custom-input-single @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" placeholder="Doe">
                        @error('last_name')
                            <span class="invalid-feedback d-block mt-1" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <!-- Date of Birth -->
                <div class="mb-3">
                    <label for="date_of_birth" class="form-label custom-label">Date of Birth</label>
                    <input id="date_of_birth" type="date" class="form-control custom-input-single @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth') }}" required style="color-scheme: dark;">
                    @error('date_of_birth')
                        <span class="invalid-feedback d-block mt-1" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label custom-label">Email Address</label>
                    <input id="email" type="email" class="form-control custom-input-single @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
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
                        <input id="password" type="password" class="form-control custom-input border-0 @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="••••••••">
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

                <!-- Confirm Password -->
                <div class="mb-4">
                    <label for="password-confirm" class="form-label custom-label">Confirm Password</label>
                    <div class="input-group custom-input-group">
                        <input id="password-confirm" type="password" class="form-control custom-input border-0" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                        <button class="btn custom-toggle-btn" type="button" id="toggleConfirmPassword">
                            <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid mb-3">
                    <button type="submit" class="btn custom-gradient-btn">
                        Get Started Free →
                    </button>
                </div>

                <!-- Login Link -->
                <div class="text-center mt-3">
                    <p class="text-muted small mb-0">Already have an account? <a href="{{ route('login') }}" class="text-decoration-none custom-link fw-semibold">Log in</a></p>
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
    .custom-input-single {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-radius: 12px !important;
        color: #ffffff !important;
        height: 48px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    .custom-input-single:focus {
        border-color: #8B5CF6 !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2) !important;
    }
    .custom-input-single::placeholder {
        color: #64748b;
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

    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
        const confirmInput = document.getElementById('password-confirm');
        const icon = document.getElementById('toggleConfirmPasswordIcon');
        if (confirmInput.type === 'password') {
            confirmInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            confirmInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>
@endsection