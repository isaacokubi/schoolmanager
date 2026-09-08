<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    private $tables = [
        'parents'=>'parents','classes'=>'school_classes','teachers'=>'teachers','subjects'=>'subjects',
        'attendance'=>'attendance','exams'=>'exams','results'=>'results','announcements'=>'announcements','events'=>'events'
    ];

    public function index(Request $request)
    {
        $section = $request->get('section', 'parents');
        abort_unless(isset($this->tables[$section]), 404);
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
        $query = DB::table($this->tables[$section])->orderByDesc('id');
        $search = trim((string)$request->get('search', ''));
        if ($search !== '') {
            $columns = DB::getSchemaBuilder()->getColumnListing($this->tables[$section]);
            $searchable = array_values(array_filter($columns, function ($column) {
                return !in_array($column, ['id','created_at','updated_at'], true);
            }));
            if ($searchable) {
                $query->where(function ($q) use ($searchable, $search) {
                    foreach ($searchable as $column) $q->orWhere($column, 'like', '%'.$search.'%');
                });
            }
        }
        return $query->paginate(10)->withQueryString();
    }

    public function store(Request $request)
    {
        $section = $request->input('section');
        $rules = $this->rules();
        abort_unless(isset($rules[$section]), 422);
        $data = $request->validate($rules[$section]);
        $data = $this->prepare($section, $data, $request);
        DB::table($this->tables[$section])->insert($data+['created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Record added successfully.');
    }

    public function update(Request $request, $id)
    {
        $section = $request->input('section');
        $rules = $this->rules();
        abort_unless(isset($this->tables[$section]) && isset($rules[$section]), 404);
        abort_unless(DB::table($this->tables[$section])->where('id',$id)->exists(), 404);
        $data = $request->validate($rules[$section]);
        $data = $this->prepare($section, $data, $request, $id);
        DB::table($this->tables[$section])->where('id',$id)->update($data+['updated_at'=>now()]);
        return redirect()->route('admin.operations',['section'=>$section])->with('success','Record updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $section = $request->input('section');
        abort_unless(isset($this->tables[$section]), 404);
        abort_unless(DB::table($this->tables[$section])->where('id',$id)->exists(), 404);
        DB::table($this->tables[$section])->where('id',$id)->delete();
        return back()->with('success','Record deleted successfully.');
    }

    public function attendance(Request $request)
    {
        $data=$request->validate($this->rules()['attendance']);
        DB::table('attendance')->updateOrInsert(
            ['student_id'=>$data['student_id'],'attendance_date'=>$data['attendance_date']],
            $data+['updated_at'=>now(),'created_at'=>now()]
        );
        return back()->with('success','Attendance saved successfully.');
    }

    public function result(Request $request)
    {
        $data=$request->validate($this->rules()['results']);
        $data['grade']=$this->grade((float)$data['marks']);
        DB::table('results')->updateOrInsert(
            ['exam_id'=>$data['exam_id'],'student_id'=>$data['student_id'],'subject_id'=>$data['subject_id']],
            $data+['updated_at'=>now(),'created_at'=>now()]
        );
        return back()->with('success','Exam result saved successfully.');
    }

    private function rules()
    {
        return [
            'parents'=>['name'=>'required|string|max:150','phone'=>['required','regex:/^(?:\\+254|0)7\\d{8}$/'],'email'=>'nullable|email|max:150','relationship'=>'nullable|string|max:50'],
            'classes'=>['name'=>'required|string|max:100','stream'=>'nullable|string|max:50','academic_year'=>'nullable|integer|min:2000|max:2100'],
            'teachers'=>['name'=>'required|string|max:150','email'=>'nullable|email|max:150','phone'=>['nullable','regex:/^(?:\\+254|0)7\\d{8}$/'],'employee_number'=>'nullable|string|max:50'],
            'subjects'=>['name'=>'required|string|max:100','code'=>'nullable|string|max:30','teacher_id'=>'nullable|exists:teachers,id'],
            'attendance'=>['student_id'=>'required|exists:students,id','attendance_date'=>'required|date','status'=>'required|in:present,absent,late,excused','notes'=>'nullable|string|max:500'],
            'exams'=>['name'=>'required|string|max:150','term'=>'required|string|max:50','academic_year'=>'required|integer|min:2000|max:2100','start_date'=>'nullable|date','end_date'=>'nullable|date|after_or_equal:start_date'],
            'results'=>['exam_id'=>'required|exists:exams,id','student_id'=>'required|exists:students,id','subject_id'=>'required|exists:subjects,id','marks'=>'required|numeric|min:0|max:100','remarks'=>'nullable|string|max:500'],
            'announcements'=>['title'=>'required|string|max:200','body'=>'required|string|max:10000','published'=>'nullable|boolean'],
            'events'=>['title'=>'required|string|max:200','event_date'=>'required|date','location'=>'nullable|string|max:200','description'=>'nullable|string|max:10000'],
        ];
    }

    private function prepare($section, array $data, Request $request, $id=null)
    {
        if ($section === 'announcements') {
            $data['published']=$request->boolean('published');
            $data['published_at']=$data['published'] ? now() : null;
        }
        if ($section === 'results' && array_key_exists('marks', $data)) {
            $data['grade']=$this->grade((float)$data['marks']);
        }
        return $data;
    }

    private function grade($marks)
    {
        return $marks >= 80 ? 'A' : ($marks >= 70 ? 'B' : ($marks >= 60 ? 'C' : ($marks >= 50 ? 'D' : ($marks >= 40 ? 'E' : 'F'))));
    }
}
