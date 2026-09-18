@extends('layouts.app')
@section('title',ucfirst($type).' | School Community')
@section('body')
<div class="page-hero"><div class="container"><p class="eyebrow">School community</p><h1>{{ ucfirst($type) }}</h1><p>Connect with the people who make {{ $settings['school_name'] }} a learning community.</p></div></div>
<section class="section"><div class="container"><div class="grid">
@forelse($data as $item)<article class="card"><h3>{{ $item->name ?? 'School community member' }}</h3>
@if($type==='teachers')<p class="muted">{{ $item->email ?? 'Teacher' }}</p>@elseif($type==='pupils')<p class="muted">Admission: {{ $item->admission_number ?? '—' }}</p>@else<p class="muted">{{ $item->email ?? 'Sponsor / guardian' }}</p>@endif
</article>@empty<div class="empty">No published community records are available.</div>@endforelse
</div></div></section>
@endsection