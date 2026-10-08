@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="text-white fw-bold mb-1">Exam Question Review</h2>
        <p class="text-white-50 mb-0">Review submitted questions and their current status.</p>
    </div>
    <div class="card glass-card border-0 p-3 p-md-4">
        <div class="table-responsive">
            <table class="table glass-table align-middle mb-0">
                <thead><tr><th>Exam</th><th>Status</th><th>Total</th><th>Draft</th><th>Pending</th><th>Approved</th><th>Rejected</th><th></th></tr></thead>
                <tbody>
                @forelse($exams as $exam)
                    <tr>
                        <td class="fw-semibold">{{ $exam->title }}</td>
                        <td>{{ ucfirst($exam->status) }}</td>
                        <td>{{ $exam->total_questions_count }}</td>
                        <td>{{ $exam->draft_questions_count }}</td>
                        <td>{{ $exam->pending_questions_count }}</td>
                        <td>{{ $exam->approved_questions_count }}</td>
                        <td>{{ $exam->rejected_questions_count }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-light" href="{{ route('super.admin.exam.questions.index', $exam) }}">Review Questions</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-white-50">No exams are available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $exams->links() }}</div>
    </div>
</div>
@endsection
