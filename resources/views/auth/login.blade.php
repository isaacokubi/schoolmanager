@extends('layouts.public')
@section('title','Portal Login | School Manager')
@section('content')
<section class="page-hero">
    <div class="container">
        <p class="eyebrow">School portal</p>
        <h1>Welcome back</h1>
        <p>Sign in to access the administration, management or learner portal.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:620px">
        <div class="card">
            @if($errors->any())
                <div class="errors">{{ $errors->first() }}</div>
            @endif

            <form method="post" action="{{ route('login.submit') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field" style="margin-top:18px">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:18px">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <div style="margin-top:24px">
                    <button class="btn" type="submit">Sign in</button>
                </div>
            </form>

            <p style="margin:22px 0 0">New pupil, parent, sponsor or teacher? <a href="{{ route('register') }}">Create a portal account</a></p>
        </div>
    </div>
</section>
@endsection
