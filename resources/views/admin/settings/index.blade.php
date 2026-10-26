@extends('layouts.admin')

@section('admin_content')

<style>
    .settings-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem 1.4rem;
        border-radius: 16px;
        color: #fff;
        text-decoration: none;
        transition: transform .2s ease, border-color .2s ease, background .2s ease;
    }
    .settings-card:hover {
        transform: translateY(-2px);
        border-color: rgba(192, 132, 252, 0.45) !important;
        background: rgba(168, 85, 247, 0.10) !important;
        color: #fff;
    }
    .settings-card .icon-wrap {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgba(168, 85, 247, 0.18);
        color: #d8b4fe;
        font-size: 1.35rem;
    }
    .settings-card h6 { margin: 0; font-weight: 700; }
    .settings-card p { margin: 2px 0 0; font-size: 0.85rem; color: rgba(255, 255, 255, 0.6); }
    .settings-card .chevron { margin-left: auto; color: rgba(255, 255, 255, 0.4); }
</style>

<div class="container-fluid px-2 px-md-4">
    <div class="mb-4">
        <h2 class="fw-bold text-white mb-1">Settings</h2>
        <p class="text-white-50 mb-0">Manage your Super Admin profile, access, and account security.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card glass-card border-0 p-3 p-md-4 shadow-lg" style="backdrop-filter: blur(10px); border-radius: 16px;">
        <div class="d-flex flex-column gap-3">

            <!-- Profile -->
            <a href="{{ route('super.admin.settings.profile') }}" class="settings-card glass-card border-0">
                <span class="icon-wrap"><i class="bi bi-person-fill"></i></span>
                <span>
                    <h6>Profile</h6>
                    <p>Update your name, date of birth, and profile photo.</p>
                </span>
                <i class="bi bi-chevron-right chevron"></i>
            </a>

            <!-- Manage Access -->
            <a href="{{ route('super.admin.settings.access') }}" class="settings-card glass-card border-0">
                <span class="icon-wrap"><i class="bi bi-shield-lock-fill"></i></span>
                <span>
                    <h6>Manage Access</h6>
                    <p>Change your account email and password.</p>
                </span>
                <i class="bi bi-chevron-right chevron"></i>
            </a>

            <!-- Back to Home Page -->
            <a href="{{ route('home') }}" class="settings-card glass-card border-0">
                <span class="icon-wrap"><i class="bi bi-house-door-fill"></i></span>
                <span>
                    <h6>Back to Home Page</h6>
                    <p>Return to the public SakuraLearn website.</p>
                </span>
                <i class="bi bi-chevron-right chevron"></i>
            </a>

        </div>
    </div>
</div>

@endsection
