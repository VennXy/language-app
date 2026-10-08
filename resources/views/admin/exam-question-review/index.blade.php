@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h2 class="text-white fw-bold mb-1">{{ $exam->title }}</h2>
            <p class="text-white-50 mb-0">Exam status: {{ ucfirst($exam->status) }}</p>
        </div>
        <a class="btn btn-outline-light" href="{{ route('super.admin.exam.questions.exams') }}">All Exams</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3 mb-4">
        @foreach(['Total' => 'total_questions_count', 'Draft' => 'draft_questions_count', 'Pending' => 'pending_questions_count', 'Approved' => 'approved_questions_count', 'Rejected' => 'rejected_questions_count'] as $label => $countField)
            <div class="col-6 col-md"><div class="card glass-card border-0 p-3 text-white h-100"><div class="text-white-50 small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $exam->{$countField} }}</div></div></div>
        @endforeach
    </div>

    <h4 class="text-white fw-semibold mb-3">Pending Questions</h4>
    @forelse($questions as $question)
        <div class="card glass-card border-0 p-4 mb-3 text-white">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div><div class="text-white-50 small">Question #{{ $question->id }}</div><h5 class="fw-bold mb-0">{{ $question->question }}</h5></div>
                <span class="badge bg-info text-dark">Pending</span>
            </div>
            <ol type="A" class="mb-3">
                @foreach(['a', 'b', 'c', 'd'] as $option)
                    <li @if(strtolower($question->correct_option) === $option) class="fw-bold text-info" @endif>{{ $question->{'option_'.$option} }}</li>
                @endforeach
            </ol>
            <div class="row text-white-50 small mb-3">
                <div class="col-md-4">Correct option: <strong class="text-white">{{ strtoupper($question->correct_option) }}</strong></div>
                <div class="col-md-4">Owner: <strong class="text-white">{{ $question->owner->name }} ({{ $question->owner->email }})</strong></div>
                <div class="col-md-4">Submitted: <strong class="text-white">{{ $question->submitted_at?->format('M j, Y g:i A') ?? '—' }}</strong></div>
            </div>
            <div class="d-flex gap-3 align-items-start flex-wrap border-top border-light border-opacity-10 pt-3">
                <form method="POST" action="{{ route('super.admin.exam.questions.approve', $question) }}">
                    @csrf<button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                </form>
                <form method="POST" action="{{ route('super.admin.exam.questions.reject', $question) }}" class="d-flex flex-grow-1 gap-2 align-items-start flex-wrap">
                    @csrf
                    <div class="flex-grow-1" style="min-width:240px">
                        <label class="visually-hidden" for="reason-{{ $question->id }}">Rejection reason</label>
                        <textarea class="form-control form-control-sm" id="reason-{{ $question->id }}" name="rejection_reason" rows="2" maxlength="2000" placeholder="Required rejection reason" required>{{ old('rejection_reason') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card glass-card border-0 p-4 text-white-50">There are no pending questions for this exam.</div>
    @endforelse
</div>
@endsection
