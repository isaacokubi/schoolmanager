{{-- Shared public layout for the remaining public authentication and account-recovery pages.
     Keep navigation limited to routes that exist in the simplified student-records/M-Pesa app. --}}
@extends('layouts.app')

@section('body')
@php($site = $settings ?? ['school_name' => 'School Manager', 'school_phone' => '', 'school_email' => '', 'school_address' => ''])
<nav class="nav" aria-label="Account navigation">
    <a class="brand" href="{{ route('login') }}" aria-label="{{ $site['school_name'] }} sign in">
        <span class="brand-mark">{{ strtoupper(substr($site['school_name'], 0, 1)) }}</span>
        <span>{{ $site['school_name'] }}</span>
    </a>
    <div class="navlinks">
        <a class="{{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Sign in</a>
        <a class="{{ request()->routeIs('password.request') ? 'active' : '' }}" href="{{ route('password.request') }}">Forgot password?</a>
    </div>
</nav>

<main id="main-content">
    @yield('content')
</main>

<footer class="footer">
    <div class="container footer-grid">
        <div>
            <h3>{{ $site['school_name'] }}</h3>
            <p>Secure student records and school-fee payments.</p>
        </div>
        <div>
            <h4>Account access</h4>
            <p><a href="{{ route('login') }}">Sign in</a><br><a href="{{ route('password.request') }}">Recover password</a></p>
        </div>
        <div>
            <h4>School office</h4>
            <p>{{ $site['school_address'] ?: 'Contact the school office for location details.' }}<br><br>{{ $site['school_phone'] ?: 'Phone not configured' }}<br>{{ $site['school_email'] ?: 'Email not configured' }}</p>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ date('Y') }} {{ $site['school_name'] }}. All rights reserved.</span>
        <span>Student records · M-Pesa payments</span>
    </div>
</footer>
@endsection
