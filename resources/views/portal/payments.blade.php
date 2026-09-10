@extends('layouts.app')

@section('title', 'M-Pesa Payments | '.config('app.name'))
@section('body')
<div class="payment-shell">
    <header class="payment-header">
        <a href="{{ route('portal.dashboard') }}" class="back-link">← Dashboard</a>
        <div><span class="payment-kicker">School fees</span><h1>M-Pesa payments</h1><p>Pay school fees securely and monitor every payment linked to your learner account.</p></div>
        <span class="portal-role">{{ ucfirst(auth()->user()->role) }} portal</span>
    </header>

    @if(session('success'))
        <div class="payment-alert success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="payment-alert error"><strong>Payment could not be started.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="payment-grid">
        <section class="payment-card payment-form-card">
            <div class="card-heading"><div><span class="payment-kicker">Pay now</span><h2>Pay with M-Pesa</h2></div><span class="mpesa-badge">M-PESA</span></div>
            @if($students->isEmpty())
                <div class="empty-state"><strong>No learner linked</strong><span>Your account does not currently have a learner record available for payment.</span></div>
            @else
                <form method="POST" action="{{ route('portal.payments.pay') }}" class="payment-form">
                    @csrf
                    <label>Learner
                        <select name="student_id" required>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }} · Balance KES {{ number_format((float)$student->fee_balance, 2) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>M-Pesa phone number
                        <input name="phone" value="{{ old('phone') }}" required inputmode="tel" autocomplete="tel" placeholder="07XXXXXXXX or 2547XXXXXXXX">
                        <small>Use the phone that should receive the M-Pesa STK prompt.</small>
                    </label>
                    <label>Amount (KES)
                        <input name="amount" type="number" min="1" max="1500000" step="1" value="{{ old('amount') }}" required placeholder="e.g. 5000">
                    </label>
                    <button type="submit">Send M-Pesa prompt</button>
                    <p class="form-note">You will receive an STK prompt. Enter your M-Pesa PIN to authorize the transaction.</p>
                </form>
            @endif
        </section>

        <section class="payment-card">
            <div class="card-heading"><div><span class="payment-kicker">Account activity</span><h2>Payment history</h2></div><span class="history-count">{{ $payments->count() }} shown</span></div>
            <div class="history-list">
                @forelse($payments as $payment)
                    <article class="history-row">
                        <div class="history-main"><strong>{{ $payment->account_reference ?: 'School fee payment' }}</strong><span>{{ $payment->mpesa_receipt ?: 'Awaiting M-Pesa receipt' }}</span></div>
                        <div class="history-amount"><strong>KES {{ number_format((float)$payment->amount, 2) }}</strong><span>{{ \Carbon\Carbon::parse($payment->created_at)->format('d M Y, H:i') }}</span></div>
                        <span class="status {{ $payment->status === 'completed' ? 'paid' : ($payment->status === 'failed' ? 'failed' : 'pending') }}">{{ ucfirst($payment->status) }}</span>
                    </article>
                @empty
                    <div class="empty-state"><strong>No payments yet</strong><span>Completed and pending M-Pesa transactions will appear here.</span></div>
                @endforelse
            </div>
        </section>
    </div>
</div>
<style>
.payment-shell{min-height:100vh;background:#f4f7fb;padding:34px 4% 60px;color:#152238}.payment-header{max-width:1240px;margin:0 auto 22px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:24px}.back-link{color:#1769df;text-decoration:none;font-weight:800;font-size:13px}.payment-kicker{display:block;color:#1769df;text-transform:uppercase;letter-spacing:.12em;font-weight:900;font-size:10px}.payment-header h1{margin:4px 0 4px;font-size:clamp(28px,4vw,42px);letter-spacing:-.04em}.payment-header p{margin:0;color:#718097;font-size:13px}.portal-role,.history-count{background:#edf4ff;color:#1769df;border-radius:999px;padding:7px 11px;font-size:10px;font-weight:900;white-space:nowrap}.payment-alert{max-width:1240px;margin:0 auto 18px;padding:14px 16px;border-radius:12px;border:1px solid}.payment-alert.success{background:#eaf8f0;border-color:#bde5cd;color:#176b3a}.payment-alert.error{background:#fff0f0;border-color:#f0c3c7;color:#a51d2d}.payment-alert ul{margin:5px 0 0 18px}.payment-grid{max-width:1240px;margin:0 auto;display:grid;grid-template-columns:minmax(330px,.8fr) 1.4fr;gap:18px}.payment-card{background:#fff;border:1px solid #e1e8f1;border-radius:18px;padding:22px;box-shadow:0 9px 30px rgba(20,40,70,.05);min-width:0}.card-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;margin-bottom:20px}.card-heading h2{margin:3px 0 0;font-size:20px;letter-spacing:-.02em}.mpesa-badge{background:#eaf8f0;color:#176b3a;border:1px solid #c7e9d5;border-radius:8px;padding:7px 9px;font-size:10px;font-weight:900}.payment-form{display:grid;gap:15px}.payment-form label{display:grid;gap:7px;font-size:12px;font-weight:850}.payment-form input,.payment-form select{width:100%;box-sizing:border-box;border:1px solid #d4deea;border-radius:10px;padding:12px 13px;background:#fff;color:#152238;outline:none}.payment-form input:focus,.payment-form select:focus{border-color:#72a6ed;box-shadow:0 0 0 4px rgba(23,105,223,.08)}.payment-form small{color:#8995a7;font-weight:500;font-size:10px}.payment-form button{border:0;border-radius:11px;padding:13px 16px;background:#159447;color:#fff;font-weight:900;cursor:pointer}.payment-form button:hover{background:#117b3a}.form-note{margin:0;color:#7b8799;font-size:10px;line-height:1.6}.history-list{display:grid}.history-row{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:18px;padding:15px 0;border-bottom:1px solid #edf0f5}.history-row:last-child{border-bottom:0}.history-main strong,.history-amount strong{display:block;font-size:12px}.history-main span,.history-amount span{display:block;color:#8b97a8;font-size:10px;margin-top:3px}.history-amount{text-align:right}.status{border-radius:999px;padding:6px 9px;font-size:10px;font-weight:900;white-space:nowrap}.status.paid{background:#eaf8f0;color:#176b3a}.status.failed{background:#fff0f0;color:#a51d2d}.status.pending{background:#fff6df;color:#966300}.empty-state{border:1px dashed #ccd7e5;border-radius:13px;padding:24px;text-align:center;color:#7c899c;background:#fafbfd;font-size:12px}.empty-state strong,.empty-state span{display:block}.empty-state span{margin-top:4px;font-size:11px}@media(max-width:900px){.payment-header{grid-template-columns:1fr;gap:8px}.portal-role{justify-self:start}.payment-grid{grid-template-columns:1fr}}@media(max-width:600px){.payment-shell{padding:22px 5% 40px}.payment-card{padding:17px}.history-row{grid-template-columns:1fr;gap:7px}.history-amount{text-align:left}.status{justify-self:start}}
</style>
@endsection
