@extends('layouts.app')

@section('content')
<div class="container py-5" style="min-height: 75vh;">
    
    <!-- Header & Toggle Tabs (Training / Exam) -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
           <div class="mb-3">
    <a href="{{ route('select.level') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Back to Levels
    </a>
</div>
            <h3 class="fw-bold text-white mb-1">Practice & Test</h3>
            <p class="text-muted small mb-0">Choose your preferred mode to practice Japanese.</p>
        </div>
        
        <!-- Training / Exam Toggle Buttons -->
        <div class="bg-dark p-1 rounded-pill d-flex border border-secondary align-self-start align-self-md-auto">
            <a href="{{ route('level.courses', ['id' => $levelId, 'type' => 'training']) }}" 
               class="btn rounded-pill px-4 py-2 {{ $type == 'training' ? 'btn-success text-white fw-bold' : 'text-muted' }}">
                Training
            </a>
            <a href="{{ route('level.courses', ['id' => $levelId, 'type' => 'exam']) }}" 
               class="btn rounded-pill px-4 py-2 {{ $type == 'exam' ? 'btn-success text-white fw-bold' : 'text-muted' }}">
                Exam
            </a>
        </div>
    </div> 

    <!-- Lessons / History List -->
    <div class="row g-3">
        @forelse($lessons as $lesson)
            <div class="col-12">
                <div class="p-3 rounded-4 d-flex align-items-center justify-content-between" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 rounded-3" style="background: rgba(147, 51, 234, 0.2); color: #c084fc;">
                            <i class="bi bi-book fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-white mb-1">{{ $lesson->title }}</h5>
                            <span class="badge bg-secondary text-dark px-2 py-1">Score: {{ $lesson->score ?? '0' }}%</span>
                        </div>
                    </div>
                    <a href="#" class="text-white-50 fs-5"><i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 rounded-4" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255, 255, 255, 0.1);">
                    <p class="text-muted mb-0">No {{ $type }} lessons available for this level yet.</p>
                </div>
            </div>
        @endforelse
    </div>

</div>
@endsection