<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PortalPaymentController extends Controller
{
    private const HISTORY_PAGE_SIZE = 20;

    public function index(Request $request)
    {
        $students = $this->accessibleStudents($request->user());
        $studentIds = $students->pluck('id');
        $payments = $studentIds->isEmpty()
            ? DB::table('payments')->whereRaw('1 = 0')->paginate(self::HISTORY_PAGE_SIZE, ['*'], 'payments_page')
            : DB::table('payments')->whereIn('student_id', $studentIds)->orderByDesc('id')->paginate(self::HISTORY_PAGE_SIZE, ['*'], 'payments_page');
        $payments->withQueryString();

        return view('portal.payments', compact('students', 'payments'));
    }

    public function pay(Request $request, MpesaService $mpesa)
    {
        $user = $request->user();
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'phone' => ['required', 'regex:/^(?:0?7|2547|\+2547)\d{8}$/'],
            'amount' => ['required', 'integer', 'min:1', 'max:1500000'],
        ]);

        $student = $this->accessibleStudents($user)->firstWhere('id', (int) $data['student_id']);
        if (!$student) abort(403, 'You are not authorised to pay fees for this learner.');

        $phone = preg_replace('/^\+/', '', trim($data['phone']));
        if (preg_match('/^07\d{8}$/', $phone)) $phone = '254' . substr($phone, 1);
        elseif (preg_match('/^7\d{8}$/', $phone)) $phone = '254' . $phone;

        $amount = (int) $data['amount'];
        $retryWindowSeconds = max(30, (int) env('MPESA_STK_RETRY_WINDOW_SECONDS', 90));

        $reservation = DB::transaction(function () use ($student, $user, $phone, $amount, $retryWindowSeconds) {
            $lockedStudent = DB::table('students')->where('id', $student->id)->lockForUpdate()->first();
            if (!$lockedStudent) abort(404, 'Learner record not found.');

            $now = now();
            $cutoff = $now->copy()->subSeconds($retryWindowSeconds);

            $existing = DB::table('payments')
                ->where('student_id', $student->id)
                ->where('user_id', $user->id)
                ->where('parent_phone', $phone)
                ->where('amount', $amount)
                ->where('channel', 'mpesa_stk')
                ->where('status', 'pending')
                ->where('created_at', '>=', $cutoff)
                ->latest('id')
                ->first();

            if ($existing) {
                return ['existing' => true, 'payment_id' => $existing->id];
            }

            DB::table('payments')
                ->where('student_id', $student->id)
                ->where('user_id', $user->id)
                ->where('parent_phone', $phone)
                ->where('amount', $amount)
                ->where('channel', 'mpesa_stk')
                ->where('status', 'pending')
                ->where('created_at', '<', $cutoff)
                ->update([
                    'status' => 'failed',
                    'verification_status' => 'rejected',
                    'failure_reason' => 'No completed M-Pesa callback was received before the retry window expired.',
                    'updated_at' => $now,
                ]);

            $reserved = (float) DB::table('payments')
                ->where('student_id', $student->id)
                ->where('channel', 'mpesa_stk')
                ->where('status', 'pending')
                ->sum('amount');

            $balance = (float) $lockedStudent->fee_balance;
            $available = max(0, $balance - $reserved);
            if ($amount > (int) floor($available)) {
                abort(422, 'The payment amount exceeds the learner\'s available outstanding fee balance after pending M-Pesa requests.');
            }

            $reference = 'FEE' . $student->id . '-' . $now->format('ymdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $paymentId = DB::table('payments')->insertGetId([
                'student_id' => $student->id,
                'user_id' => $user->id,
                'payer_role' => $user->role,
                'payer_name' => $user->name,
                'parent_phone' => $phone,
                'payment_type' => 'school_fees',
                'channel' => 'mpesa_stk',
                'amount' => $amount,
                'account_reference' => $reference,
                'status' => 'pending',
                'verification_status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return ['existing' => false, 'payment_id' => $paymentId, 'reference' => $reference];
        });

        if ($reservation['existing']) {
            return back()->with('success', 'An M-Pesa request for this learner and amount was just started. If no prompt appears, wait briefly and try again.');
        }

        try {
            $response = $mpesa->stkPush($phone, $amount, $reservation['reference'], 'School fees');
            DB::table('payments')->where('id', $reservation['payment_id'])->update([
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'updated_at' => now(),
            ]);
            return back()->with('success', 'M-Pesa request accepted by Safaricom. If the phone does not show a prompt, wait briefly and retry; the payment will only be marked paid after a verified M-Pesa callback.');
        } catch (Throwable $e) {
            $message = $e->getMessage();
            DB::table('payments')->where('id', $reservation['payment_id'])->update([
                'status' => 'failed',
                'verification_status' => 'rejected',
                'failure_reason' => $message ?: 'The M-Pesa request could not be started.',
                'updated_at' => now(),
            ]);
            report($e);
            return back()->withErrors(['mpesa' => $message ?: 'The M-Pesa request could not be started.']);
        }
    }

    private function accessibleStudents($user)
    {
        $profile = DB::table('portal_profiles')->where('user_id', $user->id)->where('active', true)->first();
        if (!$profile) return collect();

        if ($user->role === 'parent') {
            $parentId = DB::table('parents')->where('email', $user->email)->value('id');
            if ($parentId) return DB::table('students')->where('parent_id', $parentId)->whereNull('archived_at')->orderBy('name')->get();
        }

        return $profile->admission_number
            ? DB::table('students')->where('admission_number', $profile->admission_number)->whereNull('archived_at')->get()
            : collect();
    }
}
