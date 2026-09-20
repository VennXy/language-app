@extends('layouts.admin')

@section('admin_content')
<style>
    .test {
        color: pink;
    }
</style>
<div class="container py-5">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold">Super Admin Dashboard</h2>
            <p class="text">Manage system administrators and monitor application users.</p>
        </div>
        <!-- Button to trigger Add Admin Modal -->
        <button type="button" class="btn px-4 py-2 rounded-pill fw-semibold text-dark shadow-sm" style="background: #c084fc; border: none;" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="bi bi-person-plus-fill me-2"></i> Add New Admin
        </button>
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
            <div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(5px);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold text small text-uppercase fw-semibold">Total Admins</span>
                        <h3 class="text-white fw-bold mt-1 mb-0">{{ count($admins) }}</h3>
                    </div>
                    <div class="p-3 rounded-circle text-purple" style="background: rgba(192, 132, 252, 0.1); color: #c084fc;">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-4 rounded-4 shadow-sm border border-purple-subtle" style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(5px);">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="fw-bold text small text-uppercase fw-semibold">Total Users in System</span>
                        <h3 class="text-white fw-bold mt-1 mb-0">{{ $totalUsers }}</h3>
                    </div>
                    <div class="p-3 rounded-circle text-info" style="background: rgba(56, 189, 248, 0.1); color: #38bdf8;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
         <!-- Glassmorphism Admin List Table Section -->
<div class="card shadow-lg rounded-4 overflow-hidden mb-4" style="background: rgba(20, 10, 40, 0.35) !important; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.15) !important;">
    <div class="card-header py-3 px-4" style="background: rgba(255, 255, 255, 0.03) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;">
        <h5 class="text-white mb-0 fw-semibold">Administrators List</h5>
    </div>
    <div class="card-body p-0" style="background: transparent !important;">
        <div class="table-responsive w-100">
            <table class="table mb-0 align-middle text-nowrap" style="background: transparent !important; color: #ffffff;">
                <thead style="background: rgba(255, 255, 255, 0.05) !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                    <tr>
                        <th class="py-3 px-4 text-uppercase fs-7 fw-bold" style="background: transparent !important; color: #d8b4fe;">Admin Name</th>
                        <th class="py-3 px-4 text-uppercase fs-7 fw-bold" style="background: transparent !important; color: #d8b4fe;">Email Address</th>
                        <th class="py-3 px-4 text-uppercase fs-7 fw-bold" style="background: transparent !important; color: #d8b4fe;">Date of Birth</th>
                        <th class="py-3 px-4 text-uppercase fs-7 fw-bold" style="background: transparent !important; color: #d8b4fe;">Role</th>
                        <th class="py-3 px-4 text-uppercase fs-7 fw-bold text-end" style="background: transparent !important; color: #d8b4fe;">Actions</th>
                    </tr>
                </thead>
                <tbody style="background: transparent !important;">
                    @forelse($admins as $admin)
                        <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); background: transparent !important;">
                            <td class="py-3 px-4 text-white fw-medium" style="background: transparent !important;">
                                {{ $admin->name }}
                                @if($admin->id === auth()->id())
                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 10px;">You</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-light" style="background: transparent !important; opacity: 0.9;">{{ $admin->email }}</td>
                            <td class="py-3 px-4 text-light" style="background: transparent !important; opacity: 0.8;">{{ $admin->date_of_birth ?? 'N/A' }}</td>
                            <td class="py-3 px-4" style="background: transparent !important;">
                                @if($admin->role === 'super_admin')
                                    <span class="badge px-2 py-1 text-dark fw-bold" style="background: #c084fc;">Super Admin</span>
                                @else
                                    <span class="badge px-2 py-1" style="background: rgba(192, 132, 252, 0.2); color: #c084fc;">Admin</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-end" style="background: transparent !important;">
                                @if($admin->id === auth()->id())
                                    <a href="#" class="btn btn-sm btn-outline-light px-3 rounded-pill me-1" style="border-color: rgba(255,255,255,0.3);">
                                        <i class="bi bi-eye-fill me-1"></i> View
                                    </a>
                                    <a href="#" class="btn btn-sm px-3 rounded-pill text-dark fw-semibold shadow-sm" style="background: #c084fc;">
                                        <i class="bi bi-pencil-fill me-1"></i> Edit
                                    </a>
                                @else
                                    <form action="{{ route('super.admin.admins.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('ဒီ Admin ကို ဖယ်ရှားမှာ သေချာပါသလား?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
                                            <i class="bi bi-trash-fill me-1"></i> Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted" style="background: transparent !important;">No administrators found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
    
</div>

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
@endsection