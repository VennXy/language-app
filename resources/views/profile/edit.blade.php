@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <!-- Sidebar (Profile, Manage Access) -->
        <div class="col-md-3 mb-4">
            <div class="card bg-dark text-white border border-secondary shadow-lg p-3" style="background: rgba(13, 6, 26, 0.85) !important; backdrop-filter: blur(12px); border-radius: 12px;">
                <h5 class="fw-bold mb-3 px-3 text-purple" style="color: #c084fc;">Settings</h5>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action bg-transparent text-white border-0 rounded mb-1 {{ request()->routeIs('profile.edit') ? 'active-sidebar-item' : '' }}">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                    <a href="{{ route('profile.access') }}" class="list-group-item list-group-item-action bg-transparent text-white border-0 rounded mb-1 {{ request()->routeIs('profile.access') ? 'active-sidebar-item' : '' }}">
                        <i class="bi bi-shield-lock me-2"></i> Manage Access
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content (My Profile Form) -->
        <div class="col-md-8">
            <div class="card bg-dark text-white border border-secondary shadow-lg p-4" style="background: rgba(13, 6, 26, 0.85) !important; backdrop-filter: blur(12px); border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0" style="color: #c084fc;">My Profile</h3>
                    <!-- Edit Button -->
                    <button type="button" id="editBtn" class="btn btn-outline-light btn-sm rounded-pill px-3" onclick="enableEditing()">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </button>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
                    @csrf
                    @method('PATCH')

                    <!-- ပုံမှန်မြင်ရမည့် Profile Image (Hover လုပ်ရင် Change ပေါ်မည့်နေရာ) -->
                    <div class="text-center mb-4" id="normalProfileView">
                        <div class="position-relative d-inline-block">
                            <div class="rounded-circle overflow-hidden border border-purple mx-auto mb-2 shadow profile-img-box" 
                                 style="width: 100px; height: 100px;" 
                                 id="profileContainer" onclick="triggerFileSelect()">
                                
                                @if(Auth::user()->profile_photo)
                                    <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile Photo" class="w-100 h-100 object-fit-cover cursor-pointer" id="currentProfileImg">
                                @else
                                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ Auth::user()->name }}" alt="Default Avatar" class="w-100 h-100 object-fit-cover cursor-pointer" id="currentProfileImg">
                                @endif

                                <!-- Hover Overlay (Edit Mode) -->
                                <div id="editOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white" 
                                     style="background: rgba(0, 0, 0, 0.6); opacity: 0; transition: opacity 0.2s ease; cursor: pointer; display: none !important;">
                                    <i class="bi bi-camera-fill fs-4 mb-1"></i>
                                    <small style="font-size: 10px;">Change</small>
                                </div>
                            </div>

                            <!-- Hidden File Input -->
                            <input type="file" name="profile_photo" id="hiddenFileInput" class="d-none" accept="image/*">
                        </div>
                    </div>

                    <div id="inlineCropContainer" class="text-center mb-4" style="display: none;">
                        <div class="mx-auto mb-3" style="max-width: 280px; max-height: 280px;">
                            <img id="imageToCropInline" src="" style="max-width: 100%;">
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 me-2" id="cancelCropBtn">Cancel</button>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" id="applyCropBtn">Crop & Apply</button>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-white">First Name</label>
                            <input type="text" name="first_name" id="firstNameInput" value="{{ old('first_name', Auth::user()->first_name) }}" class="form-control bg-dark text-white border-secondary" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Last Name</label>
                            <input type="text" name="last_name" id="lastNameInput" value="{{ old('last_name', Auth::user()->last_name) }}" class="form-control bg-dark text-white border-secondary" readonly>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-white">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="dobInput" value="{{ old('date_of_birth', Auth::user()->date_of_birth) }}" class="form-control bg-dark text-white border-secondary" readonly>
                    </div>

                    <!-- Save Changes / Cancel -->
                    <div id="actionButtons" style="display: none;" class="text-end">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 me-2" onclick="cancelEditing()">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--  Image Viewer Modal -->
<div id="imageViewerModal" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px); z-index: 99999; display: none; align-items: center; justify-content: center;">
    <button type="button" id="closeViewerBtn" style="position: absolute; top: 25px; right: 35px; background: none; border: none; color: white; font-size: 40px; cursor: pointer; z-index: 100000;">&times;</button>
    <div style="display: flex; justify-content: center; align-items: center;">
        <img id="fullImageView" src="" alt="Profile Full View" style="width: 280px; height: 280px; border-radius: 50%; object-fit: cover; border: 3px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
    </div>
</div>

<script>
    let cropper;
    let isEditMode = false;
    const hiddenFileInput = document.getElementById('hiddenFileInput');
    const imageToCropInline = document.getElementById('imageToCropInline');
    const currentProfileImg = document.getElementById('currentProfileImg');
    const normalProfileView = document.getElementById('normalProfileView');
    const inlineCropContainer = document.getElementById('inlineCropContainer');

    function enableEditing() {
        isEditMode = true;
        
        document.getElementById('firstNameInput').removeAttribute('readonly');
        document.getElementById('lastNameInput').removeAttribute('readonly');
        document.getElementById('dobInput').removeAttribute('readonly');

        const profileBox = document.getElementById('profileContainer');
        profileBox.classList.add('editable');
        document.getElementById('editOverlay').style.setProperty('display', 'flex', 'important');

        document.getElementById('editBtn').style.display = 'none';
        document.getElementById('actionButtons').style.display = 'block';
    }

    function triggerFileSelect() {
        if (isEditMode) {
            hiddenFileInput.click();
        }
    }

    document.getElementById('profileContainer').addEventListener('click', triggerFileSelect);

    hiddenFileInput.addEventListener('change', function (e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                imageToCropInline.src = e.target.result;
                
                normalProfileView.style.display = 'none';
                inlineCropContainer.style.display = 'block';

                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(imageToCropInline, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    restore: false,
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(files[0]);
        }
    });

    document.getElementById('applyCropBtn').addEventListener('click', function () {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300,
        });

        currentProfileImg.src = canvas.toDataURL();

        canvas.toBlob(function (blob) {
            const fileName = "profile_photo.jpg";
            const file = new File([blob], fileName, { type: "image/jpeg" });
            
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            hiddenFileInput.files = dataTransfer.files;

            cropper.destroy();
            cropper = null;
            inlineCropContainer.style.display = 'none';
            normalProfileView.style.display = 'block';
        }, 'image/jpeg');
    });

    document.getElementById('cancelCropBtn').addEventListener('click', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        inlineCropContainer.style.display = 'none';
        normalProfileView.style.display = 'block';
        hiddenFileInput.value = '';
    });

    // Image Viewer Modal Script
    document.addEventListener('DOMContentLoaded', function () {
        const imageViewerModal = document.getElementById('imageViewerModal');
        const fullImageView = document.getElementById('fullImageView');
        const closeViewerBtn = document.getElementById('closeViewerBtn');
        const profileImgTarget = document.getElementById('currentProfileImg'); 

        if (profileImgTarget) {
            profileImgTarget.addEventListener('click', function (e) {
                if (!isEditMode) {
                    e.stopPropagation();
                    fullImageView.src = this.src;
                    imageViewerModal.style.display = 'flex';
                }
            });
        }

        if (closeViewerBtn) {
            closeViewerBtn.addEventListener('click', function () {
                imageViewerModal.style.display = 'none';
            });
        }

        imageViewerModal.addEventListener('click', function (e) {
            if (e.target === imageViewerModal) {
                imageViewerModal.style.display = 'none';
            }
        });
    });

    function cancelEditing() {
        location.reload();
    }
</script>

<!-- Sidebar & Cropper Custom Style CSS -->
<style>
    .list-group-item:hover {
        background: rgba(147, 51, 234, 0.15) !important;
        color: #c084fc !important;
    }
    .active-sidebar-item {
        background: rgba(147, 51, 234, 0.3) !important;
        color: #ffffff !important;
        font-weight: 600;
        border-left: 4px solid #c084fc !important;
    }

    .profile-img-box.editable {
        cursor: pointer;
    }
    .profile-img-box.editable:hover #editOverlay {
        opacity: 1 !important;
    }

    .cropper-modal {
        background-color: rgba(0, 0, 0, 0.5) !important;
        opacity: 1 !important;
    }

    .cropper-view-box {
        border-radius: 50% !important;
        outline: 2px solid #ffffff !important;
        outline-offset: 0px;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5) !important;
        background-color: transparent !important;
    }

    .cropper-face {
        background-color: transparent !important;
        border-radius: 50% !important;
    }
</style>
@endsection