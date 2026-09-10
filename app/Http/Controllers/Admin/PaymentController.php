<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
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
        $data=$request->validate([
            'student_id'=>['nullable','exists:students,id'],
            'parent_phone'=>['nullable','regex:/^\+?2547\d{8}$/'],
            'payment_type'=>['required','string','max:100'],
            'amount'=>['required','integer','min:1','max:1500000'],
            'account_reference'=>['nullable','string','max:100'],
            'mpesa_receipt'=>['nullable','string','max:100','unique:payments,mpesa_receipt'],
            'status'=>['required','in:pending,completed,failed'],
        ]);
        $data['channel']='manual';
        $data['verification_status']=$data['status']==='completed'?'verified':'pending';
        $data['user_id']=auth()->id();
        $data['payer_role']=auth()->user()->role ?? 'admin';
        $data['payer_name']=auth()->user()->name ?? 'Administrator';
        $data['paid_at']=($data['status']==='completed')?now():null;

        DB::transaction(function() use ($data){
            $paymentId = DB::table('payments')->insertGetId($data+['created_at'=>now(),'updated_at'=>now()]);
            if (!empty($data['student_id']) && $data['status']==='completed') {
                DB::table('students')->where('id',$data['student_id'])->decrement('fee_balance',(int)$data['amount']);
                DB::table('students')->where('id',$data['student_id'])->where('fee_balance','<',0)->update(['fee_balance'=>0]);
            }
            DB::table('payment_audits')->insert([
                'payment_id'=>$paymentId,
                'event'=>$data['status']==='completed'?'manual_payment_verified':'manual_payment_recorded',
                'details'=>json_encode([
                    'amount'=>(int)$data['amount'],
                    'student_id'=>$data['student_id'] ?? null,
                    'status'=>$data['status'],
                    'account_reference'=>$data['account_reference'] ?? null,
                ]),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        });
        return redirect()->route('admin.payments.index')->with('success','Payment recorded successfully.');
    }
}
