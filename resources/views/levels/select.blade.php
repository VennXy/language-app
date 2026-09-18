@extends('layouts.app')

@section('content')
<div class="container py-5" style="min-height: 75vh;">
    <!-- Header Title -->
    <div class="text-center mb-5">
        <h2 class="fw-extrabold display-5 mb-3 text-white">Choose Your Japanese Level</h2>
        <p class="text-muted">Select your current proficiency to get started with tailored courses.</p>
    </div>

    <!-- Levels Grid -->
    <div class="row g-4 justify-content-center">
        @forelse($levels as $level)
            <div class="col-md-4 col-sm-6">
                <a href="{{ route('level.courses', $level->id) }}" class="text-decoration-none">
                    <div class="level-card p-4 h-100 rounded-4 position-relative">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge px-3 py-2 rounded-pill fs-5" style="background: rgba(147, 51, 234, 0.2); color: #c084fc;">
                                {{ $level->name }}
                            </span>
                            <i class="bi bi-arrow-right fs-4 text-white-50 arrow-icon"></i>
                        </div>
                        <h4 class="fw-bold mb-2 text-white">{{ $level->title }}</h4>
                        <p class="text-muted small mb-0">{{ $level->description }}</p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted">No levels found in the database.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Custom CSS for Hover Effect -->
<style>
    .level-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.3s ease-in-out;
    }

    .level-card:hover {
        transform: translateY(-8px);
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(192, 132, 252, 0.4);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .level-card:hover .arrow-icon {
        color: #c084fc !important;
        transform: translateX(4px);
        transition: 0.3s ease;
    }
    
    .arrow-icon {
        transition: 0.3s ease;
    }
</style>
@endsection