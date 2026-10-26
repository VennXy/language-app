@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="mb-3">
    <label class="form-label text-white" for="title">Title</label>
    <input class="form-control" id="title" name="title" type="text" maxlength="191" required value="{{ old('title', $exam?->title) }}">
</div>
<div class="mb-3">
    <label class="form-label text-white" for="duration_minutes">Duration (minutes)</label>
    <input class="form-control" id="duration_minutes" name="duration_minutes" type="number" min="1" max="65535" required value="{{ old('duration_minutes', $exam?->duration_minutes) }}">
</div>
<div class="mb-3">
    <label class="form-label text-white" for="level_id">Level</label>
    <select class="form-select" id="level_id" name="level_id" required>
        @foreach(\App\Models\Level::orderBy('id')->get(['id', 'level', 'name']) as $levelOption)
            <option value="{{ $levelOption->id }}" @selected((int) old('level_id', $exam?->level_id) === (int) $levelOption->id)>{{ $levelOption->level }} — {{ $levelOption->name }}</option>
        @endforeach
    </select>
    <div class="form-text text-white-50">Each exam belongs to exactly one level.</div>
</div>
<div class="mb-4">
    <label class="form-label text-white" for="status">Status</label>
    <select class="form-select" id="status" name="status" required>
        @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $exam?->status ?? 'draft') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text text-white-50">Publishing requires exactly 25 approved questions.</div>
</div>
<button class="btn text-white px-4" type="submit" style="background:#f472b6;border:0">Save Exam</button>
<a class="btn btn-outline-light ms-2" href="{{ $exam ? route('super.admin.exams.show', $exam) : route('super.admin.exams.index') }}">Cancel</a>
