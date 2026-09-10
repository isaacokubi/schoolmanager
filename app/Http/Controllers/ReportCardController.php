<?php

namespace App\Http\Controllers;

use App\Services\CbcReportCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportCardController extends Controller
{
    public function show(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $report = $service->build($student, $exam);
        abort_unless($report, 404);
        if (!$this->canAccess($request, $report['student'])) abort(403);
        $this->decorate($report, $service);
        return view('reports.cbc-report-card', $report + [
            'schoolName' => config('app.name'),
            'printMode' => $request->boolean('print'),
            'downloadMode' => false,
        ]);
    }

    public function download(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $report = $service->build($student, $exam);
        abort_unless($report, 404);
        if (!$this->canAccess($request, $report['student'])) abort(403);
        $this->decorate($report, $service);

        $filename = 'CBC-Report-Card-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', $report['student']->name) . '-' . $report['exam']->academic_year . '.pdf';

        $pdf = Pdf::loadView('reports.cbc-report-card', $report + [
            'schoolName' => config('app.name'),
            'printMode' => false,
            'downloadMode' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function notify(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $result = $service->generateAndNotify($student, $exam);
        if (!$result['complete']) return back()->withErrors(['report' => $result['reason']]);
        if (!empty($result['errors'])) return back()->withErrors(['report' => 'The report was generated, but some notifications could not be sent.']);
        return back()->with('success', 'CBC report card generated and sent to ' . ($result['sent'] ?? 0) . ' recipient(s).');
    }

    private function decorate(array &$report, CbcReportCardService $service): void
    {
        foreach ($report['results'] as $result) {
            $level = $service->level($result->marks === null ? null : (float) $result->marks);
            $result->achievement_level = $level['code'] === 'MISSED' ? null : $level['code'];
            $result->achievement_points = $level['points'];
            $result->display_remark = $service->remark($result->marks === null ? null : (float) $result->marks, $result->assessment_status);
        }
    }

    private function canAccess(Request $request, $student): bool
    {
        $user = $request->user();
        if (in_array($user->role, ['admin', 'manager'], true)) return true;
        $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();
        if (!$profile || !$profile->active) return false;
        if ($profile->admission_number && $profile->admission_number === $student->admission_number && in_array($profile->portal_type, ['pupil', 'parent', 'sponsor'], true)) return true;
        if ($profile->portal_type === 'parent' && $student->parent_id) {
            return DB::table('parents')->where('id', $student->parent_id)->where('email', $user->email)->exists();
        }
        return false;
    }
}
