<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function index(Request $request)
    {
        $section = $request->get('section', 'parents');
        $allowed = ['parents','classes','teachers','subjects','attendance','exams','results','announcements','events'];
        abort_unless(in_array($section, $allowed, true), 404);
        $data = [
            'section'=>$section,
            'students'=>DB::table('students')->orderBy('name')->get(),
            'teachers'=>DB::table('teachers')->orderBy('name')->get(),
            'subjects'=>DB::table('subjects')->orderBy('name')->get(),
            'exams'=>DB::table('exams')->orderByDesc('id')->get(),
        ];
        $data[$section] = $this->listing($section, $request);
        return view('admin.operations.index', $data);
    }

    private function listing($section, Request $request)
    {
        $tables = ['parents'=>'parents','classes'=>'school_classes','teachers'=>'teachers','subjects'=>'subjects','attendance'=>'attendance','exams'=>'exams','results'=>'results','announcements'=>'announcements','events'=>'events'];
        $query = DB::table($tables[$section])->orderByDesc('id');
        return $query->paginate(10)->withQueryString();
    }

    public function store(Request $request)
    {
        $section = $request->input('section');
        $rules = [
            'parents'=>['name'=>'required|string|max:150','phone'=>'required|string|max:30','email'=>'nullable|email|max:150','relationship'=>'nullable|string|max:50'],
            'classes'=>['name'=>'required|string|max:100','stream'=>'nullable|string|max:50','academic_year'=>'nullable|integer|min:2000|max:2100'],
            'teachers'=>['name'=>'required|string|max:150','email'=>'nullable|email|max:150','phone'=>'nullable|string|max:30','employee_number'=>'nullable|string|max:50'],
            'subjects'=>['name'=>'required|string|max:100','code'=>'nullable|string|max:30','teacher_id'=>'nullable|exists:teachers,id'],
            'exams'=>['name'=>'required|string|max:150','term'=>'required|string|max:50','academic_year'=>'required|integer|min:2000|max:2100','start_date'=>'nullable|date','end_date'=>'nullable|date'],
            'announcements'=>['title'=>'required|string|max:200','body'=>'required|string|max:10000','published'=>'nullable|boolean'],
            'events'=>['title'=>'required|string|max:200','event_date'=>'required|date','location'=>'nullable|string|max:200','description'=>'nullable|string|max:10000'],
        ];
        abort_unless(isset($rules[$section]), 422);
        $data = $request->validate($rules[$section]);
        if ($section === 'announcements') { $data['published']=$request->boolean('published'); $data['published_at']=$data['published'] ? now() : null; }
        $table=['parents'=>'parents','classes'=>'school_classes','teachers'=>'teachers','subjects'=>'subjects','exams'=>'exams','announcements'=>'announcements','events'=>'events'][$section];
        DB::table($table)->insert($data+['created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Record added successfully.');
    }

    public function attendance(Request $request)
    {
        $data=$request->validate(['student_id'=>'required|exists:students,id','attendance_date'=>'required|date','status'=>'required|in:present,absent,late,excused','notes'=>'nullable|string|max:500']);
        DB::table('attendance')->updateOrInsert(['student_id'=>$data['student_id'],'attendance_date'=>$data['attendance_date']],$data+['updated_at'=>now(),'created_at'=>now()]);
        return back()->with('success','Attendance saved successfully.');
    }

    public function result(Request $request)
    {
        $data=$request->validate(['exam_id'=>'required|exists:exams,id','student_id'=>'required|exists:students,id','subject_id'=>'required|exists:subjects,id','marks'=>'required|numeric|min:0|max:100','remarks'=>'nullable|string|max:500']);
        $marks=(float)$data['marks'];
        $data['grade']=$marks>=80?'A':($marks>=70?'B':($marks>=60?'C':($marks>=50?'D':($marks>=40?'E':'F'))));
        DB::table('results')->updateOrInsert(['exam_id'=>$data['exam_id'],'student_id'=>$data['student_id'],'subject_id'=>$data['subject_id']],$data+['updated_at'=>now(),'created_at'=>now()]);
        return back()->with('success','Exam result saved successfully.');
    }
}
