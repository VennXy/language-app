@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1">{{ $exam->title }}</h2>
            <p class="text-white-50 mb-0">Exam status: {{ ucfirst($exam->status) }} · My Questions: {{ $questionCount }} / 5</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="{{ route('admin.exams.index') }}">All Available Exams</a>
            @if($exam->status === 'draft' && $questionCount < 5)
                <a class="btn text-white" href="{{ route('admin.exams.questions.create', $exam) }}" style="background:#f472b6;border:0">Add Question</a>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @forelse($questions as $index => $question)
        <div class="card glass-card border-0 p-4 mb-3 text-white">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="text-white-50 small mb-1">Question {{ $index + 1 }}</div>
                    <h5 class="fw-semibold">{{ $question->question }}</h5>
                </div>
                <span class="badge bg-{{ $question->status === 'approved' ? 'success' : ($question->status === 'rejected' ? 'danger' : ($question->status === 'pending' ? 'info text-dark' : 'secondary')) }}">{{ ucfirst($question->status) }}</span>
            </div>
            <ol type="A" class="mb-2">
                @foreach(['a', 'b', 'c', 'd'] as $option)
                    <li @if(strtolower($question->correct_option) === $option) class="fw-bold text-info" @endif>{{ $question->{'option_'.$option} }}</li>
                @endforeach
            </ol>
            <p class="text-white-50 mb-2">Correct: {{ strtoupper($question->correct_option) }}</p>
            @if($question->status === 'rejected' && $question->rejection_reason)
                <div class="alert alert-danger py-2 mb-3"><strong>Review feedback:</strong> {{ $question->rejection_reason }}</div>
            @endif
            @if(in_array($question->status, ['draft', 'rejected'], true) && $exam->status === 'draft')
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-sm btn-outline-light" href="{{ route('admin.exams.questions.edit', $question) }}">Edit</a>
                    @if($question->status === 'draft')
                        <form method="POST" action="{{ route('admin.exams.questions.submit', $question) }}">
                            @csrf<button class="btn btn-sm btn-outline-info" type="submit">Submit for Review</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.exams.questions.destroy', $question) }}" onsubmit="return confirm('Delete this question?')">
                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="card glass-card border-0 p-4 text-white-50">You have not added any questions to this exam.</div>
    @endforelse
</div>
@endsection
