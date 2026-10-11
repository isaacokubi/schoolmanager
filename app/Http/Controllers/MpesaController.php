<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use App\Jobs\SendPaymentReceiptJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MpesaController extends Controller
{
    public function stkPush(Request $request, MpesaService $mpesa)
    {
        // Validate the learner, Kenyan mobile number, and whole-KES payment amount.
        // MpesaService is injected by Laravel and handles Daraja API communication.
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'parent_phone' => ['required', 'regex:/^(?:0?7|2547|\+2547)\d{8}$/'],
            'amount' => ['required', 'integer', 'min:1', 'max:1500000'],
        ]);

        // Read the learner's current balance before accepting a payment request.
        $student = DB::table('students')->where('id', $data['student_id'])->first();
        if (!$student || (int) $data['amount'] > max(0, (int) floor((float) $student->fee_balance))) {
            return back()->withErrors(['amount' => 'The payment amount cannot exceed the learner\'s outstanding whole-KES fee balance.']);
        }

        $phone = preg_replace('/^\+/', '', trim($data['parent_phone']));
        if (preg_match('/^07\d{8}$/', $phone)) $phone = '254' . substr($phone, 1);
        elseif (preg_match('/^7\d{8}$/', $phone)) $phone = '254' . $phone;

        // Give the request a traceable account reference with a random suffix.
        // The database record starts as pending: starting an STK prompt is not proof of payment.
        $reference = 'STU' . $data['student_id'] . '-' . now()->format('ymdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
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
            // Ask Safaricom to send the STK prompt and save its request IDs for reconciliation.
            $response = $mpesa->stkPush($phone, (float) $data['amount'], $reference);
            DB::table('payments')->where('id', $paymentId)->update([
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'M-Pesa payment request accepted by Safaricom. Complete it on the phone if prompted; payment will be confirmed automatically.');
        } catch (Throwable $e) {
            // An initiation error is recorded as failed, never as a completed/paid transaction.
            DB::table('payments')->where('id', $paymentId)->update([
                'status' => 'failed',
                'verification_status' => 'rejected',
                'failure_reason' => 'M-Pesa request could not be started.',
                'updated_at' => now(),
            ]);
            report($e);

            // Show safe, actionable setup/network diagnostics without exposing
            // credentials, access tokens, or full provider response bodies.
            $exceptionMessage = $e->getMessage();
            $userMessage = 'The M-Pesa request could not be started. Open storage/logs/laravel.log for the exact error, then retry.';

            if (strpos($exceptionMessage, 'GuzzleHttp\\HandlerStack') !== false
                || strpos($exceptionMessage, 'GuzzleHttp\\Client') !== false) {
                $userMessage = 'The M-Pesa HTTP dependency is missing. Run "composer install" in the project directory, then retry.';
            } elseif (strpos($exceptionMessage, 'consumer credentials are not configured') !== false
                || strpos($exceptionMessage, 'shortcode/passkey are not configured') !== false) {
                $userMessage = 'M-Pesa credentials are missing. Set MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_SHORTCODE and MPESA_PASSKEY in .env, then clear the config cache.';
            } elseif (stripos($exceptionMessage, 'callback URL') !== false) {
                $userMessage = 'The M-Pesa callback URL must be a valid, publicly reachable URL. For local development, expose this app through a secure tunnel and set MPESA_CALLBACK_URL in .env.';
            } elseif (stripos($exceptionMessage, 'OAuth failed (HTTP 401)') !== false
                || stripos($exceptionMessage, 'OAuth failed (HTTP 403)') !== false) {
                $userMessage = 'Safaricom rejected the M-Pesa API credentials. Verify the consumer key/secret belong to the selected MPESA_ENV app (sandbox or production).';
            } elseif (stripos($exceptionMessage, 'OAuth failed (HTTP 400)') !== false) {
                $userMessage = 'Safaricom could not issue an M-Pesa access token. Verify the Daraja app credentials and MPESA_ENV setting.';
            } elseif (stripos($exceptionMessage, 'STK request failed (HTTP 401)') !== false
                || stripos($exceptionMessage, 'STK request failed (HTTP 403)') !== false) {
                $userMessage = 'Safaricom rejected the STK request authorization. Check the Daraja credentials, shortcode, passkey and selected environment.';
            } elseif (stripos($exceptionMessage, 'M-Pesa rejected the STK request:') !== false
                || stripos($exceptionMessage, 'M-Pesa STK request failed (HTTP 400)') !== false) {
                $userMessage = 'Safaricom rejected the STK request. Check the shortcode, passkey, transaction type, phone number and amount; see storage/logs/laravel.log for the provider error.';
            } elseif (stripos($exceptionMessage, 'Unable to connect to the M-Pesa') !== false
                || stripos($exceptionMessage, 'cURL error') !== false
                || stripos($exceptionMessage, 'Connection timed out') !== false) {
                $userMessage = 'Could not connect to Safaricom. Check your internet connection, PHP cURL/OpenSSL support and Daraja service availability, then retry.';
            } elseif (stripos($exceptionMessage, 'callback URL') !== false) {
                $userMessage = 'The M-Pesa callback URL must be a valid, publicly reachable URL. Configure MPESA_CALLBACK_URL and retry.';
            }

            return back()->withErrors(['mpesa' => $userMessage]);
        }
    }

    /**
     * Reconcile an existing pending STK payment with Safaricom.
     *
     * A query success does not manufacture a payment receipt. The payment
     * remains pending until a valid callback supplies the receipt and amount.
     * Known transient/incomplete query responses also remain pending so a
     * temporary Daraja state cannot incorrectly turn a legitimate retry into
     * a permanent failed payment.
     */
    public function query(Request $request, MpesaService $mpesa, int $paymentId)
    {
        // Find the existing payment; this endpoint checks status rather than creating a payment.
        $payment = DB::table('payments')->where('id', $paymentId)->first();

        if (!$payment) {
            return back()->withErrors(['mpesa' => 'Payment was not found.']);
        }

        if (!$payment->checkout_request_id) {
            return back()->withErrors(['mpesa' => 'This payment has no CheckoutRequestID to query.']);
        }

        if ($payment->status === 'completed' && $payment->verification_status === 'verified') {
            return back()->with('success', 'This M-Pesa payment is already verified.');
        }

        try {
            // Query Daraja with the CheckoutRequestID saved during STK initiation.
            $result = $mpesa->stkQuery($payment->checkout_request_id);
            $resultCode = $result['ResultCode'] ?? null;
            $resultDesc = (string) ($result['ResultDesc'] ?? 'No result description returned.');
            $resultCodeString = $resultCode === null ? null : (string) $resultCode;

            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'stk_query',
                'details' => json_encode([
                    'result_code' => $resultCodeString,
                    'result_desc' => $resultDesc,
                    'checkout_request_id' => $payment->checkout_request_id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($resultCodeString === '0') {
                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'pending',
                    'verification_status' => 'pending',
                    'failure_reason' => 'STK Query confirms Safaricom processed the request, but final payment metadata/receipt is still required.',
                    'updated_at' => now(),
                ]);

                return back()->with(
                    'success',
                    'Safaricom reports the STK request was processed. The payment remains pending until the verified M-Pesa callback supplies the receipt and amount.'
                );
            }

            // Daraja can return an accepted/indeterminate query state before the
            // asynchronous callback has supplied the final transaction metadata.
            // In particular, E3008 has been observed in this application's sandbox
            // tests when no handset prompt/receipt was delivered. Do not label that
            // state as a permanent payment failure; leave it pending for retry/query.
            if ($resultCodeString === 'E3008') {
                $message = 'M-Pesa is still awaiting a final transaction result. The payment remains pending; retry the status check shortly or start a new request if no phone prompt appears.';

                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'pending',
                    'verification_status' => 'pending',
                    'failure_reason' => 'M-Pesa STK Query returned E3008: ' . $resultDesc,
                    'updated_at' => now(),
                ]);

                return back()->with('success', $message);
            }

            DB::table('payments')->where('id', $payment->id)->update([
                'status' => 'failed',
                'verification_status' => 'rejected',
                'failure_reason' => 'M-Pesa STK Query: ' . $resultDesc,
                'updated_at' => now(),
            ]);

            return back()->withErrors([
                'mpesa' => 'Safaricom reports that this M-Pesa request was not completed: ' . $resultDesc,
            ]);
        } catch (Throwable $e) {
            report($e);

            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'stk_query_error',
                'details' => json_encode([
                    'message' => $e->getMessage(),
                    'checkout_request_id' => $payment->checkout_request_id,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->withErrors([
                'mpesa' => 'The M-Pesa status could not be queried right now.',
            ]);
        }
    }

    public function callback(Request $request)
    {
        // Safaricom expects a fast 200 response. Keep the raw callback in the
        // application log for diagnostics, but never log credentials or tokens.
        $payload = $request->all();
        $callback = data_get($payload, 'Body.stkCallback', []);
        $checkoutId = $callback['CheckoutRequestID'] ?? null;
        $merchantId = $callback['MerchantRequestID'] ?? null;
        $resultCode = $callback['ResultCode'] ?? null;

        logger()->info('M-Pesa STK callback received', [
            'checkout_request_id' => $checkoutId,
            'merchant_request_id' => $merchantId,
            'result_code' => $resultCode,
            'has_callback_metadata' => !empty($callback['CallbackMetadata']['Item']),
        ]);

        if (!$checkoutId && !$merchantId) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $result = DB::transaction(function () use ($checkoutId, $merchantId, $resultCode, $callback) {
            $paymentQuery = DB::table('payments')->lockForUpdate();

            if ($checkoutId) {
                $paymentQuery->where('checkout_request_id', $checkoutId);
            } else {
                $paymentQuery->where('merchant_request_id', $merchantId);
            }

            $payment = $paymentQuery->first();

            if (!$payment && $checkoutId && $merchantId) {
                $payment = DB::table('payments')
                    ->where('merchant_request_id', $merchantId)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$payment) {
                logger()->warning('M-Pesa callback could not be matched to a payment', [
                    'checkout_request_id' => $checkoutId,
                    'merchant_request_id' => $merchantId,
                ]);
                return ['status' => 'unknown'];
            }

            if ($payment->status === 'completed') {
                return ['status' => 'already_completed'];
            }

            if ((int) $resultCode !== 0) {
                $description = (string) ($callback['ResultDesc'] ?? 'M-Pesa transaction was declined or cancelled.');
                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'failed',
                    'verification_status' => 'rejected',
                    'failure_reason' => $description,
                    'updated_at' => now(),
                ]);
                DB::table('payment_audits')->insert([
                    'payment_id' => $payment->id,
                    'event' => 'callback_failed',
                    'details' => json_encode([
                        'result_code' => $resultCode,
                        'result_desc' => $description,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['status' => 'failed'];
            }

            $items = collect(data_get($callback, 'CallbackMetadata.Item', []));
            $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $amount = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
            $phone = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;
            $transactionDate = $items->firstWhere('Name', 'TransactionDate')['Value'] ?? null;

            // A successful STK callback without CallbackMetadata is not proof of
            // payment. Do not turn the payment into a failed payment merely because
            // the callback was incomplete; leave it pending so a valid callback or
            // STK status query can complete it later.
            if (!$receipt || $amount === null) {
                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'pending',
                    'verification_status' => 'pending',
                    'failure_reason' => 'M-Pesa callback was accepted but did not contain payment metadata yet.',
                    'updated_at' => now(),
                ]);
                DB::table('payment_audits')->insert([
                    'payment_id' => $payment->id,
                    'event' => 'callback_incomplete',
                    'details' => json_encode([
                        'reason' => 'missing_receipt_or_amount',
                        'result_code' => $resultCode,
                        'result_desc' => $callback['ResultDesc'] ?? null,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['status' => 'pending'];
            }

            if (abs((float) $amount - (float) $payment->amount) > 0.0001) {
                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'failed',
                    'verification_status' => 'rejected',
                    'failure_reason' => 'M-Pesa callback amount does not match the requested payment amount.',
                    'updated_at' => now(),
                ]);
                DB::table('payment_audits')->insert([
                    'payment_id' => $payment->id,
                    'event' => 'callback_rejected',
                    'details' => json_encode([
                        'reason' => 'amount_mismatch',
                        'expected' => (float) $payment->amount,
                        'received' => (float) $amount,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['status' => 'rejected'];
            }

            $normalisePhone = static function ($value) {
                $value = preg_replace('/^\+/', '', trim((string) $value));
                if (preg_match('/^07\d{8}$/', $value)) return '254' . substr($value, 1);
                if (preg_match('/^7\d{8}$/', $value)) return '254' . $value;
                return $value;
            };

            if ($phone) {
                $callbackPhone = $normalisePhone($phone);
                $paymentPhone = $normalisePhone($payment->parent_phone);
                if ($paymentPhone && $callbackPhone && $paymentPhone !== $callbackPhone) {
                    DB::table('payments')->where('id', $payment->id)->update([
                        'status' => 'failed',
                        'verification_status' => 'rejected',
                        'failure_reason' => 'M-Pesa callback phone number does not match the payment phone number.',
                        'updated_at' => now(),
                    ]);
                    DB::table('payment_audits')->insert([
                        'payment_id' => $payment->id,
                        'event' => 'callback_rejected',
                        'details' => json_encode([
                            'reason' => 'phone_mismatch',
                            'expected' => $paymentPhone,
                            'received' => $callbackPhone,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    return ['status' => 'rejected'];
                }
                $phone = $callbackPhone;
            } else {
                $phone = $normalisePhone($payment->parent_phone);
            }

            $duplicate = DB::table('payments')
                ->where('mpesa_receipt', $receipt)
                ->where('id', '<>', $payment->id)
                ->first();
            if ($duplicate) {
                DB::table('payments')->where('id', $payment->id)->update([
                    'status' => 'failed',
                    'verification_status' => 'rejected',
                    'failure_reason' => 'The M-Pesa receipt is already associated with another payment.',
                    'updated_at' => now(),
                ]);
                DB::table('payment_audits')->insert([
                    'payment_id' => $payment->id,
                    'event' => 'callback_rejected',
                    'details' => json_encode(['reason' => 'duplicate_receipt', 'receipt' => $receipt]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return ['status' => 'duplicate'];
            }

            DB::table('payments')->where('id', $payment->id)->update([
                'status' => 'completed',
                'verification_status' => 'verified',
                'failure_reason' => null,
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

            DB::table('payment_audits')->insert([
                'payment_id' => $payment->id,
                'event' => 'payment_verified',
                'details' => json_encode([
                    'receipt' => $receipt,
                    'amount' => (float) $amount,
                    'phone' => $phone,
                    'transaction_date' => $transactionDate,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'completed', 'payment' => $payment, 'amount' => $amount, 'receipt' => $receipt];
        });

        if ($result['status'] === 'completed') {
            SendPaymentReceiptJob::dispatch($result['payment']->id)
                ->afterCommit();

            DB::table('payment_audits')->insert([
                'payment_id' => $result['payment']->id,
                'event' => 'receipt_queued',
                'details' => json_encode([
                    'receipt_number' => $result['receipt'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
