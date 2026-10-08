@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="text-white fw-bold mb-1">Exam Management</h2>
            <p class="text-white-50 mb-0">Create and manage exam definitions.</p>
        </div>
        <a href="{{ route('super.admin.exams.create') }}" class="btn text-white px-4 py-2" style="background:linear-gradient(135deg,#ff758c,#ff7eb3);border:0;border-radius:12px;font-weight:600">
            <i class="bi bi-plus-lg me-2"></i>Create Exam
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card glass-card border-0 p-3 p-md-4">
        <div class="table-responsive">
            <table class="table glass-table align-middle mb-0">
                <thead><tr>
                    <th>Title</th><th>Duration</th><th>Status</th><th>Approved</th><th>Created</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                @forelse($exams as $exam)
                    <tr>
                        <td class="fw-semibold">{{ $exam->title }}</td>
                        <td>{{ $exam->duration_minutes }} min</td>
                        <td><span class="badge bg-{{ $exam->status === 'published' ? 'success' : ($exam->status === 'closed' ? 'secondary' : 'warning text-dark') }}">{{ ucfirst($exam->status) }}</span></td>
                        <td>{{ $exam->approved_questions_count }}</td>
                        <td>{{ $exam->created_at->format('M j, Y') }}</td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-light" href="{{ route('super.admin.exams.show', $exam) }}">View</a>
                            @if($exam->attempts_count === 0)
                                <a class="btn btn-sm btn-outline-light" href="{{ route('super.admin.exams.edit', $exam) }}">Edit</a>
                            @endif
                            @if($exam->status === 'draft')
                                <form class="d-inline" method="POST" action="{{ route('super.admin.exams.publish', $exam) }}">
                                    @csrf<button class="btn btn-sm btn-outline-success" type="submit">Publish</button>
                                </form>
                                @if($exam->attempts_count === 0)
                                    <form class="d-inline" method="POST" action="{{ route('super.admin.exams.destroy', $exam) }}" onsubmit="return confirm('Delete this draft exam?')">
                                        @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                @endif
                            @elseif($exam->status === 'published')
                                <form class="d-inline" method="POST" action="{{ route('super.admin.exams.close', $exam) }}">
                                    @csrf<button class="btn btn-sm btn-outline-warning" type="submit">Close</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-white-50">No exams have been created.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $exams->links() }}</div>
    </div>
</div>
@endsection
