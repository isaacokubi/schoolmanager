<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSearchController extends Controller
{
    private const LIMIT = 8;

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $scope = Str::lower(trim((string) $request->query('scope', 'global')));
        if (mb_strlen($query) < 1) return response()->json(['data' => []]);

        $allowed = ['global','students','admissions','parents','classes','teachers','subjects','attendance','exams','results','announcements','events','payments'];
        abort_unless(in_array($scope, $allowed, true), 404);

        return response()->json(['data' => $scope === 'global' ? $this->global($query) : $this->search($scope, $query)]);
    }

    private function search(string $scope, string $term): array
    {
        $like = '%' . $term . '%';
        if ($scope === 'students') return $this->students($like);
        if ($scope === 'admissions') return $this->admissions($like);
        if ($scope === 'parents') return $this->simpleTable('parents', ['name','phone','email','relationship'], $like, 'Parents / Guardians', 'parents');
        if ($scope === 'classes') return $this->classes($like);
        if ($scope === 'teachers') return $this->simpleTable('teachers', ['name','employee_number','phone','email'], $like, 'Teachers', 'teachers');
        if ($scope === 'subjects') return $this->simpleTable('subjects', ['name','code'], $like, 'Learning Areas', 'subjects');
        if ($scope === 'attendance') return $this->attendance($like);
        if ($scope === 'exams') return $this->simpleTable('exams', ['name','term','academic_year'], $like, 'Assessments', 'exams');
        if ($scope === 'results') return $this->results($like);
        if ($scope === 'announcements') return $this->simpleTable('announcements', ['title','body'], $like, 'Announcements', 'announcements');
        if ($scope === 'events') return $this->simpleTable('events', ['title','location','description','event_date'], $like, 'School Calendar', 'events');
        if ($scope === 'payments') return $this->payments($like);
        return [];
    }

    private function global(string $term): array
    {
        $like = '%' . $term . '%';
        $results = collect();
        $results = $results->merge($this->students($like));
        $results = $results->merge($this->admissions($like));
        $results = $results->merge($this->simpleTable('parents', ['name','phone','email'], $like, 'Parents / Guardians', 'parents'));
        $results = $results->merge($this->classes($like));
        $results = $results->merge($this->simpleTable('teachers', ['name','employee_number','phone','email'], $like, 'Teachers', 'teachers'));
        $results = $results->merge($this->payments($like));
        return $results->take(self::LIMIT)->values()->all();
    }

    private function students(string $like): array
    {
        return DB::table('students')->leftJoin('parents','parents.id','=','students.parent_id')->leftJoin('school_classes','school_classes.id','=','students.class_id')
            ->where(function($q) use ($like){ $q->where('students.name','like',$like)->orWhere('students.admission_number','like',$like)->orWhere('students.parent_name','like',$like)->orWhere('students.parent_phone','like',$like)->orWhere('parents.name','like',$like)->orWhere('parents.phone','like',$like)->orWhere('school_classes.name','like',$like)->orWhere('school_classes.stream','like',$like); })
            ->select('students.id','students.name','students.admission_number','school_classes.name as class_name','school_classes.stream')->orderBy('students.name')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->name,'subtitle'=>trim(($row->admission_number ?: 'No admission number').' · '.($row->class_name ? $row->class_name.($row->stream ? ' — '.$row->stream : '') : 'Unassigned')),'url'=>route('admin.students.edit',$row->id)]; })->all();
    }

    private function admissions(string $like): array
    {
        return DB::table('admission_applications')->where(function($q) use ($like){ $q->where('student_name','like',$like)->orWhere('parent_name','like',$like)->orWhere('parent_phone','like',$like)->orWhere('requested_class','like',$like)->orWhere('status','like',$like); })
            ->select('id','student_name','requested_class','status')->orderByDesc('id')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->student_name,'subtitle'=>'Admission · '.($row->requested_class ?: 'Class not specified').' · '.ucfirst((string)$row->status),'url'=>route('admin.admissions.index',['search'=>$row->student_name])]; })->all();
    }

    private function classes(string $like): array
    {
        return DB::table('school_classes')->where(function($q) use ($like){ $q->where('name','like',$like)->orWhere('stream','like',$like)->orWhere('academic_year','like',$like); })
            ->select('id','name','stream','academic_year')->orderBy('name')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->name.($row->stream ? ' — '.$row->stream : ''),'subtitle'=>'Class / stream · '.($row->academic_year ?: 'Academic year not set'),'url'=>route('admin.operations',['section'=>'classes','search'=>$row->name])]; })->all();
    }

    private function attendance(string $like): array
    {
        return DB::table('attendance')->leftJoin('students','students.id','=','attendance.student_id')->where(function($q) use ($like){ $q->where('students.name','like',$like)->orWhere('students.admission_number','like',$like)->orWhere('attendance.status','like',$like)->orWhere('attendance.attendance_date','like',$like); })
            ->select('attendance.id','students.name as student_name','attendance.status','attendance.attendance_date')->orderByDesc('attendance.attendance_date')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->student_name ?: 'Learner','subtitle'=>'Attendance · '.ucfirst((string)$row->status).' · '.$row->attendance_date,'url'=>route('admin.operations',['section'=>'attendance','search'=>$row->student_name])]; })->all();
    }

    private function results(string $like): array
    {
        return DB::table('results')->leftJoin('students','students.id','=','results.student_id')->leftJoin('exams','exams.id','=','results.exam_id')->leftJoin('subjects','subjects.id','=','results.subject_id')
            ->where(function($q) use ($like){ $q->where('students.name','like',$like)->orWhere('students.admission_number','like',$like)->orWhere('exams.name','like',$like)->orWhere('subjects.name','like',$like)->orWhere('results.achievement_level','like',$like)->orWhere('results.assessment_status','like',$like); })
            ->select('results.id','students.name as student_name','exams.name as exam_name','subjects.name as subject_name')->orderByDesc('results.id')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->student_name ?: 'Learner','subtitle'=>'Result · '.($row->exam_name ?: 'Assessment').' · '.($row->subject_name ?: 'Learning area'),'url'=>route('admin.operations',['section'=>'results','search'=>$row->student_name])]; })->all();
    }

    private function payments(string $like): array
    {
        return DB::table('payments')->leftJoin('students','students.id','=','payments.student_id')->where(function($q) use ($like){ $q->where('students.name','like',$like)->orWhere('students.admission_number','like',$like)->orWhere('payments.parent_phone','like',$like)->orWhere('payments.mpesa_receipt','like',$like)->orWhere('payments.account_reference','like',$like)->orWhere('payments.status','like',$like); })
            ->select('payments.id','students.name as student_name','payments.amount','payments.account_reference','payments.status')->orderByDesc('payments.id')->limit(self::LIMIT)->get()
            ->map(function($row){ return ['title'=>$row->student_name ?: ($row->account_reference ?: 'Payment'),'subtitle'=>'Payment · KES '.number_format((float)$row->amount,2).' · '.ucfirst((string)$row->status),'url'=>route('admin.payments.index',['search'=>$row->student_name ?: ($row->account_reference ?: '')])]; })->all();
    }

    private function simpleTable(string $table, array $columns, string $like, string $label, string $section): array
    {
        $query = DB::table($table)->where(function($q) use ($columns,$like){ foreach($columns as $column) $q->orWhere($column,'like',$like); });
        return $query->orderByDesc('id')->limit(self::LIMIT)->get()->map(function($row) use ($label,$section){
            $title = (string)($row->name ?? $row->title ?? $row->event_name ?? $label);
            $secondary = $row->email ?? $row->phone ?? $row->code ?? $row->stream ?? $row->location ?? $row->term ?? null;
            return ['title'=>$title,'subtitle'=>$label.($secondary ? ' · '.$secondary : ''),'url'=>route('admin.operations',['section'=>$section,'search'=>$title])];
        })->all();
    }
}
