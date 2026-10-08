@extends('layouts.admin')

@section('admin_content')

<style>
    /* View Button Hover Effect */
    .btn-custom-view {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
    }
    .btn-custom-view:hover {
        background-color: rgba(255, 255, 255, 0.25) !important;
        border-color: rgba(255, 255, 255, 0.5) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
    }

    /* Edit Button Hover Effect */
    .btn-custom-edit {
        background: linear-gradient(135deg, #a855f7, #d946ef);
        transition: all 0.3s ease;
    }
    .btn-custom-edit:hover {
        background: linear-gradient(135deg, #9333ea, #c026d3) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(217, 70, 239, 0.4);
    }

    /* Delete Button Hover Effect */
    .btn-custom-delete {
        background: rgba(220, 53, 69, 0.2);
        transition: all 0.3s ease;
    }
    .btn-custom-delete:hover {
        background: rgba(220, 53, 69, 0.4) !important;
        border-color: #dc3545 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    /* Responsive table text padding reduction for small screens */
    @media (max-width: 768px) {
        .table th, .table td {
            padding: 0.5rem 0.3rem !important;
            font-size: 0.8rem;
        }
        .btn-sm {
            padding: 0.20rem 0.4rem;
            font-size: 0.7rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-4">
    <!-- Page Header & Add Button -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-white mb-1">Lesson Management</h2>
            <p class="text-white-50 mb-0">Manage training and exam lessons for SakuraLearn.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 py-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#addLessonModal" style="background: #f472b6; border: none; border-radius: 12px; font-weight: 600;">
            <i class="bi bi-plus-lg me-2"></i> Add New Lesson
        </button>
    </div>

    <!-- Success Message Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show glass-card border-0 text-white mb-4" role="alert" style="background: rgba(40, 167, 69, 0.2) !important;">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Lessons Table Card -->
    <div class="card glass-card border-0 p-2 p-md-4 shadow-lg" style="backdrop-filter: blur(10px); border-radius: 16px;">
        <div class="table-responsive w-100">
            <table class="table glass-table align-middle mb-0 text-white">
                <thead>
                    <tr class="text-nowrap">
                        <th>#</th>
                        <th>Title</th>
                        <th>Level</th>
                        <th>Type</th>
                        <th class="d-none d-md-table-cell">Score</th>
                        <th class="d-none d-md-table-cell">Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lessons as $index => $lesson)
                        <tr>
                            <td class="text-nowrap">{{ $lessons->firstItem() + $index }}</td>
                            <td class="fw-bold text-nowrap">{{ $lesson->title }}</td>
                            <td class="text-nowrap">
                                <span class="badge bg-secondary px-2 py-1" style="background: rgba(255,255,255,0.1) !important;">
                                    {{ $lesson->level->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <span class="badge {{ $lesson->type === 'training' ? 'bg-info' : 'bg-warning' }} text-dark px-2 py-1">
                                   {{ ucfirst($lesson->type) }}
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell text-nowrap">{{ $lesson->score }}</td>
                            <td class="d-none d-md-table-cell text-nowrap">{{ $lesson->sort_order }}</td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
  <a href="{{ route('super.admin.lessons.show', $lesson->id) }}" class="btn btn-sm btn-info text-white">
        <i class="bi bi-eye"></i> View Details
  </a> 

                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-sm rounded-pill px-3 py-1 text-white border-0 btn-custom-edit" 
                                            data-bs-toggle="modal" data-bs-target="#editLessonModal{{ $lesson->id }}">
                                        <i class="bi bi-pencil-fill me-1"></i> Edit
                                    </button>
<form action="{{ route('super.admin.lessons.destroy', $lesson->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this lesson?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm rounded-pill px-3 py-1 text-white border border-danger btn-custom-delete" title="Delete Lesson">
        <i class="bi bi-trash-fill"></i> Delete
    </button>
</form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-white-50">No lessons found. Please add a new lesson.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 d-flex justify-content-end overflow-auto">
            {{ $lessons->links() }}
        </div>
    </div>
</div>

<!-- Add Lesson Modal -->
<div class="modal fade" id="addLessonModal" tabindex="-1" aria-labelledby="addLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0 text-white" style="background: rgba(18, 13, 28, 0.95) !important; backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold" id="addLessonModalLabel" style="color: #f3a6c8;">Add New Lesson</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('super.admin.lessons.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">  
                        <!-- Title -->
                        <div class="col-12">
                            <label for="title" class="form-label">Lesson Title</label>
                            <input type="text" class="form-control bg-transparent text-white border-light border-opacity-25" id="title" name="title" required placeholder="Enter lesson title...">
                        </div>

                        <!-- Level -->
                        <div class="col-md-6">
                            <label for="level_id" class="form-label">Language Level</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" id="level_id" name="level_id" required>
                                <option value="" selected disabled class="bg-dark">Select Level</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" class="bg-dark">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Type -->
                        <!-- <div class="col-md-6">
                            <label for="type" class="form-label">Lesson Type</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" id="type" name="type" required>
                                <option value="training" class="bg-dark">Training</option>
                                <option value="exam" class="bg-dark">Exam</option>
                            </select>
                        </div> -->

                        <!-- Score -->
                        <div class="col-md-6">
                            <label for="score" class="form-label">Score</label>
                            <input type="number" class="form-control bg-transparent text-white border-light border-opacity-25" id="score" name="score" value="0">
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control bg-transparent text-white border-light border-opacity-25" id="sort_order" name="sort_order" value="0">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control bg-transparent text-white border-light border-opacity-25" id="description" name="description" rows="3" placeholder="Enter brief description..."></textarea>
                        </div>

                        <!-- Category / Topic Selection -->
                        <div class="col-12">
                            <label for="category_id" class="form-label">Category / Topic</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" id="category_id" name="category_id" required>
                                <option value="" selected disabled class="bg-dark">Select Category</option>
                                @foreach($categories as $index => $category)
                                    <option value="{{ $category->id }}" class="bg-dark">
                                        Topic {{ $index + 1 }}: {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border:none;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background: #f472b6; border:none; font-weight:600;">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View & Edit Lesson Modals Loop -->
@foreach($lessons as $lesson)
<!-- View Lesson Modal -->
<div class="modal fade" id="viewLessonModal{{ $lesson->id }}" tabindex="-1" aria-labelledby="viewLessonModalLabel{{ $lesson->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0 text-white" style="background: rgba(18, 13, 28, 0.95) !important; backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold" id="viewLessonModalLabel{{ $lesson->id }}" style="color: #f3a6c8;">
                    <i class="bi bi-info-circle me-2"></i> Lesson Details: {{ $lesson->title }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-white-50">Lesson Title</label>
                        <div class="p-2 rounded bg-dark border border-light border-opacity-15 fw-bold">{{ $lesson->title }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white-50">Language Level</label>
                        <div class="p-2 rounded bg-dark border border-light border-opacity-15">{{ $lesson->level->name ?? 'N/A' }}</div>
                    </div>
                    <!-- <div class="col-md-6">
                        <label class="form-label text-white-50">Lesson Type</label>
                        <div class="p-2 rounded bg-dark border border-light border-opacity-15 text-uppercase fw-semibold">{{ $lesson->type }}</div>
                    </div> -->
                    <div class="col-md-6">
                        <label class="form-label text-white-50">Score</label>
                        <div class="p-2 rounded bg-dark border border-light border-opacity-15">{{ $lesson->score }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white-50">Sort Order</label>
                        <div class="p-2 rounded bg-dark border border-light border-opacity-15">{{ $lesson->sort_order }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-white-50">Description</label>
                        <div class="p-3 rounded bg-dark border border-light border-opacity-15" style="min-height: 80px;">{{ $lesson->description ?: 'No description provided.' }}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border:none;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Lesson Modal -->
<div class="modal fade" id="editLessonModal{{ $lesson->id }}" tabindex="-1" aria-labelledby="editLessonModalLabel{{ $lesson->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass-card border-0 text-white" style="background: rgba(18, 13, 28, 0.95) !important; backdrop-filter: blur(20px);">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title fw-bold" id="editLessonModalLabel{{ $lesson->id }}" style="color: #f3a6c8;">Edit Lesson: {{ $lesson->title }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('super.admin.lessons.update', $lesson->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">  
                        <!-- Title -->
                        <!-- <div class="col-12">
                            <label class="form-label">Lesson Title</label>
                            <input type="text" class="form-control bg-transparent text-white border-light border-opacity-25" name="title" value="{{ $lesson->title }}" required>
                        </div> -->

                        <!-- Level -->
                        <div class="col-md-6">
                            <label class="form-label">Language Level</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" name="level_id" required>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" class="bg-dark" {{ $lesson->level_id == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>  
                        

                        <!-- Type -->
                        <div class="col-md-6">
                            <label class="form-label">Lesson Type</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" name="type" required>
                                <option value="training" class="bg-dark" {{ $lesson->type == 'training' ? 'selected' : '' }}>Training</option>
                                <option value="exam" class="bg-dark" {{ $lesson->type == 'exam' ? 'selected' : '' }}>Exam</option>
                            </select>
                        </div>

                        <!-- Score -->
                        <div class="col-md-6">
                            <label class="form-label">Score</label>
                            <input type="number" class="form-control bg-transparent text-white border-light border-opacity-25" name="score" value="{{ $lesson->score }}">
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control bg-transparent text-white border-light border-opacity-25" name="sort_order" value="{{ $lesson->sort_order }}">
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control bg-transparent text-white border-light border-opacity-25" name="description" rows="3">{{ $lesson->description }}</textarea>
                        </div>

                        <!-- Category / Topic Selection -->
                        <div class="col-12">
                            <label class="form-label">Category / Topic</label>
                            <select class="form-select bg-transparent text-white border-light border-opacity-25" name="category_id" required>
                                @foreach($categories as $catIndex => $category)
                                    <option value="{{ $category->id }}" class="bg-dark" {{ $lesson->category_id == $category->id ? 'selected' : '' }}>
                                        Topic {{ $catIndex + 1 }}: {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border:none;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" style="background: #f472b6; border:none; font-weight:600;">Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection