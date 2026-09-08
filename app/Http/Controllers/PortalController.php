<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();

        $students = collect();
        $studentMetrics = [];
        $teacher = null;
        $subjects = collect();
        $attendanceSummary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
        $recentResults = collect();
        $announcements = collect();
        $upcomingEvents = collect();
        $dashboardStats = [];

        if ($profile && $profile->portal_type === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();

            if ($teacher) {
                $subjects = DB::table('subjects')
                    ->where('teacher_id', $teacher->id)
                    ->orderBy('name')
                    ->get();

                $subjectIds = $subjects->pluck('id');
                $teacherStudentCount = $subjectIds->isEmpty()
                    ? 0
                    : DB::table('results')->whereIn('subject_id', $subjectIds)->distinct()->count('student_id');
                $resultCount = $subjectIds->isEmpty()
                    ? 0
                    : DB::table('results')->whereIn('subject_id', $subjectIds)->count();

                $recentResults = $subjectIds->isEmpty()
                    ? collect()
                    : DB::table('results')
                        ->join('students', 'students.id', '=', 'results.student_id')
                        ->join('subjects', 'subjects.id', '=', 'results.subject_id')
                        ->join('exams', 'exams.id', '=', 'results.exam_id')
                        ->whereIn('results.subject_id', $subjectIds)
                        ->orderByDesc('results.created_at')
                        ->select('results.*', 'students.name as student_name', 'students.admission_number', 'subjects.name as subject_name', 'exams.name as exam_name')
                        ->limit(8)
                        ->get();

                $dashboardStats = [
                    ['label' => 'Assigned subjects', 'value' => $subjects->count(), 'meta' => 'Current teaching allocation'],
                    ['label' => 'Learners assessed', 'value' => $teacherStudentCount, 'meta' => 'Learners with recorded results'],
                    ['label' => 'Results recorded', 'value' => $resultCount, 'meta' => 'Across your subjects'],
                    ['label' => 'School learners', 'value' => DB::table('students')->count(), 'meta' => 'Current learner population'],
                ];
            }
        } elseif ($profile && $profile->portal_type === 'parent') {
            $parentId = DB::table('parents')->where('email', $user->email)->value('id');
            $students = $parentId
                ? DB::table('students')->where('parent_id', $parentId)->orderBy('name')->get()
                : ($profile->admission_number ? DB::table('students')->where('admission_number', $profile->admission_number)->get() : collect());
        } elseif ($profile && $profile->admission_number) {
            $students = DB::table('students')->where('admission_number', $profile->admission_number)->get();
        }

        $studentIds = $students->pluck('id');

        if ($studentIds->isNotEmpty()) {
            $attendanceRows = DB::table('attendance')
                ->whereIn('student_id', $studentIds)
                ->select('student_id', 'status', DB::raw('COUNT(*) as total'))
                ->groupBy('student_id', 'status')
                ->get();

            foreach ($attendanceRows as $row) {
                $studentMetrics[$row->student_id]['attendance'][$row->status] = (int) $row->total;
                $attendanceSummary[$row->status] = ($attendanceSummary[$row->status] ?? 0) + (int) $row->total;
            }

            foreach ($students as $student) {
                $metrics = $studentMetrics[$student->id]['attendance'] ?? [];
                $studentMetrics[$student->id] = [
                    'attendance' => [
                        'present' => (int) ($metrics['present'] ?? 0),
                        'absent' => (int) ($metrics['absent'] ?? 0),
                        'late' => (int) ($metrics['late'] ?? 0),
                        'excused' => (int) ($metrics['excused'] ?? 0),
                    ],
                    'results' => DB::table('results')->where('student_id', $student->id)->count(),
                ];
            }

            $recentResults = DB::table('results')
                ->join('students', 'students.id', '=', 'results.student_id')
                ->join('subjects', 'subjects.id', '=', 'results.subject_id')
                ->join('exams', 'exams.id', '=', 'results.exam_id')
                ->whereIn('results.student_id', $studentIds)
                ->orderByDesc('results.created_at')
                ->select('results.*', 'students.name as student_name', 'students.admission_number', 'subjects.name as subject_name', 'exams.name as exam_name')
                ->limit(10)
                ->get();

            $dashboardStats = [
                ['label' => 'Linked learners', 'value' => $students->count(), 'meta' => 'Learners connected to this account'],
                ['label' => 'Attendance records', 'value' => array_sum($attendanceSummary), 'meta' => 'Recorded attendance'],
                ['label' => 'Present', 'value' => $attendanceSummary['present'], 'meta' => 'Attendance marked present'],
                ['label' => 'Results available', 'value' => $recentResults->count(), 'meta' => 'Recent academic results'],
            ];
        } elseif ($profile && in_array($profile->portal_type, ['pupil', 'parent', 'sponsor'], true)) {
            $dashboardStats = [
                ['label' => 'Linked learners', 'value' => 0, 'meta' => 'No learner record linked yet'],
                ['label' => 'Attendance records', 'value' => 0, 'meta' => 'No attendance available'],
                ['label' => 'Present', 'value' => 0, 'meta' => 'No attendance available'],
                ['label' => 'Results available', 'value' => 0, 'meta' => 'No academic results available'],
            ];
        }

        $announcements = DB::table('announcements')
            ->where('published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $upcomingEvents = DB::table('events')
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        return view('portal.dashboard', compact(
            'user',
            'profile',
            'students',
            'studentMetrics',
            'teacher',
            'subjects',
            'attendanceSummary',
            'recentResults',
            'announcements',
            'upcomingEvents',
            'dashboardStats'
        ));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
