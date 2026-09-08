<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PortalPaymentController extends Controller
{
    public function index(Request $request)
    {
        $students = $this->accessibleStudents($request->user());
        $studentIds = $students->pluck('id');

        $payments = $studentIds->isEmpty()
            ? collect()
            : DB::table('payments')
                ->whereIn('student_id', $studentIds)
                ->orderByDesc('id')
                ->limit(20)
                ->get();

        return view('portal.payments', compact('students', 'payments'));
    }

    public function pay(Request $request, MpesaService $mpesa)
    {
        $user = $request->user();
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'phone' => ['required', 'regex:/^\\+?2547\\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1500000'],
        ]);

        $student = $this->accessibleStudents($user)->firstWhere('id', (int) $data['student_id']);
        if (!$student) {
            abort(403, 'You are not authorised to pay fees for this learner.');
        }

        $amount = min((float) $data['amount'], (float) $student->fee_balance);
        if ($amount <= 0) {
            return back()->withErrors(['amount' => 'This learner has no outstanding fee balance.']);
        }

        $reference = 'FEE' . $student->id . '-' . now()->format('ymdHis');
        $paymentId = DB::table('payments')->insertGetId([
            'student_id' => $student->id,
            'user_id' => $user->id,
            'payer_role' => $user->role,
            'payer_name' => $user->name,
            'parent_phone' => $data['phone'],
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => $amount,
            'account_reference' => $reference,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $response = $mpesa->stkPush($data['phone'], $amount, $reference, 'School fees');
            DB::table('payments')->where('id', $paymentId)->update([
                'checkout_request_id' => $response['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'updated_at' => now(),
            ]);

            return back()->with('success', 'M-Pesa prompt sent. Enter your M-Pesa PIN on the phone to complete the payment.');
        } catch (Throwable $e) {
            DB::table('payments')->where('id', $paymentId)->update([
                'status' => 'failed',
                'updated_at' => now(),
            ]);

            report($e);
            return back()->withErrors(['mpesa' => 'The M-Pesa request could not be started. Check the M-Pesa configuration and try again.']);
        }
    }

    private function accessibleStudents($user)
    {
        $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();
        if (!$profile) return collect();

        if ($user->role === 'parent') {
            $parentId = DB::table('parents')->where('email', $user->email)->value('id');
            if ($parentId) {
                return DB::table('students')->where('parent_id', $parentId)->orderBy('name')->get();
            }
        }

        return $profile->admission_number
            ? DB::table('students')->where('admission_number', $profile->admission_number)->get()
            : collect();
    }
}
