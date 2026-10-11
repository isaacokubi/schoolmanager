@extends('layouts.admin')
@section('title', 'Change Registered Email | School Manager')
@section('page_title', 'Account settings')
@section('admin_content')
<div class="admin-page-head">
    <div>
        <h1>Change registered email</h1>
        <p>Update the email address you use to sign in and recover your account.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert error" role="alert">
        <strong>Please check the information below.</strong>
        <ul style="margin:8px 0 0;padding-left:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="max-width:720px">
    <div style="display:flex;align-items:flex-start;gap:13px;margin-bottom:22px">
        <div style="display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border-radius:13px;background:#edf4ff;color:#145dcc">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
        </div>
        <div>
            <h2 style="font-size:19px;margin:0 0 4px">Login email address</h2>
            <p class="muted" style="margin:0">Current registered email: <strong>{{ auth()->user()->email }}</strong></p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.account.email.update') }}" autocomplete="off">
        @csrf
        @method('PUT')

        <label for="email">New email address</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required maxlength="150" autocomplete="email" style="width:100%;box-sizing:border-box;margin-top:6px;margin-bottom:15px;padding:12px 13px;border:1px solid #d3dce8;border-radius:9px">

        <label for="email_confirmation">Confirm new email address</label>
        <input id="email_confirmation" type="email" name="email_confirmation" value="{{ old('email_confirmation') }}" placeholder="Enter the new address again" required maxlength="150" autocomplete="off" style="width:100%;box-sizing:border-box;margin-top:6px;margin-bottom:15px;padding:12px 13px;border:1px solid #d3dce8;border-radius:9px">

        <label for="current_password">Current account password</label>
        <input id="current_password" type="password" name="current_password" required autocomplete="current-password" placeholder="Confirm it is you" style="width:100%;box-sizing:border-box;margin-top:6px;margin-bottom:9px;padding:12px 13px;border:1px solid #d3dce8;border-radius:9px">
        <p class="muted" style="margin:0 0 20px">For your security, enter your current password before changing the email registered to this account.</p>

        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap">
            <a class="btn secondary" href="{{ route('admin.students.index') }}">Cancel</a>
            <button class="btn" type="submit">Save new email</button>
        </div>
    </form>
</div>

<div class="notice" style="max-width:720px;margin-top:18px">
    <strong>Important</strong>
    <p style="margin:5px 0 0">After saving, use the new address for future sign-ins and password reset requests. Make sure you can access the new inbox before changing it.</p>
</div>
@endsection
