<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MpesaController extends Controller
{
    public function stkPush(Request $request, MpesaService $mpesa)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'parent_phone' => ['required', 'regex:/^(?:0?7|2547|\+2547)\d{8}$/'],
            'amount' => ['required', 'integer', 'min:1', 'max:1500000'],
        ]);

        $phone = preg_replace('/^\+/', '', trim($data['parent_phone']));
        if (preg_match('/^07\d{8}$/', $phone)) $phone = '254' . substr($phone, 1);
        elseif (preg_match('/^7\d{8}$/', $phone)) $phone = '254' . $phone;

        $reference = 'STU' . $data['student_id'] . '-' . now()->format('ymdHis');
        $paymentId = DB::table('payments')->insertGetId([
            'student_id' => $data['student_id'],
            'user_id' => auth()->id(),
            'payer_role' => auth()->user()->role ?? null,
            'payer_name' => auth()->user()->name ?? null,
            'parent_phone' => $phone,
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => $data['amount'],
            'account_reference' => $reference,
            'status' => 'pending',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $response = $mpesa->stkPush($phone, (float) $data['amount'], $reference);
            DB::table('payments')->where('id', $paymentId)->update([
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'M-Pesa payment prompt sent to the parent phone.');
        } catch (Throwable $e) {
            DB::table('payments')->where('id', $paymentId)->update([
                'status' => 'failed',
                'verification_status' => 'rejected',
                'failure_reason' => 'M-Pesa request could not be started.',
                'updated_at' => now(),
            ]);
            report($e);
            return back()->withErrors(['mpesa' => 'The M-Pesa request could not be started.']);
        }
    }

    public function callback(Request $request)
    {
        $callback = $request->input('Body.stkCallback', []);
        $checkoutId = $callback['CheckoutRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;
        if (!$checkoutId) return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

        $result = DB::transaction(function () use ($checkoutId, $resultCode, $callback) {
            $payment = DB::table('payments')->where('checkout_request_id', $checkoutId)->lockForUpdate()->first();
            if (!$payment) return ['status' => 'unknown'];
            if ($payment->status === 'completed') return ['status' => 'already_completed'];

            if ((int) $resultCode !== 0) {
                DB::table('payments')->where('id', $payment->id)->update(['status'=>'failed','verification_status'=>'rejected','failure_reason'=>'M-Pesa transaction was declined or cancelled.','updated_at'=>now()]);
                DB::table('payment_audits')->insert(['payment_id'=>$payment->id,'event'=>'callback_failed','details'=>json_encode(['result_code'=>$resultCode]),'created_at'=>now(),'updated_at'=>now()]);
                return ['status' => 'failed'];
            }

            $items = collect($callback['CallbackMetadata']['Item'] ?? []);
            $receipt = optional($items->firstWhere('Name', 'MpesaReceiptNumber'))['Value'] ?? null;
            $amount = optional($items->firstWhere('Name', 'Amount'))['Value'] ?? null;
            $phone = optional($items->firstWhere('Name', 'PhoneNumber'))['Value'] ?? $payment->parent_phone;

            if (!$receipt || $amount === null) {
                DB::table('payments')->where('id',$payment->id)->update(['status'=>'failed','verification_status'=>'rejected','failure_reason'=>'M-Pesa callback did not contain a receipt and amount.','updated_at'=>now()]);
                DB::table('payment_audits')->insert(['payment_id'=>$payment->id,'event'=>'callback_rejected','details'=>json_encode(['reason'=>'missing_receipt_or_amount']),'created_at'=>now(),'updated_at'=>now()]);
                return ['status'=>'rejected'];
            }
            if (abs((float)$amount-(float)$payment->amount)>0.0001) {
                DB::table('payments')->where('id',$payment->id)->update(['status'=>'failed','verification_status'=>'rejected','failure_reason'=>'M-Pesa callback amount does not match the requested payment amount.','updated_at'=>now()]);
                DB::table('payment_audits')->insert(['payment_id'=>$payment->id,'event'=>'callback_rejected','details'=>json_encode(['reason'=>'amount_mismatch','expected'=>(float)$payment->amount,'received'=>(float)$amount]),'created_at'=>now(),'updated_at'=>now()]);
                return ['status'=>'rejected'];
            }
            $duplicate = DB::table('payments')->where('mpesa_receipt',$receipt)->where('id','<>',$payment->id)->first();
            if ($duplicate) {
                DB::table('payments')->where('id',$payment->id)->update(['status'=>'failed','verification_status'=>'rejected','failure_reason'=>'The M-Pesa receipt is already associated with another payment.','updated_at'=>now()]);
                DB::table('payment_audits')->insert(['payment_id'=>$payment->id,'event'=>'callback_rejected','details'=>json_encode(['reason'=>'duplicate_receipt','receipt'=>$receipt]),'created_at'=>now(),'updated_at'=>now()]);
                return ['status'=>'duplicate'];
            }

            DB::table('payments')->where('id',$payment->id)->update(['status'=>'completed','verification_status'=>'verified','failure_reason'=>null,'mpesa_receipt'=>$receipt,'amount'=>$amount,'parent_phone'=>$phone,'paid_at'=>now(),'updated_at'=>now()]);
            if ($payment->student_id) {
                DB::table('students')->where('id',$payment->student_id)->decrement('fee_balance',(float)$amount);
                DB::table('students')->where('id',$payment->student_id)->where('fee_balance','<',0)->update(['fee_balance'=>0]);
            }
            DB::table('payment_audits')->insert(['payment_id'=>$payment->id,'event'=>'payment_verified','details'=>json_encode(['receipt'=>$receipt,'amount'=>(float)$amount,'phone'=>$phone]),'created_at'=>now(),'updated_at'=>now()]);
            return ['status'=>'completed','payment'=>$payment,'amount'=>$amount,'receipt'=>$receipt];
        });

        if ($result['status']==='completed' && !empty($result['payment']->student_id)) {
            $payment=$result['payment'];
            $student=DB::table('students')->where('id',$payment->student_id)->first();
            $recipient=$payment->user_id?DB::table('users')->where('id',$payment->user_id)->value('email'):null;
            if (!$recipient && $student && $student->parent_id) $recipient=DB::table('parents')->where('id',$student->parent_id)->value('email');
            if ($recipient && $student) {
                try { Mail::send('emails.payment-confirmation',['payment'=>(object)array_merge((array)$payment,['amount'=>$result['amount'],'mpesa_receipt'=>$result['receipt']]),'student'=>$student,'recipientName'=>$payment->payer_name?:'Parent/Guardian'],function($message)use($recipient){$message->to($recipient)->subject('School fee payment received');}); } catch(Throwable $e){ report($e); }
            }
        }
        return response()->json(['ResultCode'=>0,'ResultDesc'=>'Accepted']);
    }
}
