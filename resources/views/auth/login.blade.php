@extends('layouts.public')
@section('title','Portal Login | School Manager')
@section('content')
<section class="login-page">
    <div class="container login-shell">
        <div class="login-intro">
            <span class="login-eyebrow">Secure school portal</span>
            <h1>Welcome back</h1>
            <p>Sign in securely to access the administration, management or learner portal.</p>
            <div class="login-trust">
                <div class="trust-item"><span class="trust-icon">✓</span><div><strong>Secure access</strong><small>Your account is protected by authenticated portal access.</small></div></div>
                <div class="trust-item"><span class="trust-icon">◎</span><div><strong>One school workspace</strong><small>Access the tools and records available to your account.</small></div></div>
            </div>
        </div>

        <div class="login-card">
            <div class="login-card-head">
                <div class="login-mark">{{ strtoupper(substr(($settings['school_name'] ?? 'School Manager'), 0, 1)) }}</div>
                <div><span class="login-card-kicker">Portal sign in</span><h2>Sign in to your account</h2><p>Use the email address and password registered with the school.</p></div>
            </div>

            @if($errors->any())
                <div class="login-error" role="alert" aria-live="polite"><span>!</span><div><strong>Sign-in unsuccessful</strong><p>{{ $errors->first() }}</p></div></div>
            @endif

            <form method="post" action="{{ route('login.submit') }}" class="login-form">
                @csrf
                <div class="login-field">
                    <label for="email">Email address <span>*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required autofocus placeholder="you@example.com">
                </div>

                <div class="login-field">
                    <div class="field-label-row"><label for="password">Password <span>*</span></label><a href="{{ route('password.request') }}" class="password-help">Forgot password?</a></div>
                    <div class="password-wrap"><input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Enter your password"><button type="button" class="password-toggle" id="toggle-password" aria-label="Show password" aria-controls="password" aria-pressed="false">Show</button></div>
                </div>

                <div class="login-options">
                    <label class="remember"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}><span>Remember me</span></label>
                    <span class="secure-label">Protected sign-in</span>
                </div>

                <button class="login-submit" type="submit"><span>Sign in</span><span aria-hidden="true">→</span></button>
            </form>

            <div class="login-register">
                <span>New pupil, parent, sponsor or teacher?</span>
                <a href="{{ route('register') }}">Create a portal account <span aria-hidden="true">→</span></a>
            </div>

            @if(session('status'))
                <div class="login-success" role="status">{{ session('status') }}</div>
            @endif
            <p class="login-note">If you no longer know your password, use the email recovery link above.</p>
        </div>
    </div>
</section>

<style>
.login-page{position:relative;overflow:hidden;padding:76px 0 92px;background:linear-gradient(180deg,#f5f9ff 0%,#fff 68%)}.login-page:before{content:"";position:absolute;inset:-180px -100px auto auto;width:520px;height:520px;border-radius:50%;background:radial-gradient(circle,rgba(15,91,215,.13),rgba(15,91,215,0) 68%);pointer-events:none}.login-page:after{content:"";position:absolute;left:-220px;bottom:-300px;width:560px;height:560px;border:90px solid rgba(244,180,26,.07);border-radius:50%;pointer-events:none}.login-shell{position:relative;z-index:1;display:grid;grid-template-columns:minmax(0,1fr) minmax(420px,500px);gap:72px;align-items:center;max-width:1050px}.login-intro{padding:20px 0}.login-eyebrow,.login-card-kicker{display:inline-block;color:#0f5bd7;font-size:11px;font-weight:900;letter-spacing:.13em;text-transform:uppercase}.login-intro h1{margin:12px 0 15px;color:#0b2342;font-size:clamp(42px,5vw,62px);line-height:1.03;letter-spacing:-.045em}.login-intro>p{max-width:560px;margin:0;color:#5f7189;font-size:18px;line-height:1.7}.login-trust{display:grid;gap:14px;margin-top:34px;max-width:530px}.trust-item{display:flex;gap:13px;align-items:flex-start;padding:15px 17px;border:1px solid #e0e8f2;border-radius:14px;background:rgba(255,255,255,.78);box-shadow:0 8px 24px rgba(18,39,70,.045)}.trust-icon{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:9px;background:#eaf3ff;color:#0f5bd7;font-weight:900}.trust-item strong{display:block;color:#17304f;font-size:13px}.trust-item small{display:block;margin-top:2px;color:#718198;font-size:11px;line-height:1.5}.login-card{padding:31px;border:1px solid #dfe7f1;border-radius:20px;background:#fff;box-shadow:0 24px 65px rgba(18,39,70,.11)}.login-card-head{display:flex;gap:14px;align-items:flex-start;margin-bottom:25px}.login-mark{display:grid;place-items:center;flex:0 0 46px;width:46px;height:46px;border-radius:13px;background:linear-gradient(135deg,#0f5bd7,#3182f6);color:#fff;font-size:18px;font-weight:900;box-shadow:0 9px 20px rgba(15,91,215,.2)}.login-card h2{margin:3px 0 4px;color:#132238;font-size:21px;letter-spacing:-.02em}.login-card-head p{margin:0;color:#728198;font-size:11px;line-height:1.55}.login-error{display:flex;gap:10px;align-items:flex-start;margin:0 0 20px;padding:12px 13px;border:1px solid #f2c7c3;border-radius:11px;background:#fff2f1;color:#8f241b}.login-error>span{display:grid;place-items:center;flex:0 0 22px;width:22px;height:22px;border-radius:50%;background:#ffdcd8;font-weight:900}.login-error strong{display:block;font-size:12px}.login-error p{margin:2px 0 0;font-size:11px;line-height:1.45}.login-form{display:grid;gap:19px}.login-field{display:grid;gap:7px}.login-field label,.field-label-row label{font-size:12px;font-weight:800;color:#263950}.login-field label span,.field-label-row label span{color:#b42318}.field-label-row{display:flex;justify-content:space-between;align-items:center;gap:10px}.password-help{color:#0f5bd7;font-size:11px;text-decoration:none;font-weight:750}.password-help:hover{text-decoration:underline}.login-field input{width:100%;padding:13px 14px;border:1px solid #d1dce8;border-radius:11px;background:#fff;color:#132238;outline:0;transition:.18s ease}.login-field input::placeholder{color:#9aa8b8}.login-field input:focus{border-color:#6fa4ef;box-shadow:0 0 0 4px rgba(15,91,215,.09)}.password-wrap{position:relative}.password-wrap input{padding-right:68px}.password-toggle{position:absolute;right:7px;top:50%;transform:translateY(-50%);border:0;border-radius:8px;padding:7px 9px;background:#eef5ff;color:#0f5bd7;font-size:10px;font-weight:850;cursor:pointer}.password-toggle:hover{background:#e1edff}.login-options{display:flex;justify-content:space-between;align-items:center;gap:12px}.remember{display:flex;align-items:center;gap:8px;color:#53647a;font-size:11px;font-weight:650;cursor:pointer}.remember input{width:15px;height:15px;accent-color:#0f5bd7}.secure-label{color:#7c8b9e;font-size:10px}.login-submit{width:100%;display:flex;align-items:center;justify-content:space-between;padding:13px 15px;border:0;border-radius:11px;background:linear-gradient(135deg,#0f5bd7,#1769d9);color:#fff;font-weight:850;cursor:pointer;box-shadow:0 10px 22px rgba(15,91,215,.18);transition:.18s ease}.login-submit:hover{transform:translateY(-1px);box-shadow:0 13px 26px rgba(15,91,215,.23)}.login-submit:focus-visible,.password-toggle:focus-visible,.password-help:focus-visible{outline:3px solid rgba(15,91,215,.2);outline-offset:2px}.login-register{display:flex;flex-wrap:wrap;justify-content:center;gap:5px;margin-top:22px;padding-top:20px;border-top:1px solid #edf1f5;color:#66778d;font-size:11px;text-align:center}.login-register a{color:#0f5bd7;font-weight:800;text-decoration:none}.login-register a:hover{text-decoration:underline}.login-note{margin:17px 0 0;color:#8a98aa;font-size:10px;line-height:1.5;text-align:center}.login-success{margin:16px 0 0;padding:11px 12px;border:1px solid #bfe5cd;border-radius:10px;background:#e9f8ef;color:#176b3a;font-size:11px;line-height:1.45;text-align:center}.login-page a{-webkit-tap-highlight-color:transparent}@media(max-width:850px){.login-shell{grid-template-columns:1fr;gap:35px;max-width:560px}.login-intro{text-align:center;padding-top:5px}.login-intro>p{margin-inline:auto}.login-trust{text-align:left;margin-inline:auto}.login-intro h1{font-size:46px}}@media(max-width:560px){.login-page{padding:45px 0 62px}.login-card{padding:22px 18px;border-radius:16px}.login-intro h1{font-size:40px}.login-intro>p{font-size:16px}.login-options{align-items:flex-start;flex-direction:column}.secure-label{display:none}.login-card-head{gap:11px}.login-mark{width:40px;height:40px;flex-basis:40px;font-size:16px}}
</style>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const toggle=document.getElementById('toggle-password');
    const password=document.getElementById('password');
    if(!toggle||!password)return;
    toggle.addEventListener('click',function(){
        const visible=password.type==='text';
        password.type=visible?'password':'text';
        toggle.textContent=visible?'Show':'Hide';
        toggle.setAttribute('aria-label',visible?'Show password':'Hide password');
        toggle.setAttribute('aria-pressed',String(!visible));
    });
});
</script>
@endsection
