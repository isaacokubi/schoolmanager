@extends('layouts.app')
@section('title','Portal Login | School Manager')
@section('body')
<div style="min-height:100vh;display:grid;place-items:center;padding:25px;background:linear-gradient(135deg,#edf4ff,#f7f9fc)">
<div class="card" style="width:min(480px,100%)">
<p class="eyebrow">School portal</p><h1>Welcome back</h1><p class="muted">Sign in to access the administration, management or learner portal.</p>
@if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ route('login.submit') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}">
<div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div><br>
<div class="field"><label>Password</label><input type="password" name="password" required></div><br>
<label><input type="checkbox" name="remember" value="1"> Remember me</label><br><br><button class="btn" type="submit" style="width:100%">Sign in</button>
</form>
<p style="margin:20px 0 0">New pupil, parent or sponsor? <a href="{{ route('register') }}">Create a portal account</a></p>
<p style="margin-bottom:0"><a href="{{ route('home') }}">&larr; Back to website</a></p>
</div></div>
@endsection
