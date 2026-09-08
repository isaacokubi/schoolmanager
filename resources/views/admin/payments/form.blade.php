@extends('layouts.app')
@section('title','Record Payment | School Manager')
@section('body')
<div class="admin-shell">
<header class="admin-top"><strong>Payments</strong><a style="color:#fff" href="{{ route('admin.payments.index') }}">Payment history</a></header>
<main class="admin-main">
@if(session('success'))<div class="card" style="border-left:4px solid #16a34a">{{ session('success') }}</div>@endif
@if($errors->any())<div class="card" style="border-left:4px solid #dc2626"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid">
<div class="card"><h2>Record payment</h2><form method="post" action="{{ route('admin.payments.store') }}">@csrf
<label>Student<select name="student_id"><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->admission_number }} — {{ $student->name }}</option>@endforeach</select></label>
<label>Parent Phone<input name="parent_phone" placeholder="+2547XXXXXXXX"></label>
<label>Payment Type<input name="payment_type" required value="school_fees"></label>
<label>Amount (KES)<input type="number" name="amount" required min="1" step="0.01"></label>
<label>Account Reference<input name="account_reference"></label>
<label>M-Pesa Receipt<input name="mpesa_receipt"></label>
<label>Status<select name="status"><option value="completed">Completed</option><option value="pending">Pending</option><option value="failed">Failed</option></select></label>
<br><button class="btn" type="submit">Record payment</button></form></div>
<div class="card"><h2>Send M-Pesa STK Push</h2><p>Send a payment prompt directly to the parent's Safaricom phone.</p><form method="post" action="{{ route('admin.payments.mpesa') }}">@csrf
<label>Student<select name="student_id" required><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->admission_number }} — {{ $student->name }}</option>@endforeach</select></label>
<label>Parent Phone<input name="parent_phone" required placeholder="07XXXXXXXX or +2547XXXXXXXX"></label>
<label>Amount (KES)<input type="number" name="amount" required min="1" step="1"></label>
<br><button class="btn" type="submit">Send STK prompt</button></form>
<p style="margin-top:1rem;font-size:.9rem">M-Pesa credentials are read from server environment variables and are never stored in the repository.</p></div>
</div></main></div>
@endsection
