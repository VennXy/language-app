@extends('layouts.admin')

@section('admin_content')

<div class="container-fluid px-0">

    <!-- =========================================================
         HEADER
    ========================================================== -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="text-white fw-bold">
                Category (Topic) Management
            </h2>

            <p class="text-white-50 mb-0">
                Manage training and exam topics for SakuraLearn.
            </p>
        </div>

        <button type="button"
                class="btn text-white px-4 py-2 shadow-sm"
                style="
                    background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
                    border: none;
                    border-radius: 12px;
                    font-weight: 600;
                "
                data-bs-toggle="modal"
                data-bs-target="#addCategoryModal">

            <i class="bi bi-plus-lg me-2"></i>
            Add New Topic

        </button>

    </div>


    <!-- =========================================================
         SUCCESS MESSAGE
    ========================================================== -->
    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show glass-card border-0 text-white mb-4"
             role="alert"
             style="background: rgba(40, 167, 69, 0.2) !important;">

            <i class="bi bi-check-circle-fill me-2"></i>

            {{ session('success') }}

            <button type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="alert"
                    aria-label="Close">
            </button>

        </div>

    @endif


    <!-- =========================================================
         CATEGORIES TABLE
    ========================================================== -->
    <div class="card glass-card border-0 p-4"
         style="
             background: rgba(25, 20, 35, 0.4);
             backdrop-filter: blur(20px);
             -webkit-backdrop-filter: blur(20px);
             border-radius: 16px;
             border: 1px solid rgba(255, 255, 255, 0.08) !important;
         ">

        <div class="table-responsive">

            <table class="table glass-table align-middle mb-0"
                   style="background: transparent;">

                <thead>

                    <tr style="border-color: rgba(255, 255, 255, 0.08);">

                        <th class="py-3 px-3 text-white-50">
                            #
                        </th>

                        <th class="py-3 text-white-50">
                            Level
                        </th>

                        <th class="py-3 text-white-50">
                            Topic Name
                        </th>

                        <th class="py-3 text-white-50">
                            Description
                        </th>

                        <th class="py-3 text-end px-3 text-white-50">
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($categories as $index =>$category)

                        <tr style="border-color: rgba(255, 255, 255, 0.04);">

                            <!-- Number -->
                            <td class="px-3 text-white">
                                {{ $index + 1 }}
                            </td>


                            <!-- Level -->
                            <td>

                                <span class="badge px-3 py-2 fw-normal"
                                      style="
                                          background: rgba(255, 117, 140, 0.2);
                                          color: #ff7eb3;
                                          border: 1px solid rgba(255, 117, 140, 0.3);
                                          border-radius: 6px;
                                      ">

                                    {{ $category->level->name ?? 'N/A' }}

                                </span>

                            </td>


                            <!-- Topic Name -->
                            <td class="fw-semibold text-white">

                                {{ $category->title }}

                            </td>


                            <!-- Description -->
                            <td class="text-white-50">

                                {{ $category->description ?? '-' }}

                            </td>


                       <!-- Actions -->
<td class="text-end px-3">

    <!-- EDIT BUTTON (Glassmorphism + Purple Color) -->
    <button type="button"
            class="btn btn-sm px-3 me-1 edit-btn text-white shadow-sm"

            data-id="{{ $category->id }}"
            data-level_id="{{ $category->level_id }}"
            data-name="{{ $category->title }}"
            data-description="{{ $category->description }}"

            style="
                background: rgba(168, 85, 247, 0.4);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(168, 85, 247, 0.6);
                border-radius: 20px;
                font-weight: 500;
                padding-left: 14px;
                padding-right: 14px;
                transition: all 0.3s ease;
            "
            onmouseover="this.style.background='rgba(168, 85, 247, 0.7)'; this.style.transform='translateY(-1px)';"
            onmouseout="this.style.background='rgba(168, 85, 247, 0.4)'; this.style.transform='translateY(0)';"

            title="Edit Topic"

            data-bs-toggle="modal"
            data-bs-target="#editCategoryModal">

        <i class="bi bi-pencil-fill me-1"></i> Edit

    </button>


    <!-- DELETE BUTTON (Glassmorphism + Red Color) -->
    <form action="{{ route('super.admin.categories.destroy', $category->id) }}"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('Are you sure you want to delete this topic?');">

        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm px-3 text-white shadow-sm"
                style="
                    background: rgba(239, 68, 68, 0.3);
                    backdrop-filter: blur(10px);
                    -webkit-backdrop-filter: blur(10px);
                    border: 1px solid rgba(239, 68, 68, 0.6);
                    border-radius: 20px;
                    font-weight: 500;
                    padding-left: 14px;
                    padding-right: 14px;
                    transition: all 0.3s ease;
                "
                onmouseover="this.style.background='rgba(239, 68, 68, 0.7)'; this.style.transform='translateY(-1px)';"
                onmouseout="this.style.background='rgba(239, 68, 68, 0.3)'; this.style.transform='translateY(0)';"

                title="Delete Topic">

            <i class="bi bi-trash-fill me-1"></i> Delete

        </button>

    </form>

</td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5"
                                class="text-center py-5 text-white-50">

                                No categories found.
                                Please add a new topic.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>



<!-- =============================================================
     ADD CATEGORY MODAL
============================================================== -->

<div class="modal fade"
     id="addCategoryModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content glass-card border-0 text-white shadow-lg"
             style="
                 background: rgba(18, 13, 28, 0.95) !important;
                 backdrop-filter: blur(20px);
                 -webkit-backdrop-filter: blur(20px);
                 border-radius: 16px;
             ">

            <form action="{{ route('super.admin.categories.store') }}"
                  method="POST">

                @csrf


                <!-- Modal Header -->
                <div class="modal-header border-bottom border-light border-opacity-10">

                    <h5 class="modal-title fw-bold"
                        style="color: #f3a6c8;">

                        Add New Topic

                    </h5>

                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>


                <!-- Modal Body -->
                <div class="modal-body p-4">


                    <!-- LEVEL -->
                    <div class="mb-3">

                        <label for="level_id"
                               class="form-label text-white-50">

                            Select Level

                        </label>

                        <select class="form-select bg-transparent text-white border-light border-opacity-25 py-2"
                                id="level_id"
                                name="level_id"
                                required
                                style="border-radius: 8px;">

                            <option value=""
                                    selected
                                    disabled
                                    class="bg-dark">

                                Choose Level

                            </option>


                            @foreach($levels as $level)

                                <option value="{{ $level->id }}"
                                        class="bg-dark">

                                    {{ $level->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- TOPIC NAME -->
                    <div class="mb-3">

                        <label for="name"
                               class="form-label text-white-50">

                            Topic Name

                        </label>

                        <input type="text"
                               class="form-control bg-transparent text-white border-light border-opacity-25 py-2"
                               id="name"
                               name="name"
                               placeholder="e.g. Topic 1: Hiragana Basics"
                               required
                               style="border-radius: 8px;">

                    </div>


                    <!-- DESCRIPTION -->
                    <div class="mb-3">

                        <label for="description"
                               class="form-label text-white-50">

                            Description (Optional)

                        </label>

                        <textarea class="form-control bg-transparent text-white border-light border-opacity-25"
                                  id="description"
                                  name="description"
                                  rows="3"
                                  placeholder="Enter brief description..."
                                  style="border-radius: 8px;"></textarea>

                    </div>

                </div>


                <!-- Modal Footer -->
                <div class="modal-footer border-top border-light border-opacity-10">

                    <button type="button"
                            class="btn btn-secondary px-4"
                            data-bs-dismiss="modal"
                            style="
                                background: rgba(255,255,255,0.1);
                                border: none;
                                border-radius: 8px;
                            ">

                        Cancel

                    </button>


                    <button type="submit"
                            class="btn text-white px-4"
                            style="
                                background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
                                border: none;
                                border-radius: 8px;
                                font-weight: 600;
                            ">

                        Save Topic

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- =============================================================
     EDIT CATEGORY MODAL
============================================================== -->

<div class="modal fade"
     id="editCategoryModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content glass-card border-0 text-white shadow-lg"
             style="
                 background: rgba(18, 13, 28, 0.95) !important;
                 backdrop-filter: blur(20px);
                 -webkit-backdrop-filter: blur(20px);
                 border-radius: 16px;
             ">


            <form id="editCategoryForm"
                  method="POST">

                @csrf

                @method('PUT')


                <!-- Modal Header -->
                <div class="modal-header border-bottom border-light border-opacity-10">

                    <h5 class="modal-title fw-bold"
                        style="color: #f3a6c8;">

                        Edit Topic

                    </h5>


                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>

                </div>


                <!-- Modal Body -->
                <div class="modal-body p-4">


                    <!-- LEVEL -->
                    <div class="mb-3">

                        <label for="edit_level_id"
                               class="form-label text-white-50">

                            Select Level

                        </label>


                        <select class="form-select bg-transparent text-white border-light border-opacity-25 py-2"
                                id="edit_level_id"
                                name="level_id"
                                required
                                style="border-radius: 8px;">

                            <option value=""
                                    disabled
                                    class="bg-dark">

                                Choose Level

                            </option>


                            @foreach($levels as $level)

                                <option value="{{ $level->id }}"
                                        class="bg-dark">

                                    {{ $level->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    <!-- TOPIC NAME -->
                    <div class="mb-3">

                        <label for="edit_name"
                               class="form-label text-white-50">

                            Topic Name

                        </label>


                        <input type="text"
                               class="form-control bg-transparent text-white border-light border-opacity-25 py-2"
                               id="edit_name"
                               name="name"
                               required
                               style="border-radius: 8px;">

                    </div>


                    <!-- DESCRIPTION -->
                    <div class="mb-3">

                        <label for="edit_description"
                               class="form-label text-white-50">

                            Description (Optional)

                        </label>


                        <textarea class="form-control bg-transparent text-white border-light border-opacity-25"
                                  id="edit_description"
                                  name="description"
                                  rows="3"
                                  style="border-radius: 8px;"></textarea>

                    </div>

                </div>


                <!-- Modal Footer -->
                <div class="modal-footer border-top border-light border-opacity-10">

                    <button type="button"
                            class="btn btn-secondary px-4"
                            data-bs-dismiss="modal"
                            style="
                                background: rgba(255,255,255,0.1);
                                border: none;
                                border-radius: 8px;
                            ">

                        Cancel

                    </button>


                    <button type="submit"
                            class="btn text-white px-4"
                            style="
                                background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
                                border: none;
                                border-radius: 8px;
                                font-weight: 600;
                            ">

                        Update Topic

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- =============================================================
     JAVASCRIPT
     Edit Modal Data Binding
============================================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const editButtons = document.querySelectorAll('.edit-btn');


    editButtons.forEach(button => {

        button.addEventListener('click', function () {

            // Get category data
            const id = this.getAttribute('data-id');

            const levelId = this.getAttribute('data-level_id');

            const name = this.getAttribute('data-name');

            const description = this.getAttribute('data-description');


            // Get edit form
            const form = document.getElementById('editCategoryForm');


            // Set form action
            form.action = `/super-admin/categories/${id}`;


            // Set level
            document.getElementById('edit_level_id').value = levelId;


            // Set topic name
            document.getElementById('edit_name').value = name;


            // Set description
            document.getElementById('edit_description').value =
                description !== 'null'
                    ? description
                    : '';

        });

    });

});

</script>

@endsection