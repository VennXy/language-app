@extends('layouts.admin')

@section('admin_content')
<style>
    /* Modern Glass Button Base */
    .btn-modern-glass {
        background: rgba(255, 255, 255, 0.07);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #ffffff;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .btn-modern-glass:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(192, 132, 252, 0.5);
        color: #ffffff;
        box-shadow: 0 0 15px rgba(192, 132, 252, 0.4);
        transform: translateY(-2px);
    }

    /* Modern Primary / Purple Glow Button */
    .btn-modern-purple {
        background: linear-gradient(135deg, #c084fc 0%, #9333ea 100%);
        border: 1px solid rgba(192, 132, 252, 0.4);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(147, 51, 234, 0.3);
        transition: all 0.3s ease;
    }
    .btn-modern-purple:hover {
        background: linear-gradient(135deg, #d8b4fe 0%, #a855f7 100%);
        box-shadow: 0 0 20px rgba(192, 132, 252, 0.6);
        color: #ffffff;
        transform: translateY(-2px);
    }

    /* Modern Danger / Delete Button */
    .btn-modern-danger {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        transition: all 0.3s ease;
    }
    .btn-modern-danger:hover {
        background: rgba(239, 68, 68, 0.3);
        border-color: rgba(239, 68, 68, 0.6);
        color: #ffffff;
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        transform: translateY(-2px);
    }
    
</style>

<div class="container py-5">
    @if($dashboardForAdmin ?? false)
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
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold">Super Admin Dashboard</h2>
            <p class="text-light opacity-75">Manage system administrators and monitor application users.</p>
        </div>
        <!-- Button to trigger Add Admin Modal -->
        <!-- <button type="button" class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add New Admin
        </button> -->
    </div>

    <!-- Success Message Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; backdrop-filter: blur(10px);">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Validation Errors Alert -->
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="background: rgba(239, 68, 68, 0.15); color: #f87171; backdrop-filter: blur(10px);">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Statistics Cards Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(5px); border-color: rgba(192, 132, 252, 0.2) !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-light small text-uppercase fw-semibold opacity-75">Total Admins</span>
                        <h3 class="text-white fw-bold mt-1 mb-0">{{ isset($admins)? count($admins):0 }}</h3>
                    </div>
                    <div class="p-3 rounded-circle text-purple" style="background: rgba(192, 132, 252, 0.1); color: #c084fc;">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(5px); border-color: rgba(192, 132, 252, 0.2) !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-light small text-uppercase fw-semibold opacity-75">Total Users in System</span>
                        <h3 class="text-white fw-bold mt-1 mb-0">{{ $totalUsers }}</h3>
                    </div>
                    <div class="p-3 rounded-circle text-info" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
</div>

<!-- ========================================== -->
<!-- VIEW MODALS -->
<!-- ========================================== -->
@foreach($admins as $admin)
<div class="modal fade" id="viewAdminModal{{ $admin->id }}" tabindex="-1" aria-labelledby="viewAdminModalLabel{{ $admin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-white border-0 shadow-lg rounded-4" style="background: rgba(25, 15, 45, 0.90); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(192, 132, 252, 0.3) !important;">
            
            <!-- Modal Header -->
            <div class="modal-header border-bottom border-light border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold" id="viewAdminModalLabel{{ $admin->id }}" style="color: #d8b4fe;">
                    <i class="bi bi-person-badge-fill me-2"></i> Administrator Details
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">Full Name</span>
                            <span class="fs-6 fw-semibold text-white">{{ $admin->name }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">Email Address</span>
                            <span class="fs-6 fw-semibold text-white">{{ $admin->email }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">Date of Birth</span>
                            <span class="fs-6 fw-semibold text-white">{{ $admin->date_of_birth ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">System Role</span>
                            <span class="fs-6 fw-semibold" style="color: #f3e8ff;">{{ ucfirst(str_replace('_', ' ', $admin->role)) }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">Account Created</span>
                            <span class="fs-6 fw-semibold text-white">{{ $admin->created_at ? $admin->created_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <span class="d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #d8b4fe; font-weight: 600;">Last Updated</span>
                            <span class="fs-6 fw-semibold text-white">{{ $admin->updated_at ? $admin->updated_at->format('Y-m-d H:i:s') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top border-light border-opacity-10 px-4 py-3">
                <button type="button" class="btn btn-outline-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
@endforeach

<!-- Add New Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg" style="background: #130b26; color: #fff; border: 1px solid rgba(192, 132, 252, 0.2) !important;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold text-white" id="addAdminModalLabel">Create New Administrator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('super.admin.admins.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="name" class="form-label text-muted small">Admin Name</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" id="name" name="name" required placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small">Email Address</label>
                        <input type="email" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" id="email" name="email" required placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label text-muted small">Date of Birth</label>
                        <input type="date" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" id="date_of_birth" name="date_of_birth">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label text-muted small">Password</label>
                        <input type="password" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" id="password" name="password" required placeholder="Minimum 8 characters">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25 px-4 pb-4">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn rounded-pill px-4 text-dark fw-semibold" style="background: #c084fc;">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
    @endif
@endsection
