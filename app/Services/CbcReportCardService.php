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

    /**
     * Return a self-contained image data URI for HTML and DomPDF.
     * Signature files are stored on the public disk, but older records may contain
     * /storage/ URLs or full public-disk URLs. Normalize all supported forms here.
     * WebP is converted to PNG when GD supports it because DomPDF installations
     * commonly have incomplete WebP support even though browsers can display it.
     */
    private function imageData(?string $path): ?string
    {
        if (!$path) return null;

        $disk = Storage::disk('public');
        $relativePath = trim($path);
        $parsed = parse_url($relativePath);

        if ($parsed !== false && !empty($parsed['path'])) {
            $relativePath = $parsed['path'];
        }

        $relativePath = preg_replace('#^/+#', '', $relativePath);
        $relativePath = preg_replace('#^storage/#', '', $relativePath);
        $relativePath = urldecode($relativePath);

        if (!$disk->exists($relativePath)) {
            return null;
        }

        $bytes = $disk->get($relativePath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $absolutePath = $disk->path($relativePath);
        $mime = null;
        if (function_exists('mime_content_type') && is_file($absolutePath)) {
            $mime = mime_content_type($absolutePath) ?: null;
        }
        if (!$mime) {
            $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $mime = 'image/jpeg';
            } elseif ($extension === 'png') {
                $mime = 'image/png';
            } elseif ($extension === 'webp') {
                $mime = 'image/webp';
            } elseif ($extension === 'gif') {
                $mime = 'image/gif';
            } else {
                $mime = 'application/octet-stream';
            }
        }

        // DomPDF support for WebP depends on the installed image backend.
        // Convert it to PNG when possible so both browser printing and PDF output
        // use a format with consistent support.
        if ($mime === 'image/webp' && function_exists('imagecreatefromwebp') && function_exists('imagepng')) {
            $source = @imagecreatefromwebp($absolutePath);
            if ($source !== false) {
                ob_start();
                imagepng($source);
                $pngBytes = ob_get_clean();
                imagedestroy($source);
                if ($pngBytes !== false && $pngBytes !== '') {
                    $bytes = $pngBytes;
                    $mime = 'image/png';
                }
            }
        }

        if ($mime === 'application/octet-stream') return null;
        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
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
