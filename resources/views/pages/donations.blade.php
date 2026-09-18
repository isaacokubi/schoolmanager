@extends('layouts.app')
@section('title','Support the School')
@section('body')
<div class="page-hero"><div class="container"><p class="eyebrow">Community support</p><h1>Make a difference</h1><p>Support {{ $settings['school_name'] }} through a transparent pledge that the school team can follow up and process.</p></div></div>
<section class="section"><div class="container" style="max-width:820px"><div class="card">
@if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('donations.store') }}" class="grid-form">@csrf
<label>Name<input name="name" required maxlength="160"></label><label>Email<input type="email" name="email" maxlength="190"></label>
<label>Phone<input name="phone" maxlength="40"></label><label>Amount (KES)<input type="number" min="0" step="0.01" name="amount"></label>
<label style="grid-column:1/-1">Purpose<select name="purpose"><option>General support</option><option>Learning resources</option><option>Meals and welfare</option><option>Technology</option><option>Scholarship support</option><option>Infrastructure</option></select></label>
<label style="grid-column:1/-1">Message<textarea name="message" rows="5" maxlength="2000"></textarea></label>
<button class="btn primary" type="submit">Submit support pledge</button>
</form></div></div></section>
@endsection