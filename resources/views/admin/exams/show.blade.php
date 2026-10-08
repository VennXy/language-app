@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div><h2 class="text-white fw-bold mb-1">{{ $exam->title }}</h2><span class="badge bg-{{ $exam->status === 'published' ? 'success' : ($exam->status === 'closed' ? 'secondary' : 'warning text-dark') }}">{{ ucfirst($exam->status) }}</span></div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-light" href="{{ route('super.admin.exams.index') }}">Back to Exams</a>
            @if($exam->attempts_count === 0)<a class="btn btn-outline-light" href="{{ route('super.admin.exams.edit', $exam) }}">Edit</a>@endif
            @if($exam->status === 'draft')
                <form method="POST" action="{{ route('super.admin.exams.publish', $exam) }}">@csrf<button class="btn btn-outline-success" type="submit">Publish</button></form>
            @elseif($exam->status === 'published')
                <form method="POST" action="{{ route('super.admin.exams.close', $exam) }}">@csrf<button class="btn btn-outline-warning" type="submit">Close Exam</button></form>
            @endif
        </div>
    </div>

    <div class="card glass-card border-0 p-4" style="max-width:900px">
        <dl class="row mb-0">
            <dt class="col-sm-4 text-white-50">Duration</dt><dd class="col-sm-8 text-white">{{ $exam->duration_minutes }} minutes</dd>
            <dt class="col-sm-4 text-white-50">Creator</dt><dd class="col-sm-8 text-white">{{ $exam->creator->name }}</dd>
            <dt class="col-sm-4 text-white-50">Approved questions</dt><dd class="col-sm-8 text-white">{{ $exam->approved_questions_count }}</dd>
            <dt class="col-sm-4 text-white-50">Total questions</dt><dd class="col-sm-8 text-white">{{ $exam->questions_count }}</dd>
            <dt class="col-sm-4 text-white-50">Created</dt><dd class="col-sm-8 text-white">{{ $exam->created_at->format('M j, Y g:i A') }}</dd>
            <dt class="col-sm-4 text-white-50">Attempt history</dt><dd class="col-sm-8 text-white">{{ $exam->attempts_count }}</dd>
        </dl>
    </div>
</div>
@endsection
