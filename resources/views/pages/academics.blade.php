@extends('layouts.public')
@section('title','Academics | '.$settings['school_name'])
@section('content')
<section class="section"><div class="container"><p><strong>ACADEMICS</strong></p><h1>Learning at {{ $settings['school_name'] }}</h1><p class="muted">{{ $settings['academic_year'] ? 'Academic year '.$settings['academic_year'].' · '.$settings['academic_term'] : 'Current academic programme' }}</p>
<h2>Classes</h2><div class="grid">@forelse($classes as $class)<div class="card"><h3>{{ $class->name }}</h3><p>{{ $class->stream ?: 'General stream' }}@if($class->academic_year) · {{ $class->academic_year }}@endif</p></div>@empty<div class="card"><p>No classes have been published yet.</p></div>@endforelse</div>
<h2 style="margin-top:2rem">Subjects and learning areas</h2><div class="grid">@forelse($subjects as $subject)<div class="card"><h3>{{ $subject->name }}</h3><p>{{ $subject->code ? 'Code: '.$subject->code : 'Core learning area' }}@if($subject->teacher_name) · Teacher: {{ $subject->teacher_name }}@endif</p></div>@empty<div class="card"><p>No subjects have been entered yet.</p></div>@endforelse</div>
</div></section>
@endsection
