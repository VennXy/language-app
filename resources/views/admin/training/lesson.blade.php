@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid py-3 text-white">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
        <div>
            <a class="text-white-50" href="{{ route('super.admin.training.index', ['level_id' => $lesson->level_id]) }}">{{ $lesson->level->level }} Vocabulary Management</a>
            <h2 class="fw-bold mt-2">{{ $lesson->title }}</h2>
            <p class="text-white-50 mb-0">Add and manage this lesson's vocabulary items: word, reading, meaning, media, and display order.</p>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card bg-dark border-secondary text-white mb-4">
        <div class="card-header fw-semibold">Add vocabulary / learning item</div>
        <div class="card-body">
            <form method="POST" action="{{ route('super.admin.training.items.store', $lesson) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4"><label class="form-label">Japanese (hiragana / kana)</label><input name="japanese" class="form-control bg-black text-white border-secondary" value="{{ old('japanese') }}" required></div>
                <div class="col-md-4"><label class="form-label">Reading</label><input name="reading" class="form-control bg-black text-white border-secondary" value="{{ old('reading') }}" required></div>
                <div class="col-md-4"><label class="form-label">English meaning</label><input name="english" class="form-control bg-black text-white border-secondary" value="{{ old('english') }}" required></div>
                <div class="col-md-4"><label class="form-label">Image (optional)</label><input type="file" name="image" accept="image/*" class="form-control bg-black text-white border-secondary"></div>
                <div class="col-md-4"><label class="form-label">Audio (optional)</label><input type="file" name="audio" accept="audio/*" class="form-control bg-black text-white border-secondary"></div>
                <div class="col-md-2"><label class="form-label">Display order</label><input type="number" min="0" name="sort_order" class="form-control bg-black text-white border-secondary" value="{{ old('sort_order', ($items->max('sort_order') ?? 0) + 1) }}" required></div>
                <div class="col-md-2"><button type="submit" class="btn btn-outline-light w-100">Add Item</button></div>
            </form>
        </div>
    </div>

    <h3 class="h5 fw-bold mb-3">Vocabulary / Learning Items</h3>
    <div class="d-grid gap-3 mb-5">
        @forelse($items as $item)
            <div class="card bg-dark border-secondary text-white"><div class="card-body">
                <form method="POST" action="{{ route('super.admin.training.items.update', [$lesson, $item->id]) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf @method('PUT')
                    <div class="col-md-3"><label class="form-label">Japanese</label><input name="japanese" class="form-control bg-black text-white border-secondary" value="{{ $item->japanese }}" required></div>
                    <div class="col-md-3"><label class="form-label">Reading</label><input name="reading" class="form-control bg-black text-white border-secondary" value="{{ $item->reading }}" required></div>
                    <div class="col-md-3"><label class="form-label">English</label><input name="english" class="form-control bg-black text-white border-secondary" value="{{ $item->english }}" required></div>
                    <div class="col-md-1"><label class="form-label">Order</label><input type="number" min="0" name="sort_order" class="form-control bg-black text-white border-secondary" value="{{ $item->sort_order }}" required></div>
                    <div class="col-md-2"><button class="btn btn-outline-light w-100">Save</button></div>
                    <div class="col-md-5"><label class="form-label">Replace image</label><input type="file" name="image" accept="image/*" class="form-control bg-black text-white border-secondary">@if($item->image_path)<a class="small text-info" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->image_path) }}" target="_blank" rel="noopener">View current image</a>@endif</div>
                    <div class="col-md-5"><label class="form-label">Replace audio</label><input type="file" name="audio" accept="audio/*" class="form-control bg-black text-white border-secondary">@if($item->audio_path)<a class="small text-info" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->audio_path) }}" target="_blank" rel="noopener">Play current audio</a>@endif</div>
                </form>
                <form method="POST" action="{{ route('super.admin.training.items.destroy', [$lesson, $item->id]) }}" class="mt-2" onsubmit="return confirm('Remove this learning item and its links? Existing question records will be kept.');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">Delete Item</button></form>
            </div></div>
        @empty
            <div class="card bg-dark border-secondary p-3 text-white-50">No learning items in this lesson yet.</div>
        @endforelse
    </div>

</div>
@endsection
