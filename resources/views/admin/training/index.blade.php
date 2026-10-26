@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid py-3 text-white">
    <div class="mb-4">
        <h2 class="fw-bold">Vocabulary Management</h2>
        <p class="text-white-50 mb-0">Choose a level and manage the vocabulary items for each of its lessons.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('super.admin.training.index') }}" class="row g-3 align-items-end mb-4">
        <div class="col-sm-6 col-lg-4">
            <label for="level_id" class="form-label">Level</label>
            <select id="level_id" name="level_id" class="form-select bg-dark text-white border-secondary" onchange="this.form.submit()">
                @foreach($levels as $level)
                    <option value="{{ $level->id }}" @selected($selectedLevel?->id === $level->id)>{{ $level->level }} — {{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <noscript><div class="col-auto"><button class="btn btn-outline-light">Select Level</button></div></noscript>
    </form>

    @if($selectedLevel)
        <h3 class="h5 fw-bold mb-3">{{ $selectedLevel->level }} Lessons</h3>
        <div class="row g-3">
            @forelse($lessons as $lesson)
                <div class="col-12 col-xl-6">
                    <div class="card bg-dark border-secondary text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <div>
                                <div class="fw-semibold">{{ $lesson->title }}</div>
                                <div class="small text-white-50">Order {{ $lesson->sort_order }} · {{ $lesson->category?->title ?? 'No category' }} · {{ $itemCounts->get($lesson->id, 0) }} vocabulary items</div>
                            </div>
                            <a href="{{ route('super.admin.training.lessons.show', $lesson) }}" class="btn btn-outline-light btn-sm">Manage Vocabulary</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="card bg-dark border-secondary p-4 text-white-50">No lessons exist for this level yet.</div></div>
            @endforelse
        </div>
    @else
        <div class="alert alert-warning">No A1–C2 levels were found. Existing level records are required before lessons can be managed.</div>
    @endif
</div>
@endsection
