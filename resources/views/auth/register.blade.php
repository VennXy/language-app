@extends('layouts.app')

@section('content')
<div class="container d-flex justify-content-center align-items-center" style="min-height: 85vh;">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg" style="background-color: #130f26; border: 1px solid rgba(168, 85, 247, 0.2) !important; border-radius: 1rem;">
            <div class="card-body p-4 p-md-5">
                
                <!-- Logo & Subtitle -->
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-white" style="letter-spacing: -1px;">kotoba<span style="color: #c084fc;">.</span></h2>
                    <p class="text-white-50 small">Create your account to start learning Japanese effortlessly.</p>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- First Name -->
                    <div class="mb-3">
                        <label for="first_name" class="form-label text-white-50 small fw-semibold">First Name</label>
                        <input id="first_name" type="text" class="form-control text-white @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" autofocus style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('first_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div class="mb-3">
                        <label for="last_name" class="form-label text-white-50 small fw-semibold">Last Name</label>
                        <input id="last_name" type="text" class="form-control text-white @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('last_name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Date of Birth -->
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label text-white-50 small fw-semibold">Date of Birth</label>
                        <input id="date_of_birth" type="date" class="form-control text-white @error('date_of_birth') is-invalid @enderror" name="date_of_birth" value="{{ old('date_of_birth') }}" required style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('date_of_birth')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label text-white-50 small fw-semibold">Email Address</label>
                        <input id="email" type="email" class="form-control text-white @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label text-white-50 small fw-semibold">Password</label>
                        <input id="password" type="password" class="form-control text-white @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password-confirm" class="form-label text-white-50 small fw-semibold">Confirm Password</label>
                        <input id="password-confirm" type="password" class="form-control text-white" name="password_confirmation" required autocomplete="new-password" style="background-color: #1e1b4b; border-color: rgba(168, 85, 247, 0.3);">
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn text-white fw-semibold py-2" style="background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); border: none; border-radius: 50px;">
                            Get Started Free →
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center mt-3">
                        <p class="text-white-50 small mb-0">Already have an account? <a href="{{ route('login') }}" class="text-decoration-none" style="color: #c084fc;">Log in</a></p>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection