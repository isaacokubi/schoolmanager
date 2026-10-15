{{--
    PRESENTATION NOTE: index view.
    This Blade template renders the user interface. Blade expressions display data
    prepared by controllers, while directives control layout, loops, conditions, and forms.
    These comments are not sent to the browser as visible page content.
--}}

{{-- Reuse the shared layout so this page inherits the site's common structure and styling. --}}
@extends('layouts.admin')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('title', 'M-Pesa Payments | School Manager')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('page_title', 'M-Pesa payments')
{{-- Begin a named layout section; the parent layout inserts this content into the matching yield. --}}
@section('admin_content')
{{-- This list shows the payment result recorded by the M-Pesa callback. --}}
<div class="admin-page-head">
    <div><h1>M-Pesa fee payments</h1><p>Review STK requests and check which payments Safaricom has confirmed.</p></div>
    <div class="admin-actions"><a class="btn" href="{{ route('admin.payments.create') }}">+ New M-Pesa payment</a></div>
</div>

{{-- Render the following content only when this condition is true. --}}
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
{{-- Render the following content only when this condition is true. --}}
@if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif

<div class="card">
    {{-- Begin a form that sends the user's input to the route and HTTP method specified in its attributes. --}}
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input style="flex:1;min-width:200px" name="search" value="{{ request('search') }}" placeholder="Search student, admission, phone or receipt">
        <select name="status" style="padding:10px;border:1px solid #d3dce8;border-radius:9px">
            <option value="">All statuses</option>
            {{-- Repeat this markup for each item in the supplied collection. --}}
            @foreach(['pending','completed','failed'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Filter</button>
        {{-- Render the following content only when this condition is true. --}}
        @if(request('search') || request('status'))<a class="btn secondary" href="{{ route('admin.payments.index') }}">Clear</a>@endif
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Student</th><th>Amount (KES)</th><th>M-Pesa receipt</th><th>Status</th><th>Verification</th></tr></thead>
            <tbody>
            {{-- Loop through the supplied collection and provide an empty-state branch if it contains no rows. --}}
            @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->created_at }}</td>
                    <td><strong>{{ $payment->student_name ?: '—' }}</strong><br><small>{{ $payment->admission_number ?: '—' }}</small></td>
                    <td>{{ number_format((float)$payment->amount, 2) }}</td>
                    <td>{{ $payment->mpesa_receipt ?: ($payment->account_reference ?: 'Waiting for callback') }}</td>
                    <td>{{ ucfirst($payment->status ?: 'pending') }}</td>
                    <td>{{ ucfirst($payment->verification_status ?: 'pending') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No M-Pesa payments yet. Select “New M-Pesa payment” to send the first STK prompt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $payments->links() }}</div>
</div>
@endsection
