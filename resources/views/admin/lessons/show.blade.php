@extends('layouts.admin')

@section('admin_content')

<div class="container-fluid px-4 text-white">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">

        <h1>
            Lesson Detail: {{ $lesson->title }}
        </h1>

        <a href="{{ route('super.admin.lessons.index') }}"
           class="btn btn-secondary">
            Back to List
        </a>

    </div>


    <!-- Success Message -->
    @if (session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>

    @endif


    <!-- Validation Errors -->
    @if ($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <!-- Lesson Information -->
    <div class="card mb-4 bg-dark text-white border-secondary">

        <div class="card-header">

            <i class="bi bi-info-circle"></i>
            Lesson Information

        </div>


        <div class="card-body">

            <p>
                <strong>Title:</strong>
                {{ $lesson->title }}
            </p>

            <p>
                <strong>Level:</strong>
                {{ $lesson->level->name ?? 'N/A' }}
            </p>

            <p>
                <strong>Category:</strong>
                {{ $lesson->category->name ?? 'N/A' }}
            </p>

            <p>
                <strong>Score:</strong>
                {{ $lesson->score ?? 0 }}
            </p>

            <p>
                <strong>Sort Order:</strong>
                {{ $lesson->sort_order ?? 0 }}
            </p>

            <p>
                <strong>Description:</strong>
                {{ $lesson->description ?? 'No description' }}
            </p>

        </div>

    </div>


    <!-- Training Questions -->
    <div class="card mb-4 bg-dark text-white border-secondary">

        <div class="card-header">

            <i class="bi bi-journal-text"></i>
            Add & Manage Training Questions (Duolingo Style)

        </div>


        <div class="card-body">


          <!-- Add Question Form -->
            <form
                action="{{ route('super.admin.lessons.vocabularies.store', $lesson->id) }}"
                method="POST"
                enctype="multipart/form-data"
                class="mb-4"
                id="questionForm">

                @csrf

                <div class="row">

                    <!-- Question Type -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label text-warning fw-bold">
                            Question Type (Duolingo Style)
                        </label>
                        <select
                            name="question_type"
                            id="questionTypeSelect"
                            class="form-control bg-dark text-white border-secondary"
                            required>
                            <option value="audio">1. Audio Listenin</option>
                            <option value="word_selection">2. Word Selection</option>
                            <option value="phrase_ordering">3. Phrase Ordering</option>
                            <option value="image_selection">4. Image Selection</option>
                            <option value="translation_tiles">5. Japanese Prompt → English Word Tiles</option>
                        </select>
                        <small id="translationTilesHint" class="form-text text-info d-none">For this type, enter the Japanese prompt as the question, comma-separated English word tiles as options, and the correctly ordered English sentence as the correct answer.</small>
                    </div>

                    <!-- Hidden Options for JSON data -->
                    <input type="hidden" name="options" id="combinedOptions">

                    <!-- Question -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-warning fw-bold">
                            Question / Instruction
                        </label>
                        <textarea
                            name="question"
                            class="form-control bg-dark text-white border-secondary"
                            rows="2"
                            placeholder="e.g. Which one of these is games?"
                            required></textarea>
                    </div>

                    <!-- Correct Answer -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-warning fw-bold">
                            Correct Answer
                        </label>
                        <input
                            type="text"
                            name="correct_answer"
                            class="form-control bg-dark text-white border-secondary"
                            placeholder="correct answer (eg. ゲーム)"
                            required>
                    </div>

                    <!-- Audio File Container -->
                    <div class="col-md-12 mb-3" id="audioContainer">
                        <label class="form-label text-warning">
                            Audio File (MP3/WAV)
                        </label>
                        <input
                            type="file"
                            name="audio"
                            class="form-control bg-dark text-white border-secondary"
                            accept="audio/*">
                    </div>

                    <!-- Dynamic Options Container (Standard / Phrase Ordering) -->
                    <div class="col-md-12 mb-3" id="dynamicOptionsWrapper">
                        <label class="form-label text-warning fw-bold">
                            Answer Options 
                        </label>
                        <div id="optionsInputsContainer">
                            <div class="input-group mb-2 option-row">
                                <span class="input-group-text bg-secondary text-white">Option 1</span>
                                <input type="text" class="form-control bg-dark text-white border-secondary option-input" placeholder="Enter option text">
                            </div>
                        </div>
                        <button type="button" id="addOptionBtn" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-plus-lg"></i> Add More Option (+)
                        </button>
                    </div>

                    <!-- Image Selection Options Container (Duolingo 3-Image Style with Text) -->
                    <div class="col-md-12 mb-3 d-none" id="imageSelectionWrapper">
                        <label class="form-label text-warning fw-bold">
                            Image Options & Japanese Text 
                        </label>
                        <div id="imageOptionsInputsContainer">
                            <!-- Default 3 Options for Duolingo Style -->
                            <div class="row g-2 mb-2 image-option-row align-items-center bg-secondary bg-opacity-10 p-2 rounded border border-secondary">
                                <div class="col-md-5">
                                    <label class="form-label text-white small">Option Image</label>
                                    <input type="file" name="option_images[]" class="form-control bg-dark text-white border-secondary img-file-input" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white small">Japanese Text</label>
                                    <input type="text" class="form-control bg-dark text-white border-secondary img-text-input" placeholder="ဥပမာ - ゲーム">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger w-100 remove-img-row">X</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="addImageOptionBtn" class="btn btn-sm btn-success mt-2">
                            <i class="bi bi-plus-lg"></i> Add More Image Option (+)
                        </button>
                    </div>

                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary mt-3">
                    Add Training Question
                </button>

            </form>


            <!-- BULK IMPORT SECTION -->
            <hr class="border-secondary my-4">

            <div class="p-4 bg-secondary bg-opacity-15 rounded-4 border border-secondary mb-4">
                <h5 class="text-warning mb-3">
                    <i class="bi bi-file-earmark-arrow-up"></i> Bulk Import Questions (JSON / CSV)
                </h5>
                <p class="text-muted small">
                    If you wish to submit questions in bulk, please use a JSON or CSV file.
                </p>

                <form action="{{ route('super.admin.lessons.vocabularies.import', $lesson->id) }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                    @csrf
                    <div class="col-md-8">
                        <input type="file" name="import_file" class="form-control bg-dark text-white border-secondary" required accept=".json,.csv">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100">
                            Upload & Import Data
                        </button>
                    </div>
                </form>
            </div>


            <hr class="border-secondary">


            <!-- Questions List -->
            <h5 class="mb-3">
                Training Questions List
            </h5>


            <div class="table-responsive">

                <table class="table table-dark table-striped align-middle">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Type</th>
                            <th>Image</th>
                            <th>Question</th>
                            <th>Correct Answer</th>
                            <th>Options</th>
                            <th>Audio</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>


                        @forelse ($lesson->vocabularies as $index => $vocabulary)

                            <tr>

                                <!-- Number -->
                                <td>
                                    {{ $index + 1 }}
                                </td>


                                <!-- Type -->
                                <td>

                                    <span class="badge bg-info text-dark">

                                        {{ $vocabulary->question_type ?? 'word_selection' }}

                                    </span>

                                </td>


                                <!-- Image Column Added -->
                                <td>
                                    @if ($vocabulary->image_url)
                                        <img src="{{ asset('storage/' . $vocabulary->image_url) }}" alt="Img" style="width: 45px; height: 45px; object-fit: cover; border-radius: 5px;">
                                    @else
                                        <span class="text-muted">No Img</span>
                                    @endif
                                </td>


                                <!-- Question -->
                                <td>

                                    {{ $vocabulary->question ?? '-' }}

                                </td>


                                <!-- Correct Answer -->
                                <td>

                                    {{ $vocabulary->correct_answer ?? '-' }}

                                </td>


                                <!-- Options -->
                                <td>

                                    <small class="text-info">

                                        {{ $vocabulary->options ?? '-' }}

                                    </small>

                                </td>


                                <!-- Audio -->
                                <td>

                                    @if ($vocabulary->audio)

                                        <audio
                                            controls
                                            style="height: 30px; width: 120px;">

                                            <source
                                                src="{{ asset('storage/' . $vocabulary->audio) }}"
                                                type="audio/mpeg">

                                            Your browser does not support the audio element.

                                        </audio>

                                    @else

                                        <span class="text-muted">
                                            No Audio
                                        </span>

                                    @endif

                                </td>


                                <!-- Action -->
                                <td>


                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editVocabularyModal{{ $vocabulary->id }}">

                                        Edit

                                    </button>


                                    <!-- Delete -->
                                    <form
                                        action="{{ route('super.admin.vocabularies.destroy', $vocabulary->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure?');">

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm">

                                            Delete

                                        </button>

                                    </form>


                                </td>

                            </tr>


     
<!-- Edit Modal -->

<!-- Edit Modal -->
<!-- Edit Modal -->
<div class="modal fade text-dark"
     id="editVocabularyModal{{ $vocabulary->id }}"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">

            <form action="{{ route('super.admin.vocabularies.update', $vocabulary->id) }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Modal Header -->
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">
                        Edit Training Question
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">

                    <!-- Question Type (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label text-warning">
                            Question Type
                        </label>
                        <input type="text"
                               value="{{ $vocabulary->question_type }}"
                               class="form-control bg-secondary text-white border-secondary"
                               readonly>
                        <input type="hidden"
                               name="question_type"
                               value="{{ $vocabulary->question_type }}">
                    </div>

                    <!-- Question / Instruction -->
                    <div class="mb-3">
                        <label class="form-label text-warning">
                            Question / Instruction
                        </label>
                        <textarea name="question"
                                  class="form-control bg-dark text-white border-secondary"
                                  rows="2" required>{{ old('question', $vocabulary->question) }}</textarea>
                    </div>

                    <!-- Correct Answer -->
                    <div class="mb-3">
                        <label class="form-label text-warning">
                            Correct Answer
                        </label>
                        <input type="text"
                               name="correct_answer"
                               value="{{ old('correct_answer', $vocabulary->correct_answer) }}"
                               class="form-control bg-dark text-white border-secondary" required>
                    </div>

                    <!-- Image Path Container-->
                    @if($vocabulary->question_type == 'image_selection' || $vocabulary->question_type == 'word_selection' || $vocabulary->question_type == 'audio')
                        <div class="mb-3">
                            <label class="form-label text-warning">
                                Image Path / URL
                            </label>
                            <input type="text"
                                   name="image_url"
                                   value="{{ old('image_url', $vocabulary->image_url) }}"
                                   class="form-control bg-dark text-white border-secondary">
                        </div>
                    @endif

                    <!-- Options Container () -->
                    @if($vocabulary->question_type != 'audio')
                        <div class="mb-3">
                            <label class="form-label text-warning">
                                Options (Comma separated)
                            </label>
                            <input type="text"
                                   name="options"
                                   value="{{ old('options', $vocabulary->options) }}"
                                   class="form-control bg-dark text-white border-secondary">
                        </div>
                    @endif

                    <!-- Audio File Container -->
                    @if($vocabulary->question_type == 'audio')
                        <div class="mb-3">
                            <label class="form-label text-warning">
                                Audio File (Replace)
                            </label>
                            <input type="file"
                                   name="audio"
                                   class="form-control bg-dark text-white border-secondary"
                                   accept="audio/*">
                        </div>
                    @endif

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer border-secondary">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit"
                            class="btn btn-primary">
                        Update Question
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>


                        @empty

                            <tr>

                                <td
                                    colspan="8"
                                    class="text-center">

                                    No training questions found for this lesson.

                                </td>

                            </tr>

                        @endforelse


                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- JavaScript -->
<script>
   function handleQuestionTypeChange() {
    const selectedType = questionTypeSelect.value;

    if (selectedType === 'audio') {
        audioContainer.style.display = 'block';
        imageContainer.style.display = 'none'; // Audio မှာ ပုံမလိုပါ (ဒီနေရာကို ပြင်ရန်)
        dynamicOptionsWrapper.style.display = 'block';
    }
    else if (selectedType === 'word_selection') {
        audioContainer.style.display = 'none';
        imageContainer.style.display = 'none';
        dynamicOptionsWrapper.style.display = 'block';
    }
    else if (selectedType === 'phrase_ordering') {
        audioContainer.style.display = 'none';
        imageContainer.style.display = 'none';
        dynamicOptionsWrapper.style.display = 'block';
    }
    else if (selectedType === 'image_selection') {
        audioContainer.style.display = 'none'; // Image Selection မှာ အသံမလိုပါ
        imageContainer.style.display = 'block'; // ပုံတင်ရန် ပေါ်မည်
        dynamicOptionsWrapper.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const questionTypeSelect = document.getElementById('questionTypeSelect');
    const audioContainer = document.getElementById('audioContainer');
    const dynamicOptionsWrapper = document.getElementById('dynamicOptionsWrapper');
    const imageSelectionWrapper = document.getElementById('imageSelectionWrapper');
    
    const optionsInputsContainer = document.getElementById('optionsInputsContainer');
    const addOptionBtn = document.getElementById('addOptionBtn');

    const imageOptionsInputsContainer = document.getElementById('imageOptionsInputsContainer');
    const addImageOptionBtn = document.getElementById('addImageOptionBtn');

    const questionForm = document.getElementById('questionForm');
    const combinedOptions = document.getElementById('combinedOptions');

    // Question Type ပြောင်းလဲသည့်အခါ Fields များပေါ်/ပျောက်လုပ်ခြင်း
    function handleQuestionTypeChange() {
        const selectedType = questionTypeSelect.value;

        audioContainer.style.display = 'none';
        dynamicOptionsWrapper.style.display = 'none';
        imageSelectionWrapper.classList.add('d-none');
        document.getElementById('translationTilesHint').classList.add('d-none');

        if (selectedType === 'audio') {
            audioContainer.style.display = 'block';
            dynamicOptionsWrapper.style.display = 'block';
        } else if (selectedType === 'word_selection' || selectedType === 'phrase_ordering' || selectedType === 'translation_tiles') {
            dynamicOptionsWrapper.style.display = 'block';
            document.getElementById('translationTilesHint').classList.toggle('d-none', selectedType !== 'translation_tiles');
        } else if (selectedType === 'image_selection') {
            imageSelectionWrapper.classList.remove('d-none');
        }
    }

    questionTypeSelect.addEventListener('change', handleQuestionTypeChange);
    handleQuestionTypeChange();

    // Standard Option ထပ်ထည့်ရန်
    addOptionBtn.addEventListener('click', function () {
        const optionCount = optionsInputsContainer.querySelectorAll('.option-row').length + 1;
        const newRow = document.createElement('div');
        newRow.classList.add('input-group', 'mb-2', 'option-row');
        newRow.innerHTML = `
            <span class="input-group-text bg-secondary text-white">Option ${optionCount}</span>
            <input type="text" class="form-control bg-dark text-white border-secondary option-input" placeholder="Enter option text">
            <button type="button" class="btn btn-outline-danger remove-option-btn">X</button>
        `;
        optionsInputsContainer.appendChild(newRow);

        newRow.querySelector('.remove-option-btn').addEventListener('click', function () {
            newRow.remove();
            updateOptionLabels();
        });
    }); 

    function updateOptionLabels() {
        const rows = optionsInputsContainer.querySelectorAll('.option-row');
        rows.forEach(function (row, index) {
            row.querySelector('.input-group-text').textContent = `Option ${index + 1}`;
        });
    }
   
    addImageOptionBtn.addEventListener('click', function () {
        const newRow = document.createElement('div');
        newRow.classList.add('row', 'g-2', 'mb-2', 'image-option-row', 'align-items-center', 'bg-secondary', 'bg-opacity-10', 'p-2', 'rounded', 'border', 'border-secondary');
        newRow.innerHTML = `
            <div class="col-md-5">
                <input type="file" name="option_images[]" class="form-control bg-dark text-white border-secondary img-file-input" accept="image/*">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control bg-dark text-white border-secondary img-text-input" placeholder="ဂျပန်စာထည့်ရန်">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100 remove-img-row">X</button>
            </div>
        `;
        imageOptionsInputsContainer.appendChild(newRow);

        newRow.querySelector('.remove-img-row').addEventListener('click', function () {
            newRow.remove();
        });
    });

    questionForm.addEventListener('submit', function (e) {
        const selectedType = questionTypeSelect.value;

        if (selectedType === 'image_selection') {
            const imgRows = imageOptionsInputsContainer.querySelectorAll('.image-option-row');
            let imageOptionsData = [];

            imgRows.forEach(function (row) {
                const textVal = row.querySelector('.img-text-input').value.trim();
                if (textVal !== '') {
                    imageOptionsData.push(textVal);
                }
            });

            combinedOptions.value = JSON.stringify(imageOptionsData);
        } else {
            const optionInputs = optionsInputsContainer.querySelectorAll('.option-input');
            let values = [];
            optionInputs.forEach(function (input) {
                if (input.value.trim() !== '') {
                    values.push(input.value.trim());
                }
            });
            combinedOptions.value = values.join(',');
        }
    });
});
function handleEditTypeChange(id) {
    const typeSelect = document.getElementById(`editQuestionType${id}`);
    const audioDiv = document.getElementById(`editAudioContainer${id}`);
    const imageDiv = document.getElementById(`editImageContainer${id}`);
    const optionsDiv = document.getElementById(`editOptionsContainer${id}`);
    
    const selectedVal = typeSelect.value;

    // အားလုံးကို အရင် ပိတ်ထားပါ (Hide all)
    audioDiv.style.display = 'none';
    imageDiv.style.display = 'none';
    optionsDiv.style.display = 'none';

    // Question Type အလိုက် လိုအပ်တာတွေပဲ ပြန်ဖော်ပါ (Show based on type)
    if (selectedVal === 'audio') {
        audioDiv.style.display = 'block';     // Audio အတွက် အသံဖိုင်ပါမယ်
        optionsDiv.style.display = 'block';   // ရွေးချယ်စရာ Options တွေပါမယ်
    } else if (selectedVal === 'word_selection') {
        optionsDiv.style.display = 'block';   // စကားလုံးရွေးချယ်ရန် Options တွေပါမယ်
    } else if (selectedVal === 'phrase_ordering' || selectedVal === 'translation_tiles') {
        optionsDiv.style.display = 'block';   // ဝါကျစီရန် Options တွေပါမယ်
    } else if (selectedVal === 'image_selection') {
        imageDiv.style.display = 'block';     // ပုံရွေးချယ်ရန် Image Path လိုအပ်ပါမယ်
        optionsDiv.style.display = 'block';   // ပုံ ၃ ပုံအတွက် Options တွေပါမယ်
    }
}

</script>


@endsection
