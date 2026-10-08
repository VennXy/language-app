@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h2 class="text-white fw-bold mb-1">{{ isset($question) ? 'Edit Question' : 'Add Question' }}</h2>
        <p class="text-white-50 mb-0">{{ $exam->title }} · Draft exam · Maximum 5 questions per admin</p>
    </div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card glass-card border-0 p-4 text-white" style="max-width:900px">
        <form method="POST" action="{{ isset($question) ? route('admin.exams.questions.update', $question) : route('admin.exams.questions.store', $exam) }}">
            @csrf
            @if(isset($question)) @method('PUT') @endif
            <div class="mb-3">
                <label class="form-label" for="question">Question</label>
                <textarea class="form-control" id="question" name="question" rows="3" maxlength="2000" required>{{ old('question', $question->question ?? '') }}</textarea>
            </div>
            @foreach(['a', 'b', 'c', 'd'] as $option)
                <div class="mb-3">
                    <label class="form-label" for="option_{{ $option }}">Option {{ strtoupper($option) }}</label>
                    <input class="form-control" id="option_{{ $option }}" name="option_{{ $option }}" type="text" maxlength="2000" required value="{{ old('option_'.$option, $question->{'option_'.$option} ?? '') }}">
                </div>
            @endforeach
            <div class="mb-4">
                <label class="form-label" for="correct_option">Correct option</label>
                <select class="form-select" id="correct_option" name="correct_option" required>
                    @foreach(['A', 'B', 'C', 'D'] as $letter)
                        <option value="{{ $letter }}" @selected(old('correct_option', strtoupper($question->correct_option ?? '')) === $letter)>{{ $letter }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn text-white px-4" type="submit" style="background:#f472b6;border:0">Save Draft</button>
            <a class="btn btn-outline-light ms-2" href="{{ route('admin.exams.questions.index', $exam) }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
