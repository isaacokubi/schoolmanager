<?php

namespace App\Http\Controllers;

use App\Services\CbcReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherAssessmentController extends Controller
{
    public function index(Request $request, CbcReportCardService $cbc)
    {
        $teacher = $this->teacher($request);
        $subjects = DB::table('subjects')->where('teacher_id', $teacher->id)->orderBy('name')->get();
        abort_if($subjects->isEmpty(), 403, 'No learning areas are assigned to your teacher account.');
        $subjectIds = $subjects->pluck('id')->all();
        $students = DB::table('students')->orderBy('name')->get();
        $exams = DB::table('exams')->orderByDesc('academic_year')->orderByDesc('id')->get();
        $results = DB::table('results')->join('students','students.id','=','results.student_id')->join('subjects','subjects.id','=','results.subject_id')->join('exams','exams.id','=','results.exam_id')->whereIn('results.subject_id',$subjectIds)->orderByDesc('results.updated_at')->select('results.*','students.name as student_name','students.admission_number','subjects.name as subject_name','exams.name as exam_name','exams.term as exam_term','exams.academic_year')->paginate(15)->withQueryString();
        foreach ($results as $result) { $level=$cbc->level($result->marks===null?null:(float)$result->marks); $result->cbc_code=$result->assessment_status==='missed'?'MISSED':$level['code']; $result->cbc_points=$result->assessment_status==='missed'?null:$level['points']; }
        return view('portal.teacher-assessments',compact('teacher','subjects','students','exams','results'));
    }

    public function store(Request $request, CbcReportCardService $cbc)
    {
        $teacher=$this->teacher($request);
        $data=$request->validate([
            'exam_id'=>'required|exists:exams,id',
            'student_id'=>'required|exists:students,id',
            'subject_id'=>'required|exists:subjects,id',
            'assessment_status'=>'required|in:present,missed',
            'marks'=>'nullable|required_if:assessment_status,present|numeric|min:0|max:100',
            'remarks'=>'required|string|max:500',
        ]);
        abort_unless(DB::table('subjects')->where('id',$data['subject_id'])->where('teacher_id',$teacher->id)->exists(),403);
        if ($data['assessment_status']==='missed') { $data['marks']=null; $data['grade']=null; $data['achievement_level']='MISSED'; $data['achievement_points']=null; }
        else { $level=$cbc->level((float)$data['marks']); $data['grade']=$level['code']; $data['achievement_level']=$level['code']; $data['achievement_points']=$level['points']; }
        DB::table('results')->updateOrInsert(['exam_id'=>$data['exam_id'],'student_id'=>$data['student_id'],'subject_id'=>$data['subject_id']],$data+['updated_at'=>now(),'created_at'=>now()]);
        return redirect()->route('portal.teacher-assessments')->with('success',$data['assessment_status']==='missed'?'Missed assessment recorded. The system will not treat it as zero.':'CBC assessment saved. Achievement level and points were assigned automatically from the score.');
    }

    private function teacher(Request $request)
    {
        $profile=DB::table('portal_profiles')->where('user_id',$request->user()->id)->where('portal_type','teacher')->where('active',true)->first();
        abort_unless($profile,403);
        $teacher=DB::table('teachers')->where('email',$request->user()->email)->first();
        abort_unless($teacher,403,'Your teacher profile is not linked to your login account.');
        return $teacher;
    }
}
