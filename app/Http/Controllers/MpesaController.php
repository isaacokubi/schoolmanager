<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MpesaController extends Controller
{
    public function stkPush(Request $request, MpesaService $mpesa)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'parent_phone' => ['required', 'regex:/^\+?2547\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $reference = 'STU' . $data['student_id'] . '-' . now()->format('ymdHis');
        $paymentId = DB::table('payments')->insertGetId([
            'student_id' => $data['student_id'],
            'parent_phone' => $data['parent_phone'],
            'payment_type' => 'school_fees',
            'amount' => $data['amount'],
            'account_reference' => $reference,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $response = $mpesa->stkPush($data['parent_phone'], (float) $data['amount'], $reference);
            DB::table('payments')->where('id', $paymentId)->update([
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'M-Pesa payment prompt sent to the parent phone.');
        } catch (Throwable $e) {
            DB::table('payments')->where('id', $paymentId)->update(['status' => 'failed', 'updated_at' => now()]);
            return back()->withErrors(['mpesa' => $e->getMessage()]);
        }
    }

    public function callback(Request $request)
    {
        $callback = $request->input('Body.stkCallback', []);
        $checkoutId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;
        if (!$checkoutId) return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        $payment = DB::table('payments')->where('checkout_request_id', $checkoutId)->first();
        if (!$payment) return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        if ((int) $resultCode !== 0) {
            DB::table('payments')->where('id', $payment->id)->update(['status' => 'failed', 'updated_at' => now()]);
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $items = collect($callback['CallbackMetadata']['Item'] ?? []);
        $receipt = optional($items->firstWhere('Name', 'MpesaReceiptNumber'))['Value'] ?? null;
        $amount = optional($items->firstWhere('Name', 'Amount'))['Value'] ?? $payment->amount;
        $phone = optional($items->firstWhere('Name', 'PhoneNumber'))['Value'] ?? $payment->parent_phone;

        DB::transaction(function () use ($payment, $receipt, $amount, $phone) {
            if ($payment->status === 'completed') return;
            DB::table('payments')->where('id', $payment->id)->update([
                'status' => 'completed',
                'mpesa_receipt' => $receipt,
                'amount' => $amount,
                'parent_phone' => $phone,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
            if ($payment->student_id) {
                DB::table('students')->where('id', $payment->student_id)->decrement('fee_balance', (float) $amount);
                DB::table('students')->where('id', $payment->student_id)->where('fee_balance', '<', 0)->update(['fee_balance' => 0]);
            }
        });

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
