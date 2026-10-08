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
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-white fw-bold">Admin Management</h2>
            <p class="text-light opacity-75">Manage system administrators, update roles, and monitor permissions.</p>
        </div>
        <!-- Button to trigger Add Admin Modal -->
        <button type="button" class="btn btn-modern-purple px-4 py-2 rounded-pill fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
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
    <!-- View Button -->
    <button type="button" class="btn btn-sm btn-modern-glass px-3 rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#viewAdminModal{{ $admin->id }}">
        <i class="bi bi-eye-fill me-1"></i> View
    </button>

    <!-- Edit Button (Glassmorphism + Purple Color) -->
    <button type="button" class="btn btn-sm px-3 rounded-pill me-1 text-white shadow-sm"
            style="
                background: rgba(168, 85, 247, 0.4);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(168, 85, 247, 0.6);
                font-weight: 500;
                transition: all 0.3s ease;
            "
            onmouseover="this.style.background='rgba(168, 85, 247, 0.7)'; this.style.transform='translateY(-1px)';"
            onmouseout="this.style.background='rgba(168, 85, 247, 0.4)'; this.style.transform='translateY(0)';"
            data-bs-toggle="modal" 
            data-bs-target="#editAdminModal{{ $admin->id }}">
        <i class="bi bi-pencil-fill me-1"></i> Edit
    </button>

    @if($admin->id !== auth()->id())
        <!-- Delete Form (Glassmorphism + Red Color) -->
        <form action="{{ route('super.admin.admins.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('Do you confirm for removing this admin?');" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm px-3 rounded-pill text-white shadow-sm"
                    style="
                        background: rgba(239, 68, 68, 0.3);
                        backdrop-filter: blur(10px);
                        -webkit-backdrop-filter: blur(10px);
                        border: 1px solid rgba(239, 68, 68, 0.6);
                        font-weight: 500;
                        transition: all 0.3s ease;
                    "
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.7)'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'; this.style.transform='translateY(0)';"
                    title="Delete Admin">
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

<!-- ========================================== -->
<!-- VIEW MODALS -->
<!-- ========================================== -->
@foreach($admins as $admin)
<div class="modal fade" id="viewAdminModal{{ $admin->id }}" tabindex="-1" aria-labelledby="viewAdminModalLabel{{ $admin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content text-white border-0 shadow-lg rounded-4" style="background: rgba(25, 15, 45, 0.90); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(192, 132, 252, 0.3) !important;">
            
            <div class="modal-header border-bottom border-light border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold" id="viewAdminModalLabel{{ $admin->id }}" style="color: #d8b4fe;">
                    <i class="bi bi-person-badge-fill me-2"></i> Administrator Details
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

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

<!-- ========================================== -->
<!-- EDIT MODALS -->
<!-- ========================================== -->
@foreach($admins as $admin)
<div class="modal fade" id="editAdminModal{{ $admin->id }}" tabindex="-1" aria-labelledby="editAdminModalLabel{{ $admin->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg text-white" style="background: rgba(25, 15, 45, 0.95); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(192, 132, 252, 0.3) !important;">
            <div class="modal-header border-bottom border-light border-opacity-10 px-4 py-3">
                <h5 class="modal-title fw-bold" id="editAdminModalLabel{{ $admin->id }}" style="color: #d8b4fe;">
                    <i class="bi bi-pencil-square me-2"></i> Edit Administrator
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('super.admin.admins.update', $admin->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="name{{ $admin->id }}" class="form-label text-light small opacity-75">Admin Name</label>
                        <input type="text" class="form-control text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" style="background: rgba(255, 255, 255, 0.05);" id="name{{ $admin->id }}" name="name" value="{{ old('name', $admin->name) }}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email{{ $admin->id }}" class="form-label text-light small opacity-75">Email Address</label>
                        <input type="email" class="form-control text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" style="background: rgba(255, 255, 255, 0.05);" id="email{{ $admin->id }}" name="email" value="{{ old('email', $admin->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="date_of_birth{{ $admin->id }}" class="form-label text-light small opacity-75">Date of Birth</label>
                        <input type="date" class="form-control text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" style="background: rgba(255, 255, 255, 0.05);" id="date_of_birth{{ $admin->id }}" name="date_of_birth" value="{{ old('date_of_birth', $admin->date_of_birth) }}">
                    </div>

                    <div class="mb-3">
                        <label for="password{{ $admin->id }}" class="form-label text-light small opacity-75">New Password <span class="text-muted">(Leave blank to keep current)</span></label>
                        <input type="password" class="form-control text-white border-secondary border-opacity-50 rounded-pill px-3 py-2" style="background: rgba(255, 255, 255, 0.05);" id="password{{ $admin->id }}" name="password" placeholder="Minimum 8 characters">
                    </div>
                </div>

                <div class="modal-footer border-top border-light border-opacity-10 px-4 pb-4">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn rounded-pill px-4 text-white fw-semibold shadow-sm" style="background: linear-gradient(135deg, #c084fc 0%, #9333ea 100%);">Update Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection