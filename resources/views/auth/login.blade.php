@extends('layouts.app')
@section('title', 'Administrator Login | School Manager')
@section('body')
{{-- Authentication is a security step so only school staff can use the two features. --}}
<main style="min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f7fb">
    <section class="card" style="width:min(100%,440px);padding:28px">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:22px">
            <span style="display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:#145dcc;color:#fff;font-weight:900">SM</span>
            <div><h1 style="font-size:24px;margin:0">School Manager</h1><p class="muted" style="margin:3px 0 0">Student records and M-Pesa fees</p></div>
        </div>
        <h2 style="font-size:18px">Administrator sign in</h2>
        @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.submit') }}">
            @csrf
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" autofocus></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
            <label style="display:flex;align-items:center;gap:8px;font-weight:500"><input type="checkbox" name="remember" value="1" style="width:auto;margin:0"> Remember me</label>
            <button class="btn" type="submit" style="width:100%;margin-top:10px">Sign in</button>
        </form>
        <p class="muted" style="margin-top:18px">Use an existing administrator account. Public registration and password recovery are not part of this simplified project.</p>
    </section>
</main>
@endsection
