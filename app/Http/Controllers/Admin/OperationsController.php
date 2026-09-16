<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CbcReportCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private $tables = [
        'parents' => 'parents', 'classes' => 'school_classes', 'teachers' => 'teachers',
        'subjects' => 'subjects', 'attendance' => 'attendance', 'exams' => 'exams',
        'results' => 'results', 'announcements' => 'announcements', 'events' => 'events',
    ];

    public function index(Request $request)
    {
        $section = $request->get('section', 'parents');
        abort_unless(isset($this->tables[$section]), 404);

        $data = [
            'section' => $section,
            'students' => DB::table('students')->orderBy('name')->get(),
            'teachers' => DB::table('teachers')->whereNull('archived_at')->orderBy('name')->get(),
            'subjects' => DB::table('subjects')->whereNull('archived_at')->orderBy('name')->get(),
            'exams' => DB::table('exams')->whereNull('archived_at')->orderByDesc('id')->get(),
        ];
        $data[$section] = $this->listing($section, $request);

        return view('admin.operations.professional', $data);
    }

    private function listing($section, Request $request)
    {
        $table = $this->tables[$section];

        if ($section === 'parents') {
            $query = DB::table('parents')
                ->select('parents.*')
                ->selectSub(function ($q) {
                    $q->from('students')->selectRaw('count(*)')->whereColumn('students.parent_id', 'parents.id');
                }, 'learner_count')
                ->whereNull('parents.archived_at')
                ->orderByDesc('parents.id');
        } elseif ($section === 'classes') {
            $query = DB::table('school_classes')
                ->leftJoin('teachers', 'teachers.id', '=', 'school_classes.class_teacher_id')
                ->select('school_classes.*', 'teachers.name as teacher_name')
                ->whereNull('school_classes.archived_at')
                ->orderByDesc('school_classes.id');
        } elseif ($section === 'teachers') {
            $query = DB::table('teachers')
                ->select('teachers.*')
                ->selectSub(function ($q) {
                    $q->from('subjects')->selectRaw('count(*)')->whereColumn('subjects.teacher_id', 'teachers.id')->whereNull('subjects.archived_at');
                }, 'subject_count')
                ->whereNull('teachers.archived_at')
                ->orderByDesc('teachers.id');
        } elseif ($section === 'subjects') {
            $query = DB::table('subjects')
                ->leftJoin('teachers', 'teachers.id', '=', 'subjects.teacher_id')
                ->select('subjects.*', 'teachers.name as teacher_name')
                ->whereNull('subjects.archived_at')
                ->orderByDesc('subjects.id');
        } elseif ($section === 'attendance') {
            $query = DB::table('attendance')
                ->leftJoin('students', 'students.id', '=', 'attendance.student_id')
                ->select('attendance.*', 'students.name as student_name', 'students.admission_number')
                ->orderByDesc('attendance.attendance_date')->orderByDesc('attendance.id');
        } elseif ($section === 'results') {
            $query = DB::table('results')
                ->leftJoin('students', 'students.id', '=', 'results.student_id')
                ->leftJoin('exams', 'exams.id', '=', 'results.exam_id')
                ->leftJoin('subjects', 'subjects.id', '=', 'results.subject_id')
                ->select('results.*', 'students.name as student_name', 'students.admission_number', 'exams.name as exam_name', 'subjects.name as subject_name')
                ->orderByDesc('results.id');
        } else {
            $query = DB::table($table)->whereNull($table . '.archived_at')->orderByDesc('id');
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            if ($section === 'parents') {
                $query->where(function ($q) use ($search) {
                    $q->where('parents.name', 'like', '%' . $search . '%')
                        ->orWhere('parents.phone', 'like', '%' . $search . '%')
                        ->orWhere('parents.email', 'like', '%' . $search . '%')
                        ->orWhere('parents.relationship', 'like', '%' . $search . '%');
                });
            } elseif ($section === 'classes') {
                $query->where(function ($q) use ($search) {
                    $q->where('school_classes.name', 'like', '%' . $search . '%')
                        ->orWhere('school_classes.stream', 'like', '%' . $search . '%')
                        ->orWhere('school_classes.academic_year', 'like', '%' . $search . '%')
                        ->orWhere('teachers.name', 'like', '%' . $search . '%');
                });
            } elseif ($section === 'teachers') {
                $query->where(function ($q) use ($search) {
                    $q->where('teachers.name', 'like', '%' . $search . '%')
                        ->orWhere('teachers.email', 'like', '%' . $search . '%')
                        ->orWhere('teachers.phone', 'like', '%' . $search . '%')
                        ->orWhere('teachers.employee_number', 'like', '%' . $search . '%');
                });
            } elseif ($section === 'subjects') {
                $query->where(function ($q) use ($search) {
                    $q->where('subjects.name', 'like', '%' . $search . '%')
                        ->orWhere('subjects.code', 'like', '%' . $search . '%')
                        ->orWhere('teachers.name', 'like', '%' . $search . '%');
                });
            } elseif ($section === 'attendance') {
                $query->where(function ($q) use ($search) {
                    $q->where('students.name', 'like', '%' . $search . '%')
                        ->orWhere('students.admission_number', 'like', '%' . $search . '%')
                        ->orWhere('attendance.status', 'like', '%' . $search . '%')
                        ->orWhere('attendance.attendance_date', 'like', '%' . $search . '%');
                });
            } elseif ($section === 'results') {
                $query->where(function ($q) use ($search) {
                    $q->where('students.name', 'like', '%' . $search . '%')
                        ->orWhere('students.admission_number', 'like', '%' . $search . '%')
                        ->orWhere('exams.name', 'like', '%' . $search . '%')
                        ->orWhere('subjects.name', 'like', '%' . $search . '%')
                        ->orWhere('results.achievement_level', 'like', '%' . $search . '%')
                        ->orWhere('results.assessment_status', 'like', '%' . $search . '%');
                });
            } else {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $searchable = array_values(array_filter($columns, function ($column) {
                    return !in_array($column, ['id', 'created_at', 'updated_at', 'archived_at'], true);
                }));
                if ($searchable) {
                    $query->where(function ($q) use ($searchable, $search, $table) {
                        foreach ($searchable as $column) {
                            $q->orWhere($table . '.' . $column, 'like', '%' . $search . '%');
                        }
                    });
                }
            }
        }

        return $query->paginate(10)->withQueryString();
    }

    public function store(Request $request, CbcReportCardService $reportCards)
    {
        $section = $request->input('section');
        abort_unless(isset($this->tables[$section]), 422);
        $validated = $request->validate($this->rules($section));
        $studentId = $section === 'parents' ? ($validated['student_id'] ?? null) : null;
        unset($validated['student_id']);
        $data = $this->prepare($section, $validated, $request);

        try {
            DB::transaction(function () use ($section, $data, $studentId) {
                $id = DB::table($this->tables[$section])->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
                if ($section === 'parents' && $studentId) {
                    DB::table('students')->where('id', $studentId)->update(['parent_id' => $id, 'updated_at' => now()]);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['record' => 'The record could not be saved. Check for duplicate values or related records.'])->withInput();
        }

        if ($section === 'results') {
            try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); }
        }
        return back()->with('success', $section === 'results' ? 'CBC assessment saved successfully.' : 'Record added successfully.');
    }

    public function update(Request $request, $id, CbcReportCardService $reportCards)
    {
        $section = $request->input('section');
        abort_unless(isset($this->tables[$section]), 404);
        abort_unless(DB::table($this->tables[$section])->where('id', $id)->whereNull('archived_at')->exists(), 404);
        $data = $this->prepare($section, $request->validate($this->rules($section, $id)), $request, $id);

        try {
            DB::table($this->tables[$section])->where('id', $id)->update($data + ['updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['record' => 'The record could not be updated. Check for duplicate values or related records.'])->withInput();
        }
        if ($section === 'results') {
            try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); }
        }
        return redirect()->route('admin.operations', ['section' => $section])->with('success', 'Record updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $section = $request->input('section');
        abort_unless(isset($this->tables[$section]), 404);
        $table = $this->tables[$section];
        abort_unless(DB::table($table)->where('id', $id)->whereNull('archived_at')->exists(), 404);

        DB::table($table)->where('id', $id)->update(['archived_at' => now(), 'updated_at' => now()]);
        return back()->with('success', 'Record archived successfully. It is no longer shown in active records.');
    }

    public function attendance(Request $request)
    {
        $data = $request->validate($this->rules('attendance'));
        DB::table('attendance')->updateOrInsert(
            ['student_id' => $data['student_id'], 'attendance_date' => $data['attendance_date']],
            $data + ['updated_at' => now()]
        );
        return back()->with('success', 'Attendance saved successfully.');
    }

    public function result(Request $request, CbcReportCardService $reportCards)
    {
        $data = $this->prepare('results', $request->validate($this->rules('results')), $request);
        DB::table('results')->updateOrInsert(
            ['exam_id' => $data['exam_id'], 'student_id' => $data['student_id'], 'subject_id' => $data['subject_id']],
            $data + ['updated_at' => now()]
        );
        try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); }
        return back()->with('success', $data['assessment_status'] === 'missed' ? 'Missed assessment recorded.' : 'CBC result saved.');
    }

    private function rules($section, $id = null)
    {
        $rules = [
            'parents' => [
                'name' => 'required|string|max:150',
                'phone' => ['required', 'regex:/^(?:\\+254|0)7\\d{8}$/'],
                'email' => 'nullable|email|max:150',
                'relationship' => 'nullable|string|max:50',
                'student_id' => 'nullable|exists:students,id',
            ],
            'classes' => ['name' => 'required|string|max:100', 'stream' => 'nullable|string|max:50', 'academic_year' => 'nullable|integer|min:2000|max:2100', 'class_teacher_id' => 'nullable|exists:teachers,id'],
            'teachers' => ['name' => 'required|string|max:150', 'email' => 'nullable|email|max:150', 'phone' => ['nullable', 'regex:/^(?:\\+254|0)7\\d{8}$/'], 'employee_number' => ['nullable', 'string', 'max:50']],
            'subjects' => ['name' => 'required|string|max:100', 'code' => ['nullable', 'string', 'max:30'], 'teacher_id' => 'nullable|exists:teachers,id'],
            'attendance' => ['student_id' => 'required|exists:students,id', 'attendance_date' => 'required|date', 'status' => 'required|in:present,absent,late,excused', 'notes' => 'nullable|string|max:500'],
            'exams' => ['name' => 'required|string|max:150', 'term' => 'required|string|max:50', 'academic_year' => 'required|integer|min:2000|max:2100', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date'],
            'results' => ['exam_id' => 'required|exists:exams,id', 'student_id' => 'required|exists:students,id', 'subject_id' => 'required|exists:subjects,id', 'assessment_status' => 'required|in:present,missed', 'marks' => 'nullable|required_if:assessment_status,present|numeric|min:0|max:100', 'remarks' => 'nullable|string|max:500'],
            'announcements' => ['title' => 'required|string|max:200', 'body' => 'required|string|max:10000', 'published' => 'nullable|boolean'],
            'events' => ['title' => 'required|string|max:200', 'event_date' => 'required|date', 'location' => 'nullable|string|max:200', 'description' => 'nullable|string|max:10000'],
        ];
        if ($section === 'teachers') $rules[$section]['employee_number'][] = Rule::unique('teachers', 'employee_number')->ignore($id)->whereNull('archived_at');
        if ($section === 'subjects') $rules[$section]['code'][] = Rule::unique('subjects', 'code')->ignore($id)->whereNull('archived_at');
        return $rules[$section];
    }

    private function prepare($section, array $data, Request $request, $id = null)
    {
        if ($section === 'announcements') {
            $data['published'] = $request->boolean('published');
            $data['published_at'] = $data['published'] ? now() : null;
        }
        if ($section === 'results') {
            $level = app(CbcReportCardService::class)->level($data['marks'] === null ? null : (float) $data['marks']);
            if ($data['assessment_status'] === 'missed') {
                $data['marks'] = null; $data['grade'] = null; $data['achievement_level'] = 'MISSED'; $data['achievement_points'] = null;
            } else {
                $data['grade'] = $level['code']; $data['achievement_level'] = $level['code']; $data['achievement_points'] = $level['points'];
            }
        }
        return $data;
    }
}
