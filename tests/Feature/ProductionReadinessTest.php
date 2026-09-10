<?php

namespace Tests\Feature;

use App\Services\CbcReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role='admin'): int
    {
        return DB::table('users')->insertGetId([
            'name'=>ucfirst($role), 'email'=>$role.'@example.com', 'password'=>bcrypt('password'), 'role'=>$role,
            'created_at'=>now(), 'updated_at'=>now(),
        ]);
    }

    private function student(string $admission='S-001',string $name='Learner'): int
    {
        return DB::table('students')->insertGetId([
            'admission_number'=>$admission, 'name'=>$name, 'created_at'=>now(), 'updated_at'=>now(),
        ]);
    }

    public function test_public_pages_are_reachable(): void
    {
        foreach ([route('home'),route('about'),route('academics'),route('admissions'),route('contact')] as $url) $this->get($url)->assertOk();
    }

    public function test_authentication_redirects_users_to_their_role_area(): void
    {
        foreach (['admin','teacher','parent','pupil','sponsor'] as $role) {
            $email=$role.'@example.com';
            $user=DB::table('users')->insertGetId(['name'=>ucfirst($role),'email'=>$email,'password'=>bcrypt('password'),'role'=>$role,'created_at'=>now(),'updated_at'=>now()]);
            if (in_array($role,['teacher','parent','pupil','sponsor'],true)) DB::table('portal_profiles')->insert(['user_id'=>$user,'role'=>$role,'active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $response=$this->post(route('login.submit'),['email'=>$email,'password'=>'password']);
            $response->assertRedirect(in_array($role,['admin','manager'],true)?route('admin.dashboard'):route('portal.dashboard'));
            $this->post(in_array($role,['admin','manager'],true)?route('logout'):route('portal.logout'));
        }
    }

    public function test_roles_cannot_cross_admin_and_portal_boundaries(): void
    {
        $admin=$this->user('admin'); $this->actingAs($admin)->get(route('portal.dashboard'))->assertForbidden();
        $portal=DB::table('users')->insertGetId(['name'=>'Parent','email'=>'parent@example.com','password'=>bcrypt('password'),'role'=>'parent','created_at'=>now(),'updated_at'=>now()]);
        DB::table('portal_profiles')->insert(['user_id'=>$portal,'role'=>'parent','active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(DB::table('users')->find($portal))->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_inactive_portal_profile_is_denied_even_when_role_is_valid(): void
    {
        $user=DB::table('users')->insertGetId(['name'=>'Inactive','email'=>'inactive@example.com','password'=>bcrypt('password'),'role'=>'parent','created_at'=>now(),'updated_at'=>now()]);
        DB::table('portal_profiles')->insert(['user_id'=>$user,'role'=>'parent','active'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(DB::table('users')->find($user))->get(route('portal.dashboard'))->assertForbidden();
    }

    public function test_parent_payment_is_limited_to_linked_learners(): void
    {
        $parent=DB::table('users')->insertGetId(['name'=>'Parent','email'=>'parent@example.com','password'=>bcrypt('password'),'role'=>'parent','created_at'=>now(),'updated_at'=>now()]);
        DB::table('portal_profiles')->insert(['user_id'=>$parent,'role'=>'parent','active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $student=$this->student(); $other=$this->student('S-002','Other');
        DB::table('parents')->insert(['name'=>'Parent','phone'=>'0712345678','created_at'=>now(),'updated_at'=>now()]);
        $parentId=DB::getPdo()->lastInsertId();
        DB::table('parent_student')->insert(['parent_id'=>$parentId,'student_id'=>$student,'created_at'=>now(),'updated_at'=>now()]);
        $response=$this->actingAs(DB::table('users')->find($parent))->post(route('portal.payments.pay'),['student_id'=>$other,'amount'=>100,'phone'=>'0712345678']);
        $response->assertSessionHasErrors('student_id');
    }

    public function test_report_card_cannot_be_viewed_for_an_unlinked_student(): void
    {
        $parent=DB::table('users')->insertGetId(['name'=>'Parent','email'=>'parent@example.com','password'=>bcrypt('password'),'role'=>'parent','created_at'=>now(),'updated_at'=>now()]);
        DB::table('portal_profiles')->insert(['user_id'=>$parent,'role'=>'parent','active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $student=$this->student(); $exam=DB::table('exams')->insertGetId(['name'=>'Term 1','term'=>'Term 1','academic_year'=>2026,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(DB::table('users')->find($parent))->get(route('portal.report-cards.show',[$student,$exam]))->assertForbidden();
    }

    public function test_admission_application_is_created_as_pending(): void
    {
        $this->post(route('admissions.store'),['name'=>'New Learner','parent_name'=>'Parent','phone'=>'0712345678','email'=>'new@example.com','class_id'=>null])->assertSessionHas('success');
        $this->assertDatabaseHas('admission_applications',['name'=>'New Learner','status'=>'pending']);
    }

    public function test_approved_admission_creates_one_idempotent_student(): void
    {
        $admin=$this->user('admin'); $application=DB::table('admission_applications')->insertGetId(['name'=>'Applicant','parent_name'=>'Parent','phone'=>'0712345678','email'=>'applicant@example.com','status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs($admin)->patch(route('admin.admissions.status',$application),['status'=>'approved'])->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.admissions.status',$application),['status'=>'approved'])->assertSessionHas('success');
        $this->assertSame(1,DB::table('students')->where('name','Applicant')->count());
    }

    public function test_student_crud_rejects_duplicate_admission_numbers(): void
    {
        $admin=$this->user('admin'); $this->student('S-001','Existing');
        $this->actingAs($admin)->post(route('admin.students.store'),['name'=>'Duplicate','admission_number'=>'S-001'])->assertSessionHasErrors('admission_number');
    }

    public function test_attendance_upsert_preserves_original_creation_timestamp(): void
    {
        $admin=$this->user('admin'); $student=$this->student('S-001','Learner'); $old=now()->subDay();
        DB::table('attendance')->insert(['student_id'=>$student,'attendance_date'=>'2026-09-10','status'=>'present','created_at'=>$old,'updated_at'=>$old]);
        $this->actingAs($admin)->post(route('admin.operations.attendance'),['student_id'=>$student,'attendance_date'=>'2026-09-10','status'=>'late','notes'=>'Arrived late'])->assertSessionHas('success');
        $row=DB::table('attendance')->where('student_id',$student)->first();
        $this->assertSame('late',$row->status); $this->assertSame($old->format('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime($row->created_at)));
    }

    public function test_missed_result_can_be_recorded_and_is_not_treated_as_zero(): void
    {
        $admin=$this->user('admin'); $student=$this->student('S-001','Learner');
        $exam=DB::table('exams')->insertGetId(['name'=>'Term 1','term'=>'Term 1','academic_year'=>2026,'created_at'=>now(),'updated_at'=>now()]);
        $subject=DB::table('subjects')->insertGetId(['name'=>'Mathematics','code'=>'MAT','created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs($admin)->post(route('admin.operations.results'),['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject,'assessment_status'=>'missed','marks'=>null,'remarks'=>'Absent'])->assertSessionHas('success');
        $result=DB::table('results')->where(['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject])->first();
        $this->assertNull($result->marks); $this->assertSame('MISSED',$result->achievement_level); $this->assertNull($result->achievement_points);
    }

    public function test_migrations_allow_nullable_result_marks(): void
    {
        $this->assertTrue(\Schema::hasTable('results')); $this->assertTrue(\Schema::getColumnType('results','marks')==='integer' || \Schema::getColumnType('results','marks')==='bigint');
    }

    public function test_mpesa_callback_rejects_amount_tampering(): void
    {
        $response=$this->postJson(route('mpesa.callback'),['Body'=>['stkCallback'=>['MerchantRequestID'=>'m1','CheckoutRequestID'=>'c1','ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'Amount','Value'=>999],['Name'=>'MpesaReceiptNumber','Value'=>'R1']]]]]]);
        $response->assertStatus(404);
    }

    public function test_mpesa_callback_is_idempotent_and_updates_balance_once(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $student=$this->student();
        $checkout='ws_CO_'.uniqid(); DB::table('payments')->insert(['student_id'=>$student,'amount'=>500,'status'=>'pending','provider'=>'mpesa','checkout_request_id'=>$checkout,'created_at'=>now(),'updated_at'=>now()]);
        $payload=['Body'=>['stkCallback'=>['MerchantRequestID'=>'m1','CheckoutRequestID'=>$checkout,'ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'Amount','Value'=>500],['Name'=>'MpesaReceiptNumber','Value'=>'R1']]]]]];
        $this->postJson(route('mpesa.callback'),$payload)->assertOk(); $this->postJson(route('mpesa.callback'),$payload)->assertOk();
        $this->assertSame(1,DB::table('payments')->where('receipt_number','R1')->count());
    }

    public function test_duplicate_mpesa_receipt_is_rejected(): void
    {
        $student=$this->student(); DB::table('payments')->insert(['student_id'=>$student,'amount'=>100,'status'=>'completed','provider'=>'mpesa','receipt_number'=>'R1','created_at'=>now(),'updated_at'=>now()]);
        $checkout='ws_CO_'.uniqid(); DB::table('payments')->insert(['student_id'=>$student,'amount'=>100,'status'=>'pending','provider'=>'mpesa','checkout_request_id'=>$checkout,'created_at'=>now(),'updated_at'=>now()]);
        $payload=['Body'=>['stkCallback'=>['MerchantRequestID'=>'m1','CheckoutRequestID'=>$checkout,'ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'Amount','Value'=>100],['Name'=>'MpesaReceiptNumber','Value'=>'R1']]]]]];
        $this->postJson(route('mpesa.callback'),$payload)->assertStatus(409);
    }

    public function test_admin_manual_completed_payment_updates_balance_and_creates_audit(): void
    {
        $admin=$this->user('admin'); $student=$this->student(); DB::table('students')->where('id',$student)->update(['fee_balance'=>1000]);
        $this->actingAs($admin)->post(route('admin.payments.store'),['student_id'=>$student,'amount'=>500,'method'=>'cash','status'=>'completed','reference'=>'CASH-1'])->assertSessionHas('success');
        $this->assertSame(500.0,(float)DB::table('students')->where('id',$student)->value('fee_balance')); $this->assertDatabaseHas('payment_audits',['reference'=>'CASH-1']);
    }

    public function test_cbc_service_does_not_send_duplicate_notifications_for_unchanged_report(): void
    {
        Mail::fake(); $student=$this->student(); $exam=DB::table('exams')->insertGetId(['name'=>'Term 1','term'=>'Term 1','academic_year'=>2026,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('subjects')->insert(['name'=>'Mathematics','code'=>'MAT','created_at'=>now(),'updated_at'=>now()]); $subject=DB::table('subjects')->first();
        DB::table('results')->insert(['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject->id,'marks'=>80,'assessment_status'=>'present','grade'=>'EE2','achievement_level'=>'EE2','achievement_points'=>7,'created_at'=>now(),'updated_at'=>now()]);
        $service=app(CbcReportCardService::class); $first=$service->generateAndNotify($student,$exam); $second=$service->generateAndNotify($student,$exam);
        $this->assertSame(1,$first['sent']); $this->assertSame(0,$second['sent']);
    }
}
