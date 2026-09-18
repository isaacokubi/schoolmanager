@extends('layouts.app')
@section('title','Support | '.$settings['school_name'])
@section('body')
<div class="page-hero"><div class="container"><p class="eyebrow">Help the school community</p><h1>Support</h1><p>Find ways to contribute learning resources, welfare support, technology and community partnerships.</p></div></div>
<section class="section"><div class="container"><div class="grid">
@foreach([['Learning resources','Books, devices and classroom materials that directly support teaching and learning.'],['Learner welfare','Support programmes that help learners access meals, care and a safe learning environment.'],['Technology','Digital infrastructure and learning tools for modern classroom delivery.']] as $item)<article class="card"><h3>{{ $item[0] }}</h3><p>{{ $item[1] }}</p><a class="btn" href="{{ route('donations') }}">Offer support</a></article>@endforeach
</div></div></section>
@endsection