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
            'students' => DB::table('students')->whereNull('archived_at')->orderBy('name')->get(),
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
            $query = DB::table('parents')->select('parents.*')->selectSub(function ($q) {
                $q->from('students')->selectRaw('count(*)')->whereColumn('students.parent_id', 'parents.id')->whereNull('students.archived_at');
            }, 'learner_count')->whereNull('parents.archived_at')->orderByDesc('parents.id');
        } elseif ($section === 'classes') {
            $query = DB::table('school_classes')->leftJoin('teachers', function ($join) {
                $join->on('teachers.id', '=', 'school_classes.class_teacher_id')->whereNull('teachers.archived_at');
            })->select('school_classes.*', 'teachers.name as teacher_name')->whereNull('school_classes.archived_at')->orderByDesc('school_classes.id');
        } elseif ($section === 'teachers') {
            $query = DB::table('teachers')->select('teachers.*')->selectSub(function ($q) {
                $q->from('subjects')->selectRaw('count(*)')->whereColumn('subjects.teacher_id', 'teachers.id')->whereNull('subjects.archived_at');
            }, 'subject_count')->whereNull('teachers.archived_at')->orderByDesc('teachers.id');
        } elseif ($section === 'subjects') {
            $query = DB::table('subjects')->leftJoin('teachers', function ($join) {
                $join->on('teachers.id', '=', 'subjects.teacher_id')->whereNull('teachers.archived_at');
            })->select('subjects.*', 'teachers.name as teacher_name')->whereNull('subjects.archived_at')->orderByDesc('subjects.id');
        } elseif ($section === 'attendance') {
            $query = DB::table('attendance')->leftJoin('students', function ($join) {
                $join->on('students.id', '=', 'attendance.student_id')->whereNull('students.archived_at');
            })->select('attendance.*', 'students.name as student_name', 'students.admission_number')->whereNull('attendance.archived_at')->orderByDesc('attendance.attendance_date')->orderByDesc('attendance.id');
        } elseif ($section === 'results') {
            $query = DB::table('results')->leftJoin('students', function ($join) {
                $join->on('students.id', '=', 'results.student_id')->whereNull('students.archived_at');
            })->leftJoin('exams', function ($join) {
                $join->on('exams.id', '=', 'results.exam_id')->whereNull('exams.archived_at');
            })->leftJoin('subjects', function ($join) {
                $join->on('subjects.id', '=', 'results.subject_id')->whereNull('subjects.archived_at');
            })->select('results.*', 'students.name as student_name', 'students.admission_number', 'exams.name as exam_name', 'subjects.name as subject_name')->whereNull('results.archived_at')->orderByDesc('results.id');
        } else {
            $query = DB::table($table)->whereNull($table . '.archived_at')->orderByDesc('id');
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            if ($section === 'parents') {
                $digits = preg_replace('/\D+/', '', $search);
                $query->where(function ($q) use ($search, $digits) {
                    $q->where('parents.name', 'like', '%' . $search . '%')->orWhere('parents.phone', 'like', '%' . $search . '%')->orWhere('parents.email', 'like', '%' . $search . '%')->orWhere('parents.relationship', 'like', '%' . $search . '%');
                    if ($digits !== '') $q->orWhere('parents.phone', 'like', '%' . $digits . '%');
                });
            } elseif ($section === 'classes') {
                $query->where(function ($q) use ($search) { $q->where('school_classes.name', 'like', '%' . $search . '%')->orWhere('school_classes.stream', 'like', '%' . $search . '%')->orWhere('school_classes.academic_year', 'like', '%' . $search . '%')->orWhere('teachers.name', 'like', '%' . $search . '%'); });
            } elseif ($section === 'teachers') {
                $query->where(function ($q) use ($search) { $q->where('teachers.name', 'like', '%' . $search . '%')->orWhere('teachers.email', 'like', '%' . $search . '%')->orWhere('teachers.phone', 'like', '%' . $search . '%')->orWhere('teachers.employee_number', 'like', '%' . $search . '%'); });
            } elseif ($section === 'subjects') {
                $query->where(function ($q) use ($search) { $q->where('subjects.name', 'like', '%' . $search . '%')->orWhere('subjects.code', 'like', '%' . $search . '%')->orWhere('teachers.name', 'like', '%' . $search . '%'); });
            } elseif ($section === 'attendance') {
                $query->where(function ($q) use ($search) { $q->where('students.name', 'like', '%' . $search . '%')->orWhere('students.admission_number', 'like', '%' . $search . '%')->orWhere('attendance.status', 'like', '%' . $search . '%')->orWhere('attendance.attendance_date', 'like', '%' . $search . '%'); });
            } elseif ($section === 'results') {
                $query->where(function ($q) use ($search) { $q->where('students.name', 'like', '%' . $search . '%')->orWhere('students.admission_number', 'like', '%' . $search . '%')->orWhere('exams.name', 'like', '%' . $search . '%')->orWhere('subjects.name', 'like', '%' . $search . '%')->orWhere('results.achievement_level', 'like', '%' . $search . '%')->orWhere('results.assessment_status', 'like', '%' . $search . '%'); });
            } else {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $searchable = array_values(array_filter($columns, function ($column) { return !in_array($column, ['id', 'created_at', 'updated_at', 'archived_at'], true); }));
                if ($searchable) $query->where(function ($q) use ($searchable, $search, $table) { foreach ($searchable as $column) $q->orWhere($table . '.' . $column, 'like', '%' . $search . '%'); });
            }
        }

        $records = $query->paginate(10)->withQueryString();
        if ($section === 'results') {
            $cbc = app(CbcReportCardService::class);
            $records->getCollection()->transform(function ($row) use ($cbc) {
                if ($row->assessment_status === 'missed') $row->achievement_level = 'MISSED';
                elseif ($row->marks !== null) $row->achievement_level = $cbc->level((float) $row->marks)['code'];
                return $row;
            });
        }
        return $records;
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
                if ($section === 'parents' && $studentId) DB::table('students')->where('id', $studentId)->whereNull('archived_at')->update(['parent_id' => $id, 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['record' => 'The record could not be saved. Check the details and try again.'])->withInput();
        }
        if ($section === 'results') { try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); } }
        return back()->with('success', $section === 'results' ? 'CBC assessment saved successfully.' : 'Record added successfully.');
    }

    public function update(Request $request, $id, CbcReportCardService $reportCards)
    {
        $section = $request->input('section');
        abort_unless(isset($this->tables[$section]), 404);
        $table = $this->tables[$section];
        abort_unless(DB::table($table)->where('id', $id)->whereNull('archived_at')->exists(), 404);
        $validated = $request->validate($this->rules($section, $id));

        if ($section === 'parents') {
            $studentId = $validated['student_id'] ?? null;
            unset($validated['student_id']);
            $validated['phone'] = $this->normalizeKenyanPhone($validated['phone'] ?? null);
            DB::transaction(function () use ($id, $validated, $studentId) {
                DB::table('parents')->where('id', $id)->update($validated + ['updated_at' => now()]);
                if ($studentId) {
                    DB::table('students')->where('id', $studentId)->whereNull('archived_at')->update(['parent_id' => $id, 'updated_at' => now()]);
                }
            });
        } else {
            $data = $this->prepare($section, $validated, $request, $id);
            try { DB::table($table)->where('id', $id)->update($data + ['updated_at' => now()]); }
            catch (\Throwable $e) { report($e); return back()->withErrors(['record' => 'The record could not be updated. Check the details and try again.'])->withInput(); }
            if ($section === 'results') { try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); } }
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
        DB::transaction(function () use ($data) { DB::table('attendance')->updateOrInsert(['student_id' => $data['student_id'], 'attendance_date' => $data['attendance_date']], $data + ['archived_at' => null, 'updated_at' => now()]); });
        return back()->with('success', 'Attendance saved successfully.');
    }

    public function result(Request $request, CbcReportCardService $reportCards)
    {
        $data = $this->prepare('results', $request->validate($this->rules('results')), $request);
        DB::transaction(function () use ($data) { DB::table('results')->updateOrInsert(['exam_id' => $data['exam_id'], 'student_id' => $data['student_id'], 'subject_id' => $data['subject_id']], $data + ['archived_at' => null, 'updated_at' => now()]); });
        try { $reportCards->generateAndNotify((int) $data['student_id'], (int) $data['exam_id']); } catch (\Throwable $e) { report($e); }
        return back()->with('success', $data['assessment_status'] === 'missed' ? 'Missed assessment recorded.' : 'CBC result saved.');
    }

    private function rules($section, $id = null)
    {
        $activeTeacher = Rule::exists('teachers', 'id')->whereNull('archived_at');
        $activeExam = Rule::exists('exams', 'id')->whereNull('archived_at');
        $activeSubject = Rule::exists('subjects', 'id')->whereNull('archived_at');
        $activeStudent = Rule::exists('students', 'id')->whereNull('archived_at');
        $kenyaMobile = 'regex:/^(?:\+254|0)(?:1|7)\d{8}$/';
        $rules = [
            'parents' => ['name' => 'required|string|max:150', 'phone' => ['required', $kenyaMobile], 'email' => 'nullable|email|max:150', 'relationship' => 'nullable|string|max:50', 'student_id' => ['nullable', $activeStudent]],
            'classes' => ['name' => 'required|string|max:100', 'stream' => 'nullable|string|max:50', 'academic_year' => 'nullable|integer|min:2000|max:2100', 'class_teacher_id' => ['nullable', $activeTeacher]],
            'teachers' => ['name' => 'required|string|max:150', 'email' => 'nullable|email|max:150', 'phone' => ['nullable', $kenyaMobile], 'employee_number' => ['nullable', 'string', 'max:50']],
            'subjects' => ['name' => 'required|string|max:100', 'code' => ['nullable', 'string', 'max:30'], 'teacher_id' => ['nullable', $activeTeacher]],
            'attendance' => ['student_id' => ['required', $activeStudent], 'attendance_date' => 'required|date', 'status' => 'required|in:present,absent,late,excused', 'notes' => 'nullable|string|max:500'],
            'exams' => ['name' => 'required|string|max:150', 'term' => ['required', Rule::in(['Term 1', 'Term 2', 'Term 3'])], 'academic_year' => 'required|integer|min:2000|max:2100', 'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date'],
            'results' => ['exam_id' => ['required', $activeExam], 'student_id' => ['required', $activeStudent], 'subject_id' => ['required', $activeSubject], 'assessment_status' => 'required|in:present,missed', 'marks' => 'nullable|required_if:assessment_status,present|numeric|min:0|max:100', 'remarks' => 'nullable|string|max:500'],
            'announcements' => ['title' => 'required|string|max:200', 'body' => 'required|string|max:10000', 'published' => 'nullable|boolean'],
            'events' => ['title' => 'required|string|max:200', 'event_date' => 'required|date', 'location' => 'nullable|string|max:200', 'description' => 'nullable|string|max:10000'],
        ];
        if ($section === 'teachers') $rules[$section]['employee_number'][] = Rule::unique('teachers', 'employee_number')->ignore($id)->whereNull('archived_at');
        if ($section === 'subjects') $rules[$section]['code'][] = Rule::unique('subjects', 'code')->ignore($id)->whereNull('archived_at');
        return $rules[$section];
    }

    private function prepare($section, array $data, Request $request, $id = null)
    {
        if ($section === 'parents') $data['phone'] = $this->normalizeKenyanPhone($data['phone'] ?? null);
        if ($section === 'teachers') $data['phone'] = $this->normalizeKenyanPhone($data['phone'] ?? null);
        if ($section === 'announcements') { $data['published'] = $request->boolean('published'); $data['published_at'] = $data['published'] ? now() : null; }
        if ($section === 'results') {
            $level = app(CbcReportCardService::class)->level($data['marks'] === null ? null : (float) $data['marks']);
            if ($data['assessment_status'] === 'missed') { $data['marks'] = null; $data['grade'] = null; $data['achievement_level'] = 'MISSED'; $data['achievement_points'] = null; }
            else { $data['grade'] = $level['code']; $data['achievement_level'] = $level['code']; $data['achievement_points'] = $level['points']; }
        }
        return $data;
    }

    private function normalizeKenyanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 12 && substr($digits, 0, 3) === '254') return '+' . $digits;
        if (strlen($digits) === 10 && (substr($digits, 0, 2) === '07' || substr($digits, 0, 2) === '01')) return '+254' . substr($digits, 1);
        return $phone;
    }
}
