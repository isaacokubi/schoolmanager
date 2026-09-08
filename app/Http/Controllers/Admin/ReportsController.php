<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $report = $request->get('report', 'overview');
        abort_unless(in_array($report, ['overview', 'students', 'fees', 'attendance', 'results', 'admissions'], true), 404);
        $data = ['report' => $report, 'generatedAt' => now()];
        $data['studentCount'] = DB::table('students')->count();
        $data['parentCount'] = $this->countTable('parents');
        $data['teacherCount'] = $this->countTable('teachers');
        $data['classCount'] = $this->countTable('school_classes');

        if (in_array($report, ['overview', 'students'], true)) {
            $data['students'] = DB::table('students')->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')->leftJoin('parents', 'parents.id', '=', 'students.parent_id')
                ->select('students.admission_number', 'students.name', 'students.class_name', 'students.fee_balance', 'school_classes.name as class_label', 'school_classes.stream', 'parents.name as parent_label')->orderBy('students.name')->get();
        }
        if (in_array($report, ['overview', 'fees'], true)) {
            $amountColumn = $this->paymentAmountColumn();
            $data['payments'] = DB::table('payments')->leftJoin('students', 'students.id', '=', 'payments.student_id')->select('payments.*', 'students.name as student_name', 'students.admission_number')->latest('payments.id')->limit(100)->get();
            $data['paymentAmountColumn'] = $amountColumn;
            $data['paymentTotal'] = $amountColumn ? (float) DB::table('payments')->where(function ($q) { $q->whereNull('status')->orWhereIn('status', ['completed', 'paid', 'success']); })->sum($amountColumn) : 0;
            $data['outstanding'] = $this->studentBalanceTotal();
        }
        if (in_array($report, ['overview', 'attendance'], true)) {
            $data['attendance'] = DB::table('attendance')->leftJoin('students', 'students.id', '=', 'attendance.student_id')->select('attendance.attendance_date', 'attendance.status', 'students.name as student_name', 'students.admission_number')->orderByDesc('attendance.attendance_date')->orderBy('students.name')->limit(200)->get();
            $data['attendanceSummary'] = DB::table('attendance')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        }
        if (in_array($report, ['overview', 'results'], true)) {
            $data['results'] = DB::table('results')->leftJoin('students', 'students.id', '=', 'results.student_id')->leftJoin('subjects', 'subjects.id', '=', 'results.subject_id')->leftJoin('exams', 'exams.id', '=', 'results.exam_id')
                ->select('results.marks', 'results.grade', 'results.assessment_status', 'results.achievement_level', 'results.achievement_points', 'students.name as student_name', 'students.admission_number', 'subjects.name as subject_name', 'exams.name as exam_name')->orderByDesc('results.id')->limit(200)->get();
            $data['resultCount'] = DB::table('results')->count();
            $data['averageMarks'] = (float) (DB::table('results')->whereNotNull('marks')->avg('marks') ?: 0);
            $data['cbcLevelSummary'] = DB::table('results')->whereNotNull('achievement_level')->select('achievement_level', DB::raw('COUNT(*) as total'))->groupBy('achievement_level')->orderBy('achievement_level')->pluck('total', 'achievement_level');
            $data['missedAssessmentCount'] = DB::table('results')->where('assessment_status', 'missed')->count();
        }
        if (in_array($report, ['overview', 'admissions'], true)) {
            $data['applications'] = DB::table('admission_applications')->orderByDesc('id')->limit(100)->get();
            $data['applicationSummary'] = DB::table('admission_applications')->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        }
        return view('admin.reports.index', $data);
    }

    private function countTable($table){try{return DB::table($table)->count();}catch(\Throwable $e){return 0;}}
    private function paymentAmountColumn(){try{foreach(['amount','payment_amount','paid_amount'] as $column){if(in_array($column,DB::getSchemaBuilder()->getColumnListing('payments'),true))return $column;}}catch(\Throwable $e){}return null;}
    private function studentBalanceTotal(){try{foreach(['fee_balance','balance','outstanding_balance'] as $column){if(in_array($column,DB::getSchemaBuilder()->getColumnListing('students'),true))return (float)DB::table('students')->sum($column);}}catch(\Throwable $e){}return 0;}
}
