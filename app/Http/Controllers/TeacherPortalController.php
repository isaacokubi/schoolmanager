<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherPortalController extends Controller
{
    public function learners(Request $request)
    {
        $teacher = $this->teacher($request);
        $subjects = $this->subjects($teacher->id);
        $subjectIds = $subjects->pluck('id');

        $students = $subjectIds->isEmpty() ? collect() : DB::table('students')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')
            ->whereNull('students.archived_at')
            ->whereIn('students.id', function ($query) use ($subjectIds) {
                $query->from('results')->select('student_id')->whereIn('subject_id', $subjectIds)->whereNull('archived_at');
            })
            ->select('students.*', 'school_classes.name as class_label', 'school_classes.stream')
            ->orderBy('students.name')
            ->distinct()
            ->get();

        $studentIds = $students->pluck('id');
        $attendance = $studentIds->isEmpty() ? collect() : DB::table('attendance')
            ->whereIn('student_id', $studentIds)
            ->select('student_id', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $results = $studentIds->isEmpty() ? collect() : DB::table('results')
            ->whereIn('student_id', $studentIds)
            ->whereIn('subject_id', $subjectIds)
            ->whereNull('archived_at')
            ->select('student_id', DB::raw('COUNT(*) as total_results'), DB::raw('AVG(marks) as average_marks'))
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        foreach ($students as $student) {
            $studentAttendance = $attendance->get($student->id, collect())->keyBy('status');
            $metric = $results->get($student->id);
            $student->present = (int) ($studentAttendance->get('present')->total ?? 0);
            $student->absent = (int) ($studentAttendance->get('absent')->total ?? 0);
            $student->late = (int) ($studentAttendance->get('late')->total ?? 0);
            $student->excused = (int) ($studentAttendance->get('excused')->total ?? 0);
            $student->attendance_total = $student->present + $student->absent + $student->late + $student->excused;
            $student->attendance_rate = $student->attendance_total ? round(($student->present / $student->attendance_total) * 100) : null;
            $student->result_count = (int) ($metric->total_results ?? 0);
            $student->average_marks = $metric ? round((float) $metric->average_marks, 1) : null;
        }

        return view('portal.teacher-learners', compact('teacher', 'subjects', 'students'));
    }

    public function attendance(Request $request)
    {
        $teacher = $this->teacher($request);
        $subjects = $this->subjects($teacher->id);
        $subjectIds = $subjects->pluck('id');
        $date = $request->input('date', now()->toDateString());
        $date = date('Y-m-d', strtotime($date));

        $students = $subjectIds->isEmpty() ? collect() : DB::table('students')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')
            ->whereNull('students.archived_at')
            ->whereIn('students.id', function ($query) use ($subjectIds) {
                $query->from('results')->select('student_id')->whereIn('subject_id', $subjectIds)->whereNull('archived_at');
            })
            ->select('students.*', 'school_classes.name as class_label', 'school_classes.stream')
            ->orderBy('students.name')
            ->distinct()
            ->get();

        $studentIds = $students->pluck('id');
        $records = $studentIds->isEmpty() ? collect() : DB::table('attendance')->whereIn('student_id', $studentIds)->whereDate('attendance_date', $date)->get()->keyBy('student_id');

        foreach ($students as $student) {
            $student->attendance_status = optional($records->get($student->id))->status ?: 'present';
            $student->attendance_notes = optional($records->get($student->id))->notes ?: '';
        }

        return view('portal.teacher-attendance', compact('teacher', 'subjects', 'students', 'date'));
    }

    public function storeAttendance(Request $request)
    {
        $teacher = $this->teacher($request);
        $data = $request->validate([
            'attendance_date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'required|in:present,absent,late,excused',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:500',
        ]);

        $subjectIds = $this->subjects($teacher->id)->pluck('id');
        $allowedStudentIds = DB::table('results')->whereIn('subject_id', $subjectIds)->whereNull('archived_at')->pluck('student_id')->unique();
        $date = $data['attendance_date'];

        DB::transaction(function () use ($data, $allowedStudentIds, $date) {
            foreach ($data['attendance'] as $studentId => $status) {
                $studentId = (int) $studentId;
                abort_unless($allowedStudentIds->contains($studentId), 403);
                DB::table('attendance')->updateOrInsert(
                    ['student_id' => $studentId, 'attendance_date' => $date],
                    ['status' => $status, 'notes' => $data['notes'][$studentId] ?? null, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        });

        return redirect()->route('portal.teacher-attendance', ['date' => $date])->with('success', 'Attendance saved successfully for ' . count($data['attendance']) . ' learner(s).');
    }

    private function teacher(Request $request)
    {
        $profile = DB::table('portal_profiles')
            ->where('user_id', $request->user()->id)
            ->where('portal_type', 'teacher')
            ->where('active', true)
            ->first();
        abort_unless($profile, 403);

        $teacher = DB::table('teachers')->where('email', $request->user()->email)->whereNull('archived_at')->first();
        abort_unless($teacher, 403, 'Your teacher profile is not linked to your login account.');
        return $teacher;
    }

    private function subjects($teacherId)
    {
        return DB::table('subjects')->where('teacher_id', $teacherId)->whereNull('archived_at')->orderBy('name')->get();
    }
}
