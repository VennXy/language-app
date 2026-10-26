@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="text-white fw-bold mb-1">Exam Questions</h2>
        <p class="text-white-50 mb-0">Choose a draft exam to contribute questions.</p>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row g-3">
        @forelse($exams as $exam)
            <div class="col-12 col-lg-6 col-xl-4">
                <div class="card glass-card border-0 h-100 p-4 text-white">
                    <div class="d-flex justify-content-between gap-2 mb-3">
                        <h5 class="fw-bold mb-0">{{ $exam->title }}</h5>
                        <span class="badge bg-warning text-dark align-self-start">Draft</span>
                    </div>
                    <p class="text-white-50 mb-1">Duration: {{ $exam->duration_minutes }} minutes</p>
                    <p class="text-white-50 mb-4">My questions: {{ $exam->my_questions_count }}</p>
                    <div class="mt-auto">
                        <a class="btn btn-outline-light" href="{{ route('admin.exams.questions.create', $exam) }}">
                            Add Question
                        </a>
                        <a class="btn btn-link text-white-50" href="{{ route('admin.exams.questions.index', $exam) }}">My Questions</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="card glass-card border-0 p-4 text-white-50">There are no draft exams available for question contributions.</div></div>
        @endforelse
    </div>
</div>
@endsection
