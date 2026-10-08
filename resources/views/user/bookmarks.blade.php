@section('student_shell', true)
@extends('layouts.app')
@push('styles')
<style>
/* Bookmarks: glass vocabulary cards */
.sk-dash main .bk-item{
  border-radius:18px;
  transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.sk-dash main .bk-item:hover{
  transform:translateY(-2px);
  border-color:rgba(192,132,252,.40) !important;
  box-shadow:0 16px 38px rgba(4,2,16,.5), 0 0 20px rgba(168,85,247,.16);
}
</style>
@endpush

@section('content')
<div class="container py-5 text-white" style="max-width: 900px; min-height: 65vh;">
    <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1">Bookmarked Questions</h1>
            <p class="text-light opacity-75 mb-0">Questions you saved for later.</p>
        </div>
        <a href="{{ route('select.level') }}" class="btn btn-outline-light rounded-pill">Back to learning</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        @forelse($items as $item)
            @php($vocabulary = $item['vocabulary'])
            <div class="col-12">
                <div class="bk-item p-4 rounded-4 bg-dark border border-secondary">
                    <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
                        <div>
                            <div class="small text-info mb-2">{{ $vocabulary->lesson?->title ?? 'Lesson removed' }}</div>
                            <h2 class="h5 fw-bold">{{ $vocabulary->question }}</h2>
                            <span class="small text-light opacity-75">{{ str_replace('_', ' ', $vocabulary->question_type) }}</span>
                        </div>
                        <form method="POST" action="{{ route('student.bookmarks.toggle', $vocabulary->id) }}">
                            @csrf
                            <button class="btn btn-outline-warning rounded-pill" type="submit">Remove bookmark</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-light opacity-75 py-5">You have no bookmarked questions yet.</div>
        @endforelse
    </div>
</div>
@endsection
