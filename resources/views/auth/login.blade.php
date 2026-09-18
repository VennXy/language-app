@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 85vh;">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg" style="background-color: #130f26; border: 1px solid rgba(168, 85, 247, 0.2) !important; border-radius: 1rem;">
            <div class="card-body p-4 p-md-5">
                
                <!-- Logo & Subtitle -->
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-white" style="letter-spacing: -1px;">kotoba<span style="color: #c084fc;">.</span></h2>
                    <p class="text-white-50 small">Welcome back! Please login to continue learning.</p>
                </div>

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-white-50 small fw-semibold">Email Address</label>
                        <input id="email" type="email" class="form-control text-white @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label text-white-50 small fw-semibold">Password</label>
                        <input id="password" type="password" class="form-control text-white @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Remember Me Checkbox -->
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                            <label class="form-check-label text-white-50 small" for="remember">
                                Remember Me
                            </label>
                        </div>
                        @if (Route::has('password.request'))
                            <a class="text-decoration-none small" href="{{ route('password.request') }}" style="color: #c084fc;">
                                Forgot Password?
                            </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn text-white fw-semibold py-2" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none; border-radius: 50px;">
                            Log In →
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="text-center mt-3">
                        <p class="text-white-50 small mb-0">Don't have an account? <a href="{{ route('register') }}" class="text-decoration-none" style="color: #c084fc;">Sign up</a></p>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection