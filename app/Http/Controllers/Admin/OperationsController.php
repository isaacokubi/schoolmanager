<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CbcReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private $tables = ['parents'=>'parents','classes'=>'school_classes','teachers'=>'teachers','subjects'=>'subjects','attendance'=>'attendance','exams'=>'exams','results'=>'results','announcements'=>'announcements','events'=>'events'];

    public function index(Request $request)
    {
        $section=$request->get('section','parents'); abort_unless(isset($this->tables[$section]),404);
        $data=['section'=>$section,'students'=>DB::table('students')->orderBy('name')->get(),'teachers'=>DB::table('teachers')->orderBy('name')->get(),'subjects'=>DB::table('subjects')->orderBy('name')->get(),'exams'=>DB::table('exams')->orderByDesc('id')->get()];
        $data[$section]=$this->listing($section,$request); return view('admin.operations.index',$data);
    }

    private function listing($section,Request $request)
    {
        $table=$this->tables[$section];
        if($section==='attendance') $query=DB::table('attendance')->leftJoin('students','students.id','=','attendance.student_id')->select('attendance.*','students.name as student_name','students.admission_number')->orderByDesc('attendance.attendance_date')->orderByDesc('attendance.id');
        elseif($section==='results') $query=DB::table('results')->leftJoin('students','students.id','=','results.student_id')->leftJoin('exams','exams.id','=','results.exam_id')->leftJoin('subjects','subjects.id','=','results.subject_id')->select('results.*','students.name as student_name','students.admission_number','exams.name as exam_name','subjects.name as subject_name')->orderByDesc('results.id');
        else $query=DB::table($table)->orderByDesc('id');
        $search=trim((string)$request->get('search',''));
        if($search!=='') {
            if($section==='attendance') $query->where(function($q)use($search){$q->where('students.name','like','%'.$search.'%')->orWhere('students.admission_number','like','%'.$search.'%')->orWhere('attendance.status','like','%'.$search.'%')->orWhere('attendance.attendance_date','like','%'.$search.'%');});
            elseif($section==='results') $query->where(function($q)use($search){$q->where('students.name','like','%'.$search.'%')->orWhere('students.admission_number','like','%'.$search.'%')->orWhere('exams.name','like','%'.$search.'%')->orWhere('subjects.name','like','%'.$search.'%')->orWhere('results.achievement_level','like','%'.$search.'%')->orWhere('results.assessment_status','like','%'.$search.'%');});
            else { $columns=DB::getSchemaBuilder()->getColumnListing($table); $searchable=array_values(array_filter($columns,function($c){return !in_array($c,['id','created_at','updated_at'],true);})); if($searchable)$query->where(function($q)use($searchable,$search){foreach($searchable as $column)$q->orWhere($column,'like','%'.$search.'%');}); }
        }
        return $query->paginate(10)->withQueryString();
    }

    public function store(Request $request,CbcReportCardService $reportCards)
    {
        $section=$request->input('section'); abort_unless(isset($this->tables[$section]),422);
        $data=$this->prepare($section,$request->validate($this->rules($section)),$request);
        try { DB::table($this->tables[$section])->insert($data+['created_at'=>now(),'updated_at'=>now()]); }
        catch(\Throwable $e){return back()->withErrors(['record'=>'The record could not be saved. Check for duplicate values or related records.'])->withInput();}
        if($section==='results') { try{$reportCards->generateAndNotify((int)$data['student_id'],(int)$data['exam_id']);}catch(\Throwable $e){report($e);} }
        return back()->with('success',$section==='results'?'CBC assessment saved successfully.':'Record added successfully.');
    }

    public function update(Request $request,$id,CbcReportCardService $reportCards)
    {
        $section=$request->input('section'); abort_unless(isset($this->tables[$section]),404); abort_unless(DB::table($this->tables[$section])->where('id',$id)->exists(),404);
        $data=$this->prepare($section,$request->validate($this->rules($section,$id)),$request,$id);
        try{DB::table($this->tables[$section])->where('id',$id)->update($data+['updated_at'=>now()]);}catch(\Throwable $e){return back()->withErrors(['record'=>'The record could not be updated. Check for duplicate values or related records.'])->withInput();}
        if($section==='results'){try{$reportCards->generateAndNotify((int)$data['student_id'],(int)$data['exam_id']);}catch(\Throwable $e){report($e);}}
        return redirect()->route('admin.operations',['section'=>$section])->with('success',$section==='results'?'CBC assessment updated successfully.':'Record updated successfully.');
    }

    public function destroy(Request $request,$id){$section=$request->input('section');abort_unless(isset($this->tables[$section]),404);abort_unless(DB::table($this->tables[$section])->where('id',$id)->exists(),404);try{DB::table($this->tables[$section])->where('id',$id)->delete();}catch(\Throwable $e){return back()->withErrors(['record'=>'This record cannot be deleted because other records depend on it.']);}return back()->with('success','Record deleted successfully.');}

    public function attendance(Request $request){$data=$request->validate($this->rules('attendance'));DB::table('attendance')->updateOrInsert(['student_id'=>$data['student_id'],'attendance_date'=>$data['attendance_date']],$data+['updated_at'=>now(),'created_at'=>now()]);return back()->with('success','Attendance saved successfully.');}

    public function result(Request $request,CbcReportCardService $reportCards){$data=$request->validate($this->rules('results'));$data=$this->prepare('results',$data,$request);DB::table('results')->updateOrInsert(['exam_id'=>$data['exam_id'],'student_id'=>$data['student_id'],'subject_id'=>$data['subject_id']],$data+['updated_at'=>now(),'created_at'=>now()]);try{$reportCards->generateAndNotify((int)$data['student_id'],(int)$data['exam_id']);}catch(\Throwable $e){report($e);}return back()->with('success',$data['assessment_status']==='missed'?'Missed assessment recorded. It will appear on the CBC report card.':'CBC result saved. The report card is generated and notified automatically when the assessment set is complete.');}

    private function rules($section,$id=null){$rules=['parents'=>['name'=>'required|string|max:150','phone'=>['required','regex:/^(?:\\+254|0)7\\d{8}$/'],'email'=>'nullable|email|max:150','relationship'=>'nullable|string|max:50'],'classes'=>['name'=>'required|string|max:100','stream'=>'nullable|string|max:50','academic_year'=>'nullable|integer|min:2000|max:2100'],'teachers'=>['name'=>'required|string|max:150','email'=>'nullable|email|max:150','phone'=>['nullable','regex:/^(?:\\+254|0)7\\d{8}$/'],'employee_number'=>'nullable|string|max:50'],'subjects'=>['name'=>'required|string|max:100','code'=>'nullable|string|max:30','teacher_id'=>'nullable|exists:teachers,id'],'attendance'=>['student_id'=>'required|exists:students,id','attendance_date'=>'required|date','status'=>'required|in:present,absent,late,excused','notes'=>'nullable|string|max:500'],'exams'=>['name'=>'required|string|max:150','term'=>'required|string|max:50','academic_year'=>'required|integer|min:2000|max:2100','start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date'],'results'=>['exam_id'=>'required|exists:exams,id','student_id'=>'required|exists:students,id','subject_id'=>'required|exists:subjects,id','assessment_status'=>'required|in:present,missed','marks'=>'nullable|required_if:assessment_status,present|numeric|min:0|max:100','remarks'=>'nullable|string|max:500'],'announcements'=>['title'=>'required|string|max:200','body'=>'required|string|max:10000','published'=>'nullable|boolean'],'events'=>['title'=>'required|string|max:200','event_date'=>'required|date','location'=>'nullable|string|max:200','description'=>'nullable|string|max:10000']];if($section==='teachers')$rules[$section]['employee_number'][]=Rule::unique('teachers','employee_number')->ignore($id);if($section==='subjects')$rules[$section]['code'][]=Rule::unique('subjects','code')->ignore($id);return $rules[$section];}

    private function prepare($section,array $data,Request $request,$id=null){if($section==='announcements'){$data['published']=$request->boolean('published');$data['published_at']=$data['published']?now():null;}if($section==='results'){$level=app(CbcReportCardService::class)->level($data['marks']===null?null:(float)$data['marks']);$data['grade']=$level['code']==='MISSED'?null:$level['code'];$data['achievement_level']=$level['code']==='MISSED'?null:$level['code'];$data['achievement_points']=$level['points'];}return $data;}
}
