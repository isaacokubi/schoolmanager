<?php

namespace App\Http\Controllers;

use App\Services\CbcReportCardService;
use Barryvdh\DomPDF\Facade as Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
/**
 * Controller for the report card workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

class ReportCardController extends Controller
{
    /**
     * Load and display the requested record or page.
     */
    public function show(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $report = $service->build($student, $exam);
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless($report, 404);
        if (!$this->canAccess($request, $report['student'])) abort(403);
        $this->decorate($report, $service);
        $canParentSign = $this->canParentSign($request, $report['student']);
        // Render the Blade template and pass it the data prepared by this controller.
        return view('reports.cbc-report-card', $report + ['schoolName'=>config('app.name'),'printMode'=>$request->boolean('print'),'downloadMode'=>false,'canParentSign'=>$canParentSign]);
    }

    /**
     * Prepare and return the requested file or downloadable response.
     */
    public function download(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $report = $service->build($student, $exam);
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless($report, 404);
        if (!$this->canAccess($request, $report['student'])) abort(403);
        $this->decorate($report, $service);
        $filename='CBC-Report-Card-'.preg_replace('/[^A-Za-z0-9_-]+/','-',$report['student']->name).'-'.$report['exam']->academic_year.'.pdf';
        $pdf=Pdf::loadView('reports.cbc-report-card',$report+['schoolName'=>config('app.name'),'printMode'=>false,'downloadMode'=>true,'canParentSign'=>false])->setPaper('a4','portrait');
        return $pdf->download($filename);
    }

    /**
     * Validate the signing request and create or store the relevant signature information.
     */
    public function sign(Request $request,CbcReportCardService $service,int $student,int $exam)
    {
        $report=$service->build($student,$exam); abort_unless($report,404); if(!$this->canParentSign($request,$report['student']))abort(403);
        // Validate request input before this workflow uses it or writes changes to the database.
        $request->validate(['parent_signature'=>'required|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $this->decorate($report,$service); $hash=$service->currentContentHash($report['results'],$student,$exam); $existing=DB::table('report_cards')->where('student_id',$student)->where('exam_id',$exam)->first();
        if($existing&&$existing->parent_signature_path)Storage::disk('public')->delete($existing->parent_signature_path);
        $path=$request->file('parent_signature')->store('signatures/parents/report-cards','public');
        $payload=['student_id'=>$student,'exam_id'=>$exam,'content_hash'=>$hash,'generated_at'=>$existing&&$existing->generated_at?$existing->generated_at:now(),'parent_signature_path'=>$path,'parent_signed_by'=>$request->user()->id,'parent_signed_at'=>now(),'updated_at'=>now()];
        if($existing)DB::table('report_cards')->where('id',$existing->id)->update($payload);else{ $payload['created_at']=now();$payload['notification_status']='pending';DB::table('report_cards')->insert($payload); }
        return back()->with('success','Your signature has been recorded on this CBC report card.');
    }

    /**
     * Perform the required notification or email step using the configured application service.
     */
    public function notify(Request $request,CbcReportCardService $service,int $student,int $exam)
    {
        $result=$service->generateAndNotify($student,$exam); if(!$result['complete'])return back()->withErrors(['report'=>$result['reason']]); if(!empty($result['errors']))return back()->withErrors(['report'=>'The report was generated, but some notifications could not be sent.']); return back()->with('success','CBC report card generated and sent to '.($result['sent']??0).' recipient(s).');
    }

    /**
     * Helper method for ReportCardController.php; it performs the decorate step and returns or updates data used by its caller.
     */
    private function decorate(array &$report,CbcReportCardService $service):void
    {
        foreach($report['results'] as $result){$level=$service->level($result->marks===null?null:(float)$result->marks);$result->achievement_level=$level['code']==='MISSED'?null:$level['code'];$result->achievement_points=$level['points'];$result->display_remark=$result->remarks?:$service->remark($result->marks===null?null:(float)$result->marks,$result->assessment_status);}
    }

    /**
     * Helper method for ReportCardController.php; it performs the canAccess step and returns or updates data used by its caller.
     */
    private function canAccess(Request $request,$student):bool
    {
        $user=$request->user();
        if(in_array($user->role,['admin','manager'],true))return true;
        $profile=DB::table('portal_profiles')->where('user_id',$user->id)->first();
        if(!$profile||!$profile->active)return false;
        if($profile->portal_type==='teacher'){
            $teacher=DB::table('teachers')->where('email',$user->email)->first();
            return $teacher && DB::table('results')->join('subjects','subjects.id','=','results.subject_id')->where('results.student_id',$student->id)->where('subjects.teacher_id',$teacher->id)->exists();
        }
        if($profile->admission_number&&$profile->admission_number===$student->admission_number&&in_array($profile->portal_type,['pupil','parent','sponsor'],true))return true;
        if($profile->portal_type==='parent'&&$student->parent_id)return DB::table('parents')->where('id',$student->parent_id)->where('email',$user->email)->exists();
        return false;
    }

    /**
     * Helper method for ReportCardController.php; it performs the canParentSign step and returns or updates data used by its caller.
     */
    private function canParentSign(Request $request,$student):bool
    {
        $user=$request->user(); $profile=DB::table('portal_profiles')->where('user_id',$user->id)->where('portal_type','parent')->where('active',true)->first(); if(!$profile)return false; if($student->parent_id&&DB::table('parents')->where('id',$student->parent_id)->where('email',$user->email)->exists())return true; return $profile->admission_number&&$profile->admission_number===$student->admission_number;
    }
}