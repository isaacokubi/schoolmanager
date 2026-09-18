@extends('layouts.app')
@section('title','Account Email | School Manager')
@section('body')
<div style="min-height:100vh;padding:50px 20px;background:linear-gradient(135deg,#eef5ff,#f8fbff)">
<div class="container" style="max-width:760px">
<a href="{{ url()->previous() }}" style="text-decoration:none;color:#0f5bd7;font-weight:800">← Back</a>
<div class="card" style="margin-top:18px">
<p class="eyebrow">Account security</p>
<h1 style="margin:4px 0 8px">Login &amp; password recovery email</h1>
<p class="muted">Use one verified email address for your account login and password recovery. This works consistently for administrators, managers, teachers, parents, sponsors and pupils.</p>
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="errors"><ul style="margin:0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="notice" style="margin:18px 0">
<strong>Current email</strong><br>
<span>{{ $user->email ?: 'No email configured' }}</span>
</div>
<form method="POST" action="{{ route('account.email.update') }}" class="form-grid">
@csrf @method('PUT')
<div class="field full">
<label for="email">New email address</label>
<input id="email" type="email" name="email" value="{{ old('email',$user->email) }}" required autocomplete="email">
<small class="muted">Password reset links will be sent to this address.</small>
</div>
<div class="field full">
<label for="current_password">Current password</label>
<input id="current_password" type="password" name="current_password" required autocomplete="current-password">
<small class="muted">Required to prevent another person using your logged-in session from changing the recovery address.</small>
</div>
<div class="field full" style="display:flex;justify-content:flex-end">
<button class="btn" type="submit">Save recovery email</button>
</div>
</form>
<div style="margin-top:22px;padding-top:18px;border-top:1px solid #e4eaf2">
<strong>How recovery works</strong>
<p class="muted" style="margin-bottom:0">Forgot Password searches the account email stored for your user account. After this change, the same address is used for login, password recovery and role-specific email notifications.</p>
</div>
</div></div>
@endsection
