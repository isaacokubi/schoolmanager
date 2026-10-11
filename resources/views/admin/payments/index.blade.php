@extends('layouts.admin')
@section('title', 'M-Pesa Payments | School Manager')
@section('page_title', 'M-Pesa payments')
@section('admin_content')
{{-- This list shows the payment result recorded by the M-Pesa callback. --}}
<div class="admin-page-head">
    <div><h1>M-Pesa fee payments</h1><p>Review STK requests and check which payments Safaricom has confirmed.</p></div>
    <div class="admin-actions"><a class="btn" href="{{ route('admin.payments.create') }}">+ New M-Pesa payment</a></div>
</div>

@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif

<div class="card">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input style="flex:1;min-width:200px" name="search" value="{{ request('search') }}" placeholder="Search student, admission, phone or receipt">
        <select name="status" style="padding:10px;border:1px solid #d3dce8;border-radius:9px">
            <option value="">All statuses</option>
            @foreach(['pending','completed','failed'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit">Filter</button>
        @if(request('search') || request('status'))<a class="btn secondary" href="{{ route('admin.payments.index') }}">Clear</a>@endif
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Date</th><th>Student</th><th>Amount (KES)</th><th>M-Pesa receipt</th><th>Status</th><th>Verification</th></tr></thead>
            <tbody>
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
