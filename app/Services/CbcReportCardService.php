<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CbcReportCardService
{
    public function level(?float $marks): array
    {
        if ($marks === null) return ['code' => 'MISSED', 'label' => 'Missed Assessment', 'points' => null];
        if ($marks >= 90) return ['code' => 'EE1', 'label' => 'Exceeding Expectation 1', 'points' => 8];
        if ($marks >= 75) return ['code' => 'EE2', 'label' => 'Exceeding Expectation 2', 'points' => 7];
        if ($marks >= 58) return ['code' => 'ME1', 'label' => 'Meeting Expectation 1', 'points' => 6];
        if ($marks >= 41) return ['code' => 'ME2', 'label' => 'Meeting Expectation 2', 'points' => 5];
        if ($marks >= 31) return ['code' => 'AE1', 'label' => 'Approaching Expectation 1', 'points' => 4];
        if ($marks >= 21) return ['code' => 'AE2', 'label' => 'Approaching Expectation 2', 'points' => 3];
        if ($marks >= 11) return ['code' => 'BE1', 'label' => 'Below Expectation 1', 'points' => 2];
        return ['code' => 'BE2', 'label' => 'Below Expectation 2', 'points' => 1];
    }

    public function remark(?float $marks, string $status): string
    {
        if ($status === 'missed') return 'Assessment missed. Follow-up assessment required.';
        if ($marks === null) return 'No score recorded.';
        return $this->level($marks)['label'];
    }

    public function currentContentHash($results, int $studentId, int $examId): string
    {
        return hash('sha256', json_encode([
            'student' => $studentId,
            'exam' => $examId,
            'results' => $results->map(function ($r) {
                return [$r->subject_id, $r->marks, $r->assessment_status, $r->achievement_level];
            })->values()->all(),
        ]));
    }

    public function build(int $studentId, int $examId): ?array
    {
        $student = DB::table('students')->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')
            ->select('students.*', 'school_classes.name as class_label', 'school_classes.stream as class_stream', 'school_classes.class_teacher_id')
            ->where('students.id', $studentId)->first();
        $exam = DB::table('exams')->find($examId);
        if (!$student || !$exam) return null;

        $results = DB::table('results')->join('subjects', 'subjects.id', '=', 'results.subject_id')
            ->where('results.student_id', $studentId)->where('results.exam_id', $examId)
            ->orderBy('subjects.name')->select('results.*', 'subjects.name as subject_name', 'subjects.code as subject_code')->get();
        if ($results->isEmpty()) return null;

        $expectedSubjectIds = DB::table('subjects')->pluck('id');
        $recordedSubjectIds = $results->pluck('subject_id');
        $complete = $expectedSubjectIds->isNotEmpty() && $expectedSubjectIds->diff($recordedSubjectIds)->isEmpty();

        $present = $results->where('assessment_status', '!=', 'missed')->filter(function ($r) { return $r->marks !== null; });
        $points = $present->sum(function ($result) {
            return $this->level((float) $result->marks)['points'];
        });
        $average = $present->count() ? round($present->avg('marks'), 1) : null;
        $attendance = DB::table('attendance')->where('student_id', $studentId)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
        $parent = $student->parent_id ? DB::table('parents')->find($student->parent_id) : null;

        $classTeacher = $student->class_teacher_id ? DB::table('teachers')->find($student->class_teacher_id) : null;
        if (!$classTeacher) {
            $teacherIds = DB::table('subjects')->whereIn('id', $results->pluck('subject_id')->all())->whereNotNull('teacher_id')->pluck('teacher_id');
            if ($teacherIds->isNotEmpty()) $classTeacher = DB::table('teachers')->whereIn('id', $teacherIds->all())->orderBy('name')->first();
        }
        if (!$classTeacher) $classTeacher = DB::table('teachers')->orderBy('name')->first();

        $headUserId = DB::table('settings')->where('key', 'head_of_institution_user_id')->value('value');
        $headOfInstitution = $headUserId ? DB::table('users')->where('id', $headUserId)->whereIn('role', ['admin', 'manager'])->first() : null;
        if (!$headOfInstitution) $headOfInstitution = DB::table('users')->whereIn('role', ['admin', 'manager'])->orderBy('id')->first();

        $schoolBadge = DB::table('settings')->where('key', 'school_badge')->value('value');
        $schoolStamp = DB::table('settings')->where('key', 'school_stamp')->value('value');
        $schoolBadgeData = $this->imageData($schoolBadge);
        $schoolStampData = $this->imageData($schoolStamp);
        $classTeacherSignatureData = $this->imageData($classTeacher ? $classTeacher->signature_path : null);
        $headSignatureData = $this->imageData($headOfInstitution ? $headOfInstitution->signature_path : null);

        $reportCard = DB::table('report_cards')->where('student_id', $studentId)->where('exam_id', $examId)->first();
        $parentSignatureData = $this->imageData($reportCard ? $reportCard->parent_signature_path : null);
        $parentSignedAt = $reportCard ? $reportCard->parent_signed_at : null;

        return compact('student', 'exam', 'results', 'complete', 'points', 'average', 'attendance', 'parent', 'classTeacher', 'headOfInstitution', 'schoolBadgeData', 'schoolStampData', 'classTeacherSignatureData', 'headSignatureData', 'parentSignatureData', 'parentSignedAt');
    }

    private function imageData(?string $path): ?string
    {
        if (!$path || !Storage::disk('public')->exists($path)) return null;
        $absolutePath = Storage::disk('public')->path($path);
        $mime = function_exists('mime_content_type') ? mime_content_type($absolutePath) : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }

    public function generateAndNotify(int $studentId, int $examId): array
    {
        $report = $this->build($studentId, $examId);
        if (!$report) return ['complete' => false, 'sent' => 0, 'reason' => 'No assessment records exist.'];
        if (!$report['complete']) return ['complete' => false, 'sent' => 0, 'reason' => 'Some learning areas have not been recorded yet. Mark a missed assessment where appropriate.'];

        foreach ($report['results'] as $result) {
            $level = $this->level($result->marks === null ? null : (float) $result->marks);
            $result->achievement_level = $level['code'] === 'MISSED' ? null : $level['code'];
            $result->achievement_points = $level['points'];
            $result->display_remark = $this->remark($result->marks === null ? null : (float) $result->marks, $result->assessment_status);
        }

        $hash = $this->currentContentHash($report['results'], $studentId, $examId);
        $existing = DB::table('report_cards')->where('student_id', $studentId)->where('exam_id', $examId)->first();
        if ($existing && $existing->content_hash === $hash && $existing->notification_status === 'sent') return ['complete' => true, 'sent' => 0, 'reason' => 'Report already sent for this version.'];

        if ($existing && $existing->content_hash !== $hash && $existing->parent_signature_path) {
            Storage::disk('public')->delete($existing->parent_signature_path);
        }
        $payload = ['student_id' => $studentId, 'exam_id' => $examId, 'content_hash' => $hash, 'generated_at' => now(), 'notification_status' => 'pending', 'parent_signature_path' => null, 'parent_signed_by' => null, 'parent_signed_at' => null, 'updated_at' => now()];
        if ($existing) DB::table('report_cards')->where('id', $existing->id)->update($payload); else DB::table('report_cards')->insert($payload + ['created_at' => now()]);

        $recipients = $this->recipients($report['student']); $sent = 0; $errors = [];
        foreach ($recipients as $email) {
            try { Mail::send('emails.cbc_report_card', $report, function ($message) use ($email, $report) { $message->to($email)->subject(config('app.name') . ' CBC Report Card — ' . $report['student']->name . ' — ' . $report['exam']->name); }); $sent++; }
            catch (\Throwable $e) { $errors[] = $email . ': ' . $e->getMessage(); }
        }

        DB::table('report_cards')->where('student_id', $studentId)->where('exam_id', $examId)->update(['notification_status' => $errors ? ($sent ? 'partial' : 'failed') : 'sent', 'notified_at' => $sent ? now() : null, 'notification_error' => $errors ? Str::limit(implode(' | ', $errors), 1000) : null, 'updated_at' => now()]);
        return ['complete' => true, 'sent' => $sent, 'recipients' => $recipients, 'errors' => $errors];
    }

    private function recipients($student): array
    {
        $emails = [];
        if ($student->parent_id) { $parentEmail = DB::table('parents')->where('id', $student->parent_id)->value('email'); if ($parentEmail) $emails[] = $parentEmail; }
        $profileEmails = DB::table('portal_profiles')->join('users', 'users.id', '=', 'portal_profiles.user_id')->where('portal_profiles.admission_number', $student->admission_number)->whereIn('portal_profiles.portal_type', ['pupil', 'parent', 'sponsor'])->where('portal_profiles.active', true)->pluck('users.email')->all();
        $emails = array_merge($emails, $profileEmails);
        return array_values(array_unique(array_filter($emails, function ($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); })));
    }
}
