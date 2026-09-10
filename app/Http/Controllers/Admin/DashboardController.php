<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CbcReportCardService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(CbcReportCardService $cbc)
    {
        $stats=['students'=>DB::table('students')->count(),'applications'=>DB::table('admission_applications')->count(),'payments'=>DB::table('payments')->count(),'parents'=>$this->countTable('parents'),'teachers'=>$this->countTable('teachers'),'classes'=>$this->countTable('school_classes'),'attendance_today'=>$this->countWhere('attendance','attendance_date',now()->toDateString()),'published_announcements'=>$this->countWhere('announcements','published',1)];
        $paymentTotal=0;$outstanding=0;
        try{$columns=DB::getSchemaBuilder()->getColumnListing('payments');foreach(['amount','payment_amount','paid_amount'] as $column){if(in_array($column,$columns,true)){$paymentTotal=(float)DB::table('payments')->where(function($q){$q->whereNull('status')->orWhereIn('status',['completed','paid','success']);})->sum($column);break;}}}catch(\Throwable $e){}
        try{$studentColumns=DB::getSchemaBuilder()->getColumnListing('students');foreach(['fee_balance','balance','outstanding_balance'] as $column){if(in_array($column,$studentColumns,true)){$outstanding=(float)DB::table('students')->sum($column);break;}}}catch(\Throwable $e){}
        $stats['payment_total']=$paymentTotal;$stats['outstanding']=$outstanding;
        $recentApplications=DB::table('admission_applications')->latest()->limit(5)->get();$recentPayments=DB::table('payments')->latest()->limit(5)->get();
        $cbcRecentResults=$this->safeQuery('results',function($q){return $q->join('students','students.id','=','results.student_id')->join('subjects','subjects.id','=','results.subject_id')->join('exams','exams.id','=','results.exam_id')->orderByDesc('results.created_at')->select('results.*','students.name as student_name','students.admission_number','subjects.name as subject_name','exams.name as exam_name')->limit(8)->get();});
        foreach($cbcRecentResults as $result){$level=$cbc->level($result->marks===null?null:(float)$result->marks);$result->cbc_code=$result->assessment_status==='missed'?'MISSED':$level['code'];$result->cbc_label=$result->assessment_status==='missed'?'Missed Assessment':$level['label'];$result->cbc_points=$result->assessment_status==='missed'?null:$level['points'];}
        $upcomingEvents=$this->safeQuery('events',function($q){return $q->whereDate('event_date','>=',now()->toDateString())->orderBy('event_date')->limit(5)->get();});
        return view('admin.dashboard',compact('stats','recentApplications','recentPayments','upcomingEvents','cbcRecentResults'));
    }
    private function countTable($table){try{return DB::table($table)->count();}catch(\Throwable $e){return 0;}}
    private function countWhere($table,$column,$value){try{return DB::table($table)->where($column,$value)->count();}catch(\Throwable $e){return 0;}}
    private function safeQuery($table,$callback){try{return $callback(DB::table($table));}catch(\Throwable $e){return collect();}}
}
