@extends('layouts.public')

@section('title', 'Forgot Password | School Manager')

@section('content')
<section class="auth-page">
    <div class="auth-card">
        <div class="auth-icon">↗</div>
        <span class="auth-kicker">Account recovery</span>
        <h1>Forgot your password?</h1>
        <p class="auth-intro">Enter the email address registered with your school account and we will send you a secure password reset link.</p>

        @if(session('status'))
            <div class="auth-alert success" role="status">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="auth-alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <label for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="email"
                inputmode="email"
                required
                autofocus
                placeholder="you@example.com"
            >

            <button type="submit">Email me a reset link <span aria-hidden="true">→</span></button>
        </form>

        <a class="back-link" href="{{ route('login') }}">← Back to sign in</a>
    </div>
</section>

<style>
.auth-page{min-height:70vh;display:grid;place-items:center;padding:70px 20px;background:linear-gradient(180deg,#f5f9ff 0%,#fff 100%)}
.auth-card{width:min(100%,520px);padding:38px;border:1px solid #dfe7f1;border-radius:22px;background:#fff;box-shadow:0 24px 65px rgba(18,39,70,.1)}
.auth-icon{display:grid;place-items:center;width:48px;height:48px;margin-bottom:18px;border-radius:14px;background:#eaf3ff;color:#0f5bd7;font-weight:900;font-size:21px}
.auth-kicker{color:#0f5bd7;font-size:11px;font-weight:900;letter-spacing:.13em;text-transform:uppercase}
.auth-card h1{margin:9px 0 10px;color:#10243e;font-size:32px;line-height:1.12;letter-spacing:-.035em}
.auth-intro{margin:0 0 25px;color:#687991;line-height:1.65}
.auth-form{display:grid;gap:9px}
.auth-form label{font-size:13px;font-weight:800;color:#263950}
.auth-form input{width:100%;padding:14px;border:1px solid #d1dce8;border-radius:11px;outline:0;background:#fff}
.auth-form input:focus{border-color:#6fa4ef;box-shadow:0 0 0 4px rgba(15,91,215,.09)}
.auth-form button{margin-top:8px;padding:14px 16px;border:0;border-radius:11px;background:linear-gradient(135deg,#0f5bd7,#1769d9);color:#fff;font-weight:850;cursor:pointer;box-shadow:0 10px 22px rgba(15,91,215,.18)}
.auth-alert{padding:13px 14px;border-radius:11px;margin-bottom:18px;font-size:13px}
.auth-alert.success{background:#e9f8ef;color:#176b3a}
.auth-alert.error{background:#fff0f0;color:#a51d2d}
.back-link{display:inline-block;margin-top:22px;color:#0f5bd7;text-decoration:none;font-size:13px;font-weight:800}
.back-link:hover{text-decoration:underline}
@media(max-width:560px){.auth-page{padding:45px 14px}.auth-card{padding:25px 19px;border-radius:17px}.auth-card h1{font-size:28px}}
</style>
@endsection
