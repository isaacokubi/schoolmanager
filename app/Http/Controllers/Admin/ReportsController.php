<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CbcReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    private const REPORT_PAGE_SIZE = 25;

    public function index(Request $request, CbcReportCardService $cbc)
    {
        $report = $request->get('report', 'overview');
        abort_unless(in_array($report, ['overview', 'students', 'fees', 'attendance', 'results', 'admissions'], true), 404);

        $data = ['report' => $report, 'generatedAt' => now()];
        $data['studentCount'] = $this->countTable('students');
        $data['parentCount'] = $this->countTable('parents');
        $data['teacherCount'] = $this->countTable('teachers');
        $data['classCount'] = $this->countTable('school_classes');

        if (in_array($report, ['overview', 'students'], true)) {
            $data['students'] = DB::table('students')
                ->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')
                ->leftJoin('parents', 'parents.id', '=', 'students.parent_id')
                ->whereNull('students.archived_at')
                ->select('students.admission_number', 'students.name', 'students.class_name', 'students.fee_balance', 'school_classes.name as class_label', 'school_classes.stream', 'parents.name as parent_label')
                ->orderBy('students.name')
                ->paginate(self::REPORT_PAGE_SIZE, ['*'], 'students_page')
                ->withQueryString();
        }

        if (in_array($report, ['overview', 'fees'], true)) {
            $amountColumn = $this->paymentAmountColumn();
            $data['payments'] = DB::table('payments')
                ->leftJoin('students', 'students.id', '=', 'payments.student_id')
                ->select('payments.*', 'students.name as student_name', 'students.admission_number')
                ->latest('payments.id')
                ->paginate(self::REPORT_PAGE_SIZE, ['*'], 'payments_page')
                ->withQueryString();
            $data['paymentAmountColumn'] = $amountColumn;
            $data['paymentTotal'] = $amountColumn ? (float) DB::table('payments')->where(function ($q) {
                $q->whereNull('status')->orWhereIn('status', ['completed', 'paid', 'success']);
            })->sum($amountColumn) : 0;
            $data['outstanding'] = $this->studentBalanceTotal();
        }

        if (in_array($report, ['overview', 'attendance'], true)) {
            $data['attendance'] = DB::table('attendance')
                ->leftJoin('students', 'students.id', '=', 'attendance.student_id')
                ->select('attendance.attendance_date', 'attendance.status', 'students.name as student_name', 'students.admission_number')
                ->orderByDesc('attendance.attendance_date')
                ->orderBy('students.name')
                ->paginate(self::REPORT_PAGE_SIZE, ['*'], 'attendance_page')
                ->withQueryString();
            $data['attendanceSummary'] = DB::table('attendance')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        if (in_array($report, ['overview', 'results'], true)) {
            $resultsQuery = DB::table('results')
                ->leftJoin('students', 'students.id', '=', 'results.student_id')
                ->leftJoin('subjects', 'subjects.id', '=', 'results.subject_id')
                ->leftJoin('exams', 'exams.id', '=', 'results.exam_id')
                ->whereNull('results.archived_at')
                ->select('results.marks', 'results.grade', 'results.assessment_status', 'results.achievement_level', 'results.achievement_points', 'students.name as student_name', 'students.admission_number', 'subjects.name as subject_name', 'exams.name as exam_name', 'results.id');

            $data['resultCount'] = (clone $resultsQuery)->count('results.id');
            $data['averageMarks'] = (float) ((clone $resultsQuery)->whereNotNull('results.marks')->avg('results.marks') ?: 0);
            $data['missedAssessmentCount'] = (clone $resultsQuery)->where('results.assessment_status', 'missed')->count('results.id');
            $data['cbcLevelSummary'] = $this->cbcLevelSummary($resultsQuery);

            $data['results'] = $resultsQuery
                ->orderByDesc('results.id')
                ->paginate(self::REPORT_PAGE_SIZE, ['*'], 'results_page')
                ->withQueryString();

            $data['results']->getCollection()->transform(function ($row) use ($cbc) {
                if ($row->assessment_status === 'missed') {
                    $row->achievement_level = 'MISSED';
                    $row->achievement_points = null;
                } elseif ($row->marks !== null) {
                    $level = $cbc->level((float) $row->marks);
                    $row->achievement_level = $level['code'];
                    $row->achievement_points = $level['points'];
                }
                return $row;
            });
        }

        if (in_array($report, ['overview', 'admissions'], true)) {
            $data['applications'] = DB::table('admission_applications')
                ->orderByDesc('id')
                ->paginate(self::REPORT_PAGE_SIZE, ['*'], 'admissions_page')
                ->withQueryString();
            $data['applicationSummary'] = DB::table('admission_applications')
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        return view('admin.reports.index', $data);
    }

    private function cbcLevelSummary($resultsQuery)
    {
        $row = (clone $resultsQuery)->selectRaw("
            SUM(CASE WHEN results.marks BETWEEN 90 AND 100 THEN 1 ELSE 0 END) as EE1,
            SUM(CASE WHEN results.marks BETWEEN 75 AND 89.999999 THEN 1 ELSE 0 END) as EE2,
            SUM(CASE WHEN results.marks BETWEEN 58 AND 74.999999 THEN 1 ELSE 0 END) as ME1,
            SUM(CASE WHEN results.marks BETWEEN 41 AND 57.999999 THEN 1 ELSE 0 END) as ME2,
            SUM(CASE WHEN results.marks BETWEEN 31 AND 40.999999 THEN 1 ELSE 0 END) as AE1,
            SUM(CASE WHEN results.marks BETWEEN 21 AND 30.999999 THEN 1 ELSE 0 END) as AE2,
            SUM(CASE WHEN results.marks BETWEEN 11 AND 20.999999 THEN 1 ELSE 0 END) as BE1,
            SUM(CASE WHEN results.marks BETWEEN 0 AND 10.999999 THEN 1 ELSE 0 END) as BE2
        ")->first();

        return collect((array) $row)->mapWithKeys(function ($value, $key) {
            return [strtoupper($key) => (int) $value];
        });
    }

    private function countTable($table)
    {
        try {
            return DB::table($table)->whereNull('archived_at')->count();
        } catch (\Throwable $e) {
            try { return DB::table($table)->count(); } catch (\Throwable $ignored) { return 0; }
        }
    }

    private function paymentAmountColumn()
    {
        try {
            foreach (['amount', 'payment_amount', 'paid_amount'] as $column) {
                if (in_array($column, DB::getSchemaBuilder()->getColumnListing('payments'), true)) return $column;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    private function studentBalanceTotal()
    {
        try {
            foreach (['fee_balance', 'balance', 'outstanding_balance'] as $column) {
                if (in_array($column, DB::getSchemaBuilder()->getColumnListing('students'), true)) return (float) DB::table('students')->whereNull('archived_at')->sum($column);
            }
        } catch (\Throwable $e) {}
        return 0;
    }
}
