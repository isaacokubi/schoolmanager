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
    <form id="mpesa-stk-form" method="post" action="{{ route('admin.payments.mpesa') }}">
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
        <button id="mpesa-stk-submit" class="btn" type="submit" style="display:inline-flex;align-items:center;justify-content:center;gap:10px;min-width:210px">
            <span id="mpesa-stk-submit-label">Send M-Pesa prompt</span>
            <span id="mpesa-stk-submit-loading" hidden>
                <span class="mpesa-stk-spinner" aria-hidden="true"></span>
                Sending prompt…
            </span>
        </button>
        <p id="mpesa-stk-live-status" class="muted" role="status" aria-live="polite" hidden style="margin-top:12px">
            Connecting to Safaricom. Please keep this page open and do not submit again.
        </p>
    </form>
    <p class="muted" style="margin-top:14px">The payment stays pending until Safaricom sends a callback containing a valid receipt and matching amount. Never mark a payment as paid just because the prompt was sent.</p>
</div>

<style>
    .mpesa-stk-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        vertical-align: -2px;
        animation: mpesa-stk-spin .7s linear infinite;
    }
    @keyframes mpesa-stk-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        .mpesa-stk-spinner { animation-duration: 1.8s; }
    }
</style>
<script>
(function () {
    var form = document.getElementById('mpesa-stk-form');
    var button = document.getElementById('mpesa-stk-submit');
    var label = document.getElementById('mpesa-stk-submit-label');
    var loading = document.getElementById('mpesa-stk-submit-loading');
    var status = document.getElementById('mpesa-stk-live-status');
    var submitting = false;

    if (!form || !button || !label || !loading || !status) return;

    form.addEventListener('submit', function (event) {
        if (submitting) {
            event.preventDefault();
            return;
        }

        // Browser validation runs before this event, so invalid forms stay interactive.
        submitting = true;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        label.hidden = true;
        loading.hidden = false;
        status.hidden = false;
    });
})();
</script>
@endsection
