@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid py-3 text-white">
    <div class="mb-4">
        <a class="text-white-50" href="{{ route('super.admin.training.lessons.show', $lesson) }}">{{ $lesson->title }}</a>
        <h2 class="fw-bold mt-2">Edit training question</h2>
    </div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card bg-dark border-secondary text-white"><div class="card-body">
        @include('admin.training.question-fields', ['editing' => true])
    </div></div>
</div>
@endsection
