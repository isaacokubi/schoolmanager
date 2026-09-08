@extends('layouts.app')
@section('body')
@php($site = $settings ?? ['school_name'=>'School Manager','school_phone'=>'','school_email'=>'','school_address'=>''])
<nav class="nav" aria-label="Primary navigation">
    <a class="brand" href="{{ route('home') }}" aria-label="{{ $site['school_name'] }} home"><span class="brand-mark">{{ strtoupper(substr($site['school_name'],0,1)) }}</span><span>{{ $site['school_name'] }}</span></a>
    <div class="navlinks">
        <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
        <a class="{{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a>
        <a class="{{ request()->routeIs('academics') ? 'active' : '' }}" href="{{ route('academics') }}">Academics</a>
        <a class="{{ request()->routeIs('admissions') ? 'active' : '' }}" href="{{ route('admissions') }}">Admissions</a>
        <a class="{{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a>
        <a class="btn" href="{{ route('login') }}">Admin Portal</a>
    </div>
</nav>
<main>@yield('content')</main>
<footer class="footer">
    <div class="container footer-grid">
        <div><h3>{{ $site['school_name'] }}</h3><p>{{ $site['vision'] ?? 'Building confident learners through knowledge, character and opportunity.' }}</p><a class="btn secondary" href="{{ route('admissions') }}">Start an application</a></div>
        <div><h4>Explore</h4><p><a href="{{ route('home') }}">Home</a><br><a href="{{ route('about') }}">About us</a><br><a href="{{ route('academics') }}">Academics</a><br><a href="{{ route('admissions') }}">Admissions</a></p></div>
        <div><h4>School office</h4><p>{{ $site['school_address'] ?: 'Contact the school office for location details.' }}<br><br>{{ $site['school_phone'] ?: 'Phone not configured' }}<br>{{ $site['school_email'] ?: 'Email not configured' }}</p></div>
        <div><h4>Parents & families</h4><p>Stay informed about admissions, learning programmes and school activities.</p><a href="{{ route('contact') }}">Contact the school →</a></div>
    </div>
    <div class="container footer-bottom"><span>© {{ date('Y') }} {{ $site['school_name'] }}. All rights reserved.</span><span>School website & management platform</span></div>
</footer>
@endsection