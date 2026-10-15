{{--
    PRESENTATION NOTE: support template.
    This Blade view displays page content using data and state prepared by Laravel.
    The directives below control reusable layout sections, loops, and conditional content.
--}}

{{-- Inherit the shared layout and its common structure. --}}
@extends('layouts.app')
{{-- Define the page content that the parent layout will render. --}}
@section('title','Support | '.$settings['school_name'])
{{-- Define the page content that the parent layout will render. --}}
@section('body')
<div class="page-hero"><div class="container"><p class="eyebrow">Help the school community</p><h1>Support</h1><p>Find ways to contribute learning resources, welfare support, technology and community partnerships.</p></div></div>
<section class="section"><div class="container"><div class="grid">
{{-- Render this block once for each item in the collection. --}}
@foreach([['Learning resources','Books, devices and classroom materials that directly support teaching and learning.'],['Learner welfare','Support programmes that help learners access meals, care and a safe learning environment.'],['Technology','Digital infrastructure and learning tools for modern classroom delivery.']] as $item)<article class="card"><h3>{{ $item[0] }}</h3><p>{{ $item[1] }}</p><a class="btn" href="{{ route('donations') }}">Offer support</a></article>@endforeach
</div></div></section>
@endsection