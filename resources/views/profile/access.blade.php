@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card bg-dark text-white border border-secondary shadow-lg p-4" style="background: rgba(13, 6, 26, 0.85) !important; backdrop-filter: blur(12px); border-radius: 12px;">
                <h3 class="fw-bold mb-4" style="color: #c084fc;">Manage Access</h3>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Email Address Section -->
                <div class="mb-4">
                    <label class="form-label text-muted">Email Address</label>
                    <form action="{{ route('profile.email.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="input-group">
                            @php
                                $email = Auth::user()->email;
                                $maskedEmail = substr($email, 0, 3) . '****' . strstr($email, '@');
                            @endphp
                            <!-- Mask လုပ်ထားသော Email ကို အစပိုင်းတွင် ပြသမည်၊ data-real-email တွင် အီးမေးလ်အစစ်ကို သိမ်းထားမည် -->
                            <input type="text" id="emailInput" value="{{ $maskedEmail }}" data-real-email="{{ Auth::user()->email }}" class="form-control bg-dark text-white border-secondary" readonly required>
                            
                            <!-- ပုံမှန်အခြေအနေတွင် name ဖြုတ်ထားပြီး Edit လုပ်မှ ထည့်မည် (Database ထဲသို့ *** ကြီး မရောက်စေရန်) -->
                            <input type="hidden" name="email" id="hiddenEmailField" value="{{ Auth::user()->email }}">

                            <button type="button" class="btn btn-outline-light" id="editEmailBtn" onclick="toggleEmailEdit()">Change Email</button>
                            <button type="submit" class="btn btn-primary" id="saveEmailBtn" style="display: none;">Save</button>
                        </div>
                    </form>
                </div>

                <hr class="border-secondary my-4">

                <!-- Password Section -->
                <div class="mb-4">
                    <label class="form-label text-muted">Password</label>
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="mb-3">
                            <input type="password" name="current_password" placeholder="Current Password" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" placeholder="New Password" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password_confirmation" placeholder="Confirm New Password" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-light">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for toggling email edit mode -->
<script>
    function toggleEmailEdit() {
        const emailInput = document.getElementById('emailInput');
        const hiddenEmailField = document.getElementById('hiddenEmailField');
        const editBtn = document.getElementById('editEmailBtn');
        const saveBtn = document.getElementById('saveEmailBtn');

        
        emailInput.value = emailInput.getAttribute('data-real-email');
        emailInput.name = 'email'; 
        hiddenEmailField.remove(); 
        
        emailInput.removeAttribute('readonly');
        emailInput.focus();
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
    }
</script>

<!-- Sidebar Active Style CSS -->
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
</style>
@endsection