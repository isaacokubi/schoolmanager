@extends('layouts.admin')
@section('title', 'M-Pesa Fee Payment | School Manager')
@section('page_title', 'M-Pesa fee payment')
@section('admin_content')
{{-- Feature 2: ask Safaricom to send an STK prompt to the guardian's phone. --}}
<div class="admin-page-head">
    <div>
        <h1>Pay school fees with M-Pesa</h1>
        <p>Choose a learner, enter the guardian's Safaricom number and send a payment prompt.</p>
    </div>
    <a class="btn secondary" href="{{ route('admin.payments.index') }}">Payment history</a>
</div>

@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif

<div class="card" style="max-width:680px">
    <form method="post" action="{{ route('admin.payments.mpesa') }}">
        @csrf
        <label>Student
            <select name="student_id" required>
                <option value="">Select a student</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" {{ (string)old('student_id') === (string)$student->id ? 'selected' : '' }}>
                        {{ $student->admission_number }} — {{ $student->name }} (balance KES {{ number_format((float)$student->fee_balance, 2) }})
                    </option>
                @endforeach
            </select>
        </label>
        <label>Guardian's M-Pesa phone number
            <input name="parent_phone" required inputmode="tel" autocomplete="tel" placeholder="0712345678 or 254712345678" value="{{ old('parent_phone') }}">
        </label>
        <label>Amount (KES)
            <input type="number" name="amount" required min="1" max="1500000" step="1" value="{{ old('amount') }}">
        </label>
        <button class="btn" type="submit">Send M-Pesa prompt</button>
    </form>
    <p class="muted" style="margin-top:14px">The payment stays pending until Safaricom sends a callback containing a valid receipt and matching amount. Never mark a payment as paid just because the prompt was sent.</p>
</div>
@endsection
