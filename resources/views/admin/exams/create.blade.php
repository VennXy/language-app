@extends('layouts.admin')

@section('admin_content')
<div class="container-fluid px-0">
    <h2 class="text-white fw-bold mb-4">Create Exam</h2>
    <div class="card glass-card border-0 p-4" style="max-width:760px">
        <form method="POST" action="{{ route('super.admin.exams.store') }}">
            @csrf
            @include('admin.exams._form', ['exam' => null])
        </form>
    </div>
</div>
@endsection
