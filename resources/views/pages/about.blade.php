@extends('layouts.public')
@section('title','About | '.$settings['school_name'])
@section('content')
<section class="section"><div class="container"><p><strong>ABOUT OUR SCHOOL</strong></p><h1>{{ $settings['school_name'] }}</h1><p class="muted">{{ $settings['vision'] }}</p><div class="grid"><div class="card"><h3>Our Mission</h3><p>{{ $settings['mission'] }}</p></div><div class="card"><h3>Our Vision</h3><p>{{ $settings['vision'] }}</p></div><div class="card"><h3>Our Values</h3><p>{{ $settings['values'] }}</p></div></div>
@if($teachers->count())<div style="margin-top:2rem"><h2>Our teaching team</h2><div class="grid">@foreach($teachers as $teacher)<div class="card"><h3>{{ $teacher->name }}</h3><p>{{ $teacher->email ?: 'Teacher' }}@if($teacher->phone) · {{ $teacher->phone }}@endif</p></div>@endforeach</div></div>@endif
</div></section>
@endsection
