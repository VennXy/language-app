@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid py-3 text-white">
    <div class="mb-4">
        <h2 class="fw-bold">Training Management</h2>
        <p class="text-white-50 mb-0">Choose a level, select a lesson, and manage its learning cards and linked practice questions.</p>
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
        <div class="card bg-dark border-secondary text-white mb-4">
            <div class="card-header fw-semibold">Create a lesson in {{ $selectedLevel->level }}</div>
            <div class="card-body">
                @if($categories->isEmpty())
                    <p class="text-warning mb-0">This level has no category yet. Create a category first in Category Management.</p>
                @else
                    <form method="POST" action="{{ route('super.admin.training.lessons.store', $selectedLevel) }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5"><label class="form-label">Lesson title</label><input name="title" class="form-control bg-black text-white border-secondary" value="{{ old('title') }}" required maxlength="255"></div>
                        <div class="col-md-3"><label class="form-label">Category</label><select name="category_id" class="form-select bg-black text-white border-secondary" required><option value="">Choose category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->title }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Lesson order</label><input name="sort_order" type="number" min="0" class="form-control bg-black text-white border-secondary" value="{{ old('sort_order', ($lessons->max('sort_order') ?? 0) + 1) }}" required></div>
                        <div class="col-md-2"><button class="btn btn-outline-light w-100" type="submit">Create Lesson</button></div>
                        <div class="col-12"><label class="form-label">Description <span class="text-white-50">(optional)</span></label><textarea name="description" class="form-control bg-black text-white border-secondary" rows="2">{{ old('description') }}</textarea></div>
                    </form>
                @endif
            </div>
        </div>

        <h3 class="h5 fw-bold mb-3">{{ $selectedLevel->level }} Lessons</h3>
        <div class="row g-3">
            @forelse($lessons as $lesson)
                <div class="col-12 col-xl-6">
                    <div class="card bg-dark border-secondary text-white h-100">
                        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <div>
                                <div class="fw-semibold">{{ $lesson->title }}</div>
                                <div class="small text-white-50">Order {{ $lesson->sort_order }} · {{ $lesson->category?->title ?? 'No category' }} · {{ $itemCounts->get($lesson->id, 0) }} learning items · {{ $lesson->vocabularies_count ?? $lesson->vocabularies()->count() }} questions</div>
                            </div>
                            <a href="{{ route('super.admin.training.lessons.show', $lesson) }}" class="btn btn-outline-light btn-sm">Manage Content</a>
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
