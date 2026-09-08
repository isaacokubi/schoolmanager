@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <p class="text-sm font-semibold text-emerald-600">SCHOOL FEES</p>
        <h1 class="text-3xl font-bold text-gray-900 mt-1">M-Pesa payments</h1>
        <p class="text-gray-600 mt-2">Pay school fees securely from your M-Pesa phone and track completed payments here.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
            <ul class="list-disc ml-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-bold mb-5">Pay school fees</h2>
            @if($students->isEmpty())
                <p class="text-gray-600">No learner is linked to this portal account yet.</p>
            @else
                <form method="POST" action="{{ route('portal.payments.pay') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Learner</label>
                        <select name="student_id" required class="w-full rounded-lg border-gray-300">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_number }} (Balance: KES {{ number_format((float)$student->fee_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">M-Pesa phone number</label>
                        <input name="phone" value="{{ old('phone', auth()->user()->email === '' ? '' : '') }}" required placeholder="07XXXXXXXX or 2547XXXXXXXX" class="w-full rounded-lg border-gray-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Amount (KES)</label>
                        <input name="amount" type="number" min="1" step="1" required placeholder="e.g. 5000" class="w-full rounded-lg border-gray-300">
                    </div>
                    <button class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3">Pay with M-Pesa</button>
                    <p class="text-xs text-gray-500">An STK prompt will be sent to the phone number entered above.</p>
                </form>
            @endif
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border p-6">
            <h2 class="text-lg font-bold mb-5">Payment history</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left border-b"><th class="py-3 pr-4">Reference</th><th class="py-3 pr-4">Amount</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Receipt</th><th class="py-3">Date</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr class="border-b last:border-0">
                            <td class="py-3 pr-4">{{ $payment->account_reference }}</td>
                            <td class="py-3 pr-4">KES {{ number_format((float)$payment->amount, 2) }}</td>
                            <td class="py-3 pr-4"><span class="px-2 py-1 rounded-full text-xs font-semibold {{ $payment->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($payment->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($payment->status) }}</span></td>
                            <td class="py-3 pr-4">{{ $payment->mpesa_receipt ?: '—' }}</td>
                            <td class="py-3">{{ $payment->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-gray-500">No payments recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
