@php
    $link = $link ?? null;
    $formQuestion = $editing ? $question : null;
    $initialOptions = $formQuestion ? (preg_split('/\s*,\s*/', $formQuestion->options ?? '') ?: []) : [];
    $initialImages = $formQuestion ? (json_decode($formQuestion->image_url ?? '', true) ?: []) : [];
    $questionAction = $editing ? route('super.admin.training.questions.update', [$lesson, $formQuestion]) : route('super.admin.training.questions.store', $lesson);
@endphp
<form method="POST" action="{{ $questionAction }}" enctype="multipart/form-data" class="row g-3">
    @csrf
    @if($editing) @method('PUT') @endif
    @if($editing && $link)
        <input type="hidden" name="current_item_id" value="{{ $link->lesson_vocabulary_item_id }}">
        <input type="hidden" name="current_stage" value="{{ $link->stage }}">
    @endif
    <div class="col-md-4"><label class="form-label">Question type</label><select name="question_type" id="question-type" class="form-select bg-black text-white border-secondary" required>
        @foreach(['image_selection' => 'Image selection', 'word_selection' => 'Word selection', 'translation_tiles' => 'Translation tiles', 'phrase_ordering' => 'Phrase ordering'] as $type => $label)
            <option value="{{ $type }}" @selected(old('question_type', $formQuestion?->question_type) === $type)>{{ $label }}</option>
        @endforeach
    </select></div>
    <div class="col-md-4"><label class="form-label">Learning item</label><select name="learning_item_id" class="form-select bg-black text-white border-secondary" required>
        <option value="">Choose learning item</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected(old('learning_item_id', $link?->lesson_vocabulary_item_id) == $item->id)>{{ $item->japanese }} — {{ $item->english }}</option>@endforeach
    </select></div>
    <div class="col-md-2"><label class="form-label">Learning stage</label><select name="stage" class="form-select bg-black text-white border-secondary" required>
        @foreach(['picture_recognition' => 'Picture recognition', 'word_recognition' => 'Word recognition', 'recall' => 'Recall', 'listening' => 'Listening', 'usage' => 'Usage', 'review' => 'Review'] as $stage => $label)
            <option value="{{ $stage }}" @selected(old('stage', $link?->stage) === $stage)>{{ $label }}</option>
        @endforeach
    </select></div>
    <div class="col-md-2"><label class="form-label">Stage order</label><input type="number" min="0" name="stage_order" class="form-control bg-black text-white border-secondary" value="{{ old('stage_order', $link?->stage_order ?? 0) }}" required></div>
    <div class="col-12"><label class="form-label">Question / prompt</label><textarea name="question" class="form-control bg-black text-white border-secondary" rows="2" required>{{ old('question', $formQuestion?->question) }}</textarea></div>
    <div class="col-md-6"><label class="form-label">Options (one per line; comma-separated is also accepted)</label><textarea id="question-options" name="options" class="form-control bg-black text-white border-secondary" rows="4" required>{{ old('options', implode("\n", $initialOptions)) }}</textarea></div>
    <div class="col-md-6"><label class="form-label">Correct answer</label><input name="correct_answer" class="form-control bg-black text-white border-secondary" value="{{ old('correct_answer', $formQuestion?->correct_answer) }}" required><div class="form-text text-white-50">For selection questions, match one option exactly.</div></div>
    <div class="col-12" id="option-image-fields"><label class="form-label">Image per option (image_selection)</label><div class="row g-2" id="option-image-list"></div><div class="form-text text-white-50">Each uploaded image stays mapped to its option text. Existing option images are kept when their option remains.</div></div>
    <div class="col-md-6"><label class="form-label">Audio file (optional, used for listening practice)</label><input type="file" name="audio" accept="audio/*" class="form-control bg-black text-white border-secondary">@if($formQuestion?->audio)<a class="small text-info" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($formQuestion->audio) }}" target="_blank" rel="noopener">Play current audio</a>@endif</div>
    <div class="col-12"><button type="submit" class="btn btn-outline-light">{{ $editing ? 'Save Question' : 'Add Question' }}</button></div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const optionsInput = document.getElementById('question-options');
    const imagesList = document.getElementById('option-image-list');
    const imagesSection = document.getElementById('option-image-fields');
    const typeInput = document.getElementById('question-type');
    const keptImages = @json($initialImages);
    function renderOptionImages() {
        const options = [...new Set(optionsInput.value.split(/[\r\n,]+/).map(value => value.trim()).filter(Boolean))];
        imagesList.replaceChildren();
        options.forEach((option, index) => {
            const col = document.createElement('div'); col.className = 'col-md-6';
            const label = document.createElement('label'); label.className = 'form-label small'; label.textContent = option;
            col.append(label);
            if (keptImages[option]) {
                const img = document.createElement('img'); img.src = @json(asset('storage')) + '/' + keptImages[option]; img.alt = option; img.className = 'd-block mb-2 rounded'; img.style.maxWidth = '120px'; img.style.maxHeight = '80px'; img.style.objectFit = 'cover'; col.append(img);
            }
            const input = document.createElement('input'); input.type = 'file'; input.name = 'option_images[' + index + ']'; input.accept = 'image/*'; input.className = 'form-control bg-black text-white border-secondary'; col.append(input); imagesList.append(col);
        });
    }
    function updateFields() {
        const imageQuestion = typeInput.value === 'image_selection';
        imagesSection.hidden = !imageQuestion;
    }
    optionsInput.addEventListener('input', renderOptionImages);
    typeInput.addEventListener('change', updateFields);
    renderOptionImages(); updateFields();
});
</script>
