<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // Join student details so the payment list can show a learner's name and admission number.
        $query = DB::table('payments')->leftJoin('students','students.id','=','payments.student_id')
            ->select('payments.*','students.name as student_name','students.admission_number')
            ->orderByDesc('payments.id');
        if ($request->filled('status')) $query->where('payments.status',$request->input('status'));
        if ($request->filled('search')) {
            $search=trim($request->input('search'));
            $query->where(function($q) use ($search){
                $q->where('students.name','like',"%{$search}%")
                    ->orWhere('students.admission_number','like',"%{$search}%")
                    ->orWhere('payments.parent_phone','like',"%{$search}%")
                    ->orWhere('payments.mpesa_receipt','like',"%{$search}%")
                    ->orWhere('payments.account_reference','like',"%{$search}%");
            });
        }
        // Preserve search/filter values when moving between pages of results.
        $payments=$query->paginate(10)->withQueryString();
        return view('admin.payments.index',compact('payments'));
    }

    public function create()
    {
        $students=DB::table('students')->orderBy('name')->get();
        return view('admin.payments.form',compact('students'));
    }

    public function store(Request $request)
    {
        // Validate administrator-entered records separately from automated STK requests.
        $data=$request->validate([
            'student_id'=>['nullable','exists:students,id'],
            'parent_phone'=>['nullable','regex:/^\+?2547\d{8}$/'],
            'payment_type'=>['required','string','max:100'],
            'amount'=>['required','integer','min:1','max:1500000'],
            'account_reference'=>['nullable','string','max:100'],
            'mpesa_receipt'=>['nullable','string','max:100','unique:payments,mpesa_receipt'],
            'status'=>['required','in:pending,completed,failed'],
        ]);
        // Record who entered the payment and whether this manual record is treated as verified.
        $data['channel']='manual';
        $data['verification_status']=$data['status']==='completed'?'verified':'pending';
        $data['user_id']=auth()->id();
        $data['payer_role']=auth()->user()->role ?? 'admin';
        $data['payer_name']=auth()->user()->name ?? 'Administrator';
        $data['paid_at']=($data['status']==='completed')?now():null;

        // Save the payment, adjust the balance, and add its audit entry atomically.
        // If one step fails, the database rolls back all changes in this transaction.
        DB::transaction(function() use ($data){
            if (!empty($data['student_id']) && $data['status']==='completed') {
                $student = DB::table('students')->where('id',$data['student_id'])->lockForUpdate()->first();
                if (!$student || (int)$data['amount'] > max(0,(int)floor((float)$student->fee_balance))) {
                    throw ValidationException::withMessages(['amount'=>'The payment amount cannot exceed the learner\'s outstanding whole-KES fee balance.']);
                }
            }

            // Save first to obtain the payment ID used by the audit trail.
            $paymentId = DB::table('payments')->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);
            if (!empty($data['student_id']) && $data['status']==='completed') {
                DB::table('students')->where('id',$data['student_id'])->decrement('fee_balance',(int)$data['amount']);
            }
            DB::table('payment_audits')->insert([
                'payment_id'=>$paymentId,
                'event'=>$data['status']==='completed'?'manual_payment_verified':'manual_payment_recorded',
                'details'=>json_encode(['amount'=>(int)$data['amount'],'student_id'=>$data['student_id'] ?? null,'status'=>$data['status'],'account_reference'=>$data['account_reference'] ?? null]),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        });
        return redirect()->route('admin.payments.index')->with('success','Payment recorded successfully.');
    }
}
