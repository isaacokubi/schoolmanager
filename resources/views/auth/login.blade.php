@extends('layouts.app')
@section('title', 'Administrator Sign In | School Manager')
@section('body')
{{-- Keep this page focused on secure administrator access; public registration remains disabled. --}}
<style>
    .login-page{min-height:100vh;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(420px,.95fr);background:#f5f8fc}
    .login-brand-panel{position:relative;isolation:isolate;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;padding:clamp(32px,5vw,72px);background:linear-gradient(145deg,#071b36 0%,#0b3970 58%,#145dcc 100%);color:#fff}
    .login-brand-panel:before,.login-brand-panel:after{content:"";position:absolute;z-index:-1;border:1px solid rgba(255,255,255,.12);border-radius:50%;pointer-events:none}
    .login-brand-panel:before{width:520px;height:520px;right:-240px;top:-170px;box-shadow:0 0 0 42px rgba(255,255,255,.025),0 0 0 88px rgba(255,255,255,.02)}
    .login-brand-panel:after{width:310px;height:310px;left:-170px;bottom:-140px;box-shadow:0 0 0 35px rgba(255,255,255,.025)}
    .login-brand{display:flex;align-items:center;gap:13px;color:#fff;text-decoration:none;width:max-content;max-width:100%}
    .login-brand-mark{width:48px;height:48px;display:grid;place-items:center;border-radius:15px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.22);font-weight:900;font-size:15px;letter-spacing:.03em;box-shadow:0 8px 25px rgba(0,0,0,.12)}
    .login-brand-name{font-size:19px;font-weight:800;letter-spacing:-.03em;line-height:1.2}
    .login-brand-caption{display:block;margin-top:4px;color:#c6dcfa;font-size:12px;font-weight:600;letter-spacing:.04em}
    .login-brand-copy{max-width:560px;margin:72px 0;position:relative}
    .login-eyebrow{display:inline-flex;align-items:center;gap:9px;color:#c6dcfa;font-size:12px;font-weight:800;letter-spacing:.16em;text-transform:uppercase}
    .login-eyebrow:before{content:"";width:24px;height:2px;background:#f4b41a;border-radius:2px}
    .login-brand-copy h1{font-size:clamp(36px,4.4vw,62px);line-height:1.04;letter-spacing:-.055em;margin:20px 0 22px;max-width:550px}
    .login-brand-copy p{font-size:17px;line-height:1.8;color:#d4e4f8;max-width:440px;margin:0}
    .login-feature-list{display:grid;gap:15px;margin-top:38px}
    .login-feature{display:flex;align-items:center;gap:12px;color:#e6f0fc;font-size:14px;font-weight:600}
    .login-feature-icon{width:30px;height:30px;flex:0 0 30px;border-radius:10px;display:grid;place-items:center;background:rgba(255,255,255,.1);color:#fff}
    .login-brand-footer{color:#a9c5e8;font-size:12px;position:relative}
    .login-content{display:grid;place-items:center;padding:36px clamp(22px,5vw,76px);min-width:0}
    .login-card{width:min(100%,440px);background:#fff;border:1px solid #e3eaf3;border-radius:24px;padding:clamp(25px,3.5vw,42px);box-shadow:0 24px 70px rgba(20,45,80,.09)}
    .login-mobile-brand{display:none;align-items:center;gap:11px;margin-bottom:32px}
    .login-mobile-mark{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#0f5bd7,#3182f6);color:#fff;font-weight:900}
    .login-mobile-brand strong{display:block;font-size:17px;line-height:1.2}
    .login-mobile-brand small{display:block;margin-top:3px;color:#607087;font-size:12px}
    .login-lock{width:48px;height:48px;display:grid;place-items:center;border-radius:15px;background:#edf4ff;color:#145dcc;margin-bottom:23px}
    .login-heading{margin:0;color:#132238;font-size:30px;line-height:1.15;letter-spacing:-.045em}
    .login-subheading{margin:10px 0 27px;color:#65758b;font-size:14px;line-height:1.7}
    .login-form{display:grid;gap:18px}
    .login-field{display:grid;gap:8px;min-width:0}
    .login-field label{font-size:13px;font-weight:800;color:#273b55}
    .login-input-wrap{position:relative}
    .login-input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#7d8da2;display:flex;pointer-events:none}
    .login-field input[type=email],.login-field input[type=password]{display:block;width:100%;min-height:51px;border:1px solid #d9e2ee;border-radius:12px;background:#fbfcfe;padding:12px 14px 12px 43px;color:#132238;outline:0;transition:border-color .18s ease,box-shadow .18s ease,background .18s ease}
    .login-field input:focus{border-color:#4c8de9;background:#fff;box-shadow:0 0 0 4px rgba(15,91,215,.1)}
    .login-field input::placeholder{color:#9aa7b8}
    .login-options{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .login-remember{display:inline-flex;align-items:center;gap:9px;color:#53647a;font-size:13px;font-weight:600;cursor:pointer}
    .login-remember input{width:16px;height:16px;margin:0;accent-color:#145dcc}
    .login-link{color:#145dcc;text-decoration:none;font-size:13px;font-weight:800}
    .login-link:hover{text-decoration:underline}
    .login-submit{width:100%;min-height:51px;display:flex;align-items:center;justify-content:center;gap:10px;margin-top:3px;border:0;border-radius:12px;background:linear-gradient(100deg,#145dcc,#2675e8);color:#fff;font-size:14px;font-weight:850;letter-spacing:.01em;cursor:pointer;box-shadow:0 9px 20px rgba(20,93,204,.2);transition:transform .18s ease,box-shadow .18s ease}
    .login-submit:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(20,93,204,.25)}
    .login-submit:focus-visible,.login-link:focus-visible{outline:3px solid #93c5fd;outline-offset:3px}
    .login-security-note{display:flex;align-items:flex-start;gap:10px;padding:14px;margin-top:24px;border:1px solid #e5edf7;border-radius:12px;background:#f7faff;color:#64758b;font-size:12px;line-height:1.65}
    .login-security-note svg{flex:0 0 auto;color:#2870d7;margin-top:1px}
    .login-card-footer{border-top:1px solid #edf1f6;margin-top:25px;padding-top:18px;color:#8794a6;font-size:11px;text-align:center}
    .login-alert{padding:12px 14px;border-radius:11px;margin:0 0 18px;font-size:13px;line-height:1.6}
    .login-alert.success{background:#eaf8ef;border:1px solid #ccebd6;color:#176b3a}
    .login-alert.error{background:#fff0f0;border:1px solid #ffd7d7;color:#a51d2d}
    @media(max-width:900px){.login-page{grid-template-columns:minmax(0,.85fr) minmax(380px,1.15fr)}.login-brand-panel{padding:34px}.login-brand-copy h1{font-size:clamp(34px,5vw,48px)}.login-content{padding:28px}}
    @media(max-width:700px){.login-page{display:block;min-height:100vh;padding:22px 16px;background:radial-gradient(circle at 10% 0%,rgba(49,130,246,.1),transparent 30%),#f5f8fc}.login-brand-panel{display:none}.login-content{display:block;padding:0}.login-card{margin:0 auto;padding:27px 23px;border-radius:21px}.login-mobile-brand{display:flex}.login-lock{margin-bottom:18px}.login-heading{font-size:27px}.login-subheading{margin-bottom:23px}}
    @media(max-width:380px){.login-page{padding:14px 10px}.login-card{padding:23px 18px}.login-options{align-items:flex-start;flex-direction:column}.login-heading{font-size:25px}}
    @media(prefers-reduced-motion:reduce){.login-field input,.login-submit{transition:none}}
</style>
<main class="login-page">
    <aside class="login-brand-panel" aria-label="School Manager information">
        <a class="login-brand" href="{{ url('/') }}" aria-label="School Manager home">
            <span class="login-brand-mark">SM</span>
            <span><span class="login-brand-name">School Manager</span><span class="login-brand-caption">SCHOOL ADMINISTRATION</span></span>
        </a>
        <div class="login-brand-copy">
            <span class="login-eyebrow">Administrator workspace</span>
            <h1>School records.<br>Handled with care.</h1>
            <p>One secure place to manage student information and keep track of school fee payments.</p>
            <div class="login-feature-list">
                <div class="login-feature"><span class="login-feature-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>Organised student records</div>
                <div class="login-feature"><span class="login-feature-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg></span>M-Pesa fee payment tracking</div>
                <div class="login-feature"><span class="login-feature-icon" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span>Private administrator access</div>
            </div>
        </div>
        <div class="login-brand-footer">A simpler way to stay on top of school administration.</div>
    </aside>

    <section class="login-content" aria-labelledby="login-heading">
        <div class="login-card">
            <div class="login-mobile-brand" aria-label="School Manager">
                <span class="login-mobile-mark">SM</span>
                <span><strong>School Manager</strong><small>Student records &amp; fee payments</small></span>
            </div>
            <div class="login-lock" aria-hidden="true">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/><path d="M12 14v3"/></svg>
            </div>
            <h2 class="login-heading" id="login-heading">Welcome back</h2>
            <p class="login-subheading">Sign in with your administrator account to continue.</p>

            @if(session('status'))
                <div class="login-alert success" role="status">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="login-alert error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="login-form" method="post" action="{{ route('login.submit') }}">
                @csrf
                <div class="login-field">
                    <label for="login-email">Email address</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                        <input id="login-email" type="email" name="email" value="{{ old('email') }}" placeholder="admin@school.com" required autocomplete="username" autofocus>
                    </div>
                </div>
                <div class="login-field">
                    <label for="login-password">Password</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2.5"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg></span>
                        <input id="login-password" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>
                <div class="login-options">
                    <label class="login-remember" for="login-remember"><input id="login-remember" type="checkbox" name="remember" value="1"> Remember me</label>
                    <a class="login-link" href="{{ route('password.request') }}">Forgot password?</a>
                </div>
                <button class="login-submit" type="submit">
                    Sign in securely
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <div class="login-security-note">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                <span><strong>Administrator-only access.</strong> Public registration is disabled. If you need help signing in, use the secure password reset link.</span>
            </div>
            <div class="login-card-footer">School Manager <span aria-hidden="true">·</span> Secure administrator sign in</div>
        </div>
    </section>
</main>
@endsection
