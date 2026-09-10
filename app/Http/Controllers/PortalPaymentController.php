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
        $payments = $studentIds->isEmpty() ? collect() : DB::table('payments')->whereIn('student_id',$studentIds)->orderByDesc('id')->limit(20)->get();
        return view('portal.payments', compact('students','payments'));
    }

    public function pay(Request $request, MpesaService $mpesa)
    {
        $user=$request->user();
        $data=$request->validate([
            'student_id'=>['required','integer','exists:students,id'],
            'phone'=>['required','regex:/^(?:0?7|2547|\+2547)\d{8}$/'],
            'amount'=>['required','integer','min:1','max:1500000'],
        ]);
        $student=$this->accessibleStudents($user)->firstWhere('id',(int)$data['student_id']);
        if(!$student) abort(403,'You are not authorised to pay fees for this learner.');
        $balance=(float)$student->fee_balance;
        $amount=(int)$data['amount'];
        if($amount>max(0,(int)floor($balance))) return back()->withErrors(['amount'=>'The payment amount cannot exceed the learner\'s outstanding whole-KES fee balance.']);
        if($amount<=0) return back()->withErrors(['amount'=>'This learner has no whole-KES outstanding fee balance.']);

        $phone=preg_replace('/^\+/','',trim($data['phone']));
        if(preg_match('/^07\d{8}$/',$phone)) $phone='254'.substr($phone,1);
        elseif(preg_match('/^7\d{8}$/',$phone)) $phone='254'.$phone;

        // Only suppress a rapid accidental double-submit. A pending STK request
        // must not block a genuine retry indefinitely when the phone never prompts.
        $retryWindowSeconds = max(30, (int) env('MPESA_STK_RETRY_WINDOW_SECONDS', 90));
        $existing=DB::table('payments')
            ->where('student_id',$student->id)
            ->where('user_id',$user->id)
            ->where('parent_phone',$phone)
            ->where('amount',$amount)
            ->where('channel','mpesa_stk')
            ->where('status','pending')
            ->where('created_at','>=',now()->subSeconds($retryWindowSeconds))
            ->latest('id')
            ->first();
        if($existing) return back()->with('success','An M-Pesa request for this learner and amount was just started. If no prompt appears, wait briefly and try again.');

        // Close an older abandoned attempt before creating the retry. This keeps
        // the portal history truthful instead of accumulating indefinite pending
        // KES 1/2 test attempts when Safaricom never delivers a prompt.
        DB::table('payments')
            ->where('student_id',$student->id)
            ->where('user_id',$user->id)
            ->where('parent_phone',$phone)
            ->where('amount',$amount)
            ->where('channel','mpesa_stk')
            ->where('status','pending')
            ->where('created_at','<',now()->subSeconds($retryWindowSeconds))
            ->update([
                'status'=>'failed',
                'verification_status'=>'rejected',
                'failure_reason'=>'No completed M-Pesa callback was received before the retry window expired.',
                'updated_at'=>now(),
            ]);

        $reference='FEE'.$student->id.'-'.now()->format('ymdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
        $paymentId=DB::table('payments')->insertGetId([
            'student_id'=>$student->id,'user_id'=>$user->id,'payer_role'=>$user->role,'payer_name'=>$user->name,
            'parent_phone'=>$phone,'payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>$amount,
            'account_reference'=>$reference,'status'=>'pending','verification_status'=>'pending','created_at'=>now(),'updated_at'=>now(),
        ]);
        try {
            $response=$mpesa->stkPush($phone,$amount,$reference,'School fees');
            DB::table('payments')->where('id',$paymentId)->update(['checkout_request_id'=>$response['CheckoutRequestID']??null,'merchant_request_id'=>$response['MerchantRequestID']??null,'updated_at'=>now()]);
            return back()->with('success','M-Pesa request accepted by Safaricom. If the phone does not show a prompt, wait briefly and retry; the payment will only be marked paid after a verified M-Pesa callback.');
        } catch(Throwable $e) {
            $message=$e->getMessage();
            DB::table('payments')->where('id',$paymentId)->update(['status'=>'failed','verification_status'=>'rejected','failure_reason'=>$message ?: 'The M-Pesa request could not be started.','updated_at'=>now()]);
            report($e);
            return back()->withErrors(['mpesa'=>$message ?: 'The M-Pesa request could not be started.']);
        }
    }

    private function accessibleStudents($user)
    {
        $profile=DB::table('portal_profiles')->where('user_id',$user->id)->first();
        if(!$profile) return collect();
        if($user->role==='parent') {
            $parentId=DB::table('parents')->where('email',$user->email)->value('id');
            if($parentId) return DB::table('students')->where('parent_id',$parentId)->orderBy('name')->get();
        }
        return $profile->admission_number ? DB::table('students')->where('admission_number',$profile->admission_number)->get() : collect();
    }
}
