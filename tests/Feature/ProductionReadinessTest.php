<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CbcReportCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email = null): User
    {
        $user = User::create(['name'=>ucfirst($role).' User','email'=>$email ?: $role.'@example.test','password'=>Hash::make('Password123!'),'role'=>$role]);
        if (in_array($role, ['pupil','parent','sponsor','teacher'], true)) {
            DB::table('portal_profiles')->insert(['user_id'=>$user->id,'portal_type'=>$role,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        return $user;
    }

    private function student(string $admission, string $name, int $parentId = null, float $balance = 10000): int
    {
        return DB::table('students')->insertGetId(['admission_number'=>$admission,'name'=>$name,'parent_id'=>$parentId,'parent_name'=>'Parent One','parent_phone'=>'+254712345678','fee_balance'=>$balance,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function test_public_pages_are_reachable(): void
    {
        foreach (['/','/about','/academics','/admissions','/contact','/login','/register'] as $path) $this->get($path)->assertSuccessful();
    }

    public function test_authentication_redirects_users_to_their_role_area(): void
    {
        $admin=$this->user('admin');
        $this->post('/login',['email'=>$admin->email,'password'=>'Password123!'])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));
        $parent=$this->user('parent','parent@example.test');
        DB::table('portal_profiles')->where('user_id',$parent->id)->update(['admission_number'=>'P-001']);
        $this->post('/login',['email'=>$parent->email,'password'=>'Password123!'])->assertRedirect(route('portal.dashboard'));
    }

    public function test_roles_cannot_cross_admin_and_portal_boundaries(): void
    {
        $admin=$this->user('admin');
        $this->actingAs($admin)->get(route('portal.dashboard'))->assertForbidden();
        $parent=$this->user('parent');
        $this->actingAs($parent)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_inactive_portal_profile_is_denied_even_when_role_is_valid(): void
    {
        $parent=$this->user('parent');
        DB::table('portal_profiles')->where('user_id',$parent->id)->update(['active'=>false]);
        $this->actingAs($parent)->get(route('portal.dashboard'))->assertForbidden();
    }

    public function test_parent_payment_is_limited_to_linked_learners(): void
    {
        $parent=DB::table('parents')->insertGetId(['name'=>'Parent One','phone'=>'0712345678','email'=>'parent@example.test','created_at'=>now(),'updated_at'=>now()]);
        $parentUser=$this->user('parent','parent@example.test');
        $ownStudent=$this->student('P-001','Own Learner',$parent,5000);
        $otherStudent=$this->student('P-002','Other Learner',null,5000);
        DB::table('portal_profiles')->where('user_id',$parentUser->id)->update(['admission_number'=>'P-001']);
        $this->actingAs($parentUser)->post(route('portal.payments.pay'),['student_id'=>$otherStudent,'phone'=>'0712345678','amount'=>1000])->assertForbidden();
        $this->assertDatabaseMissing('payments',['student_id'=>$otherStudent]);
        $this->assertDatabaseMissing('payments',['student_id'=>$ownStudent]);
    }

    public function test_report_card_cannot_be_viewed_for_an_unlinked_student(): void
    {
        $parent=DB::table('parents')->insertGetId(['name'=>'Other Parent','phone'=>'0712345679','email'=>'other-parent@example.test','created_at'=>now(),'updated_at'=>now()]);
        $user=$this->user('parent','parent@example.test');
        DB::table('portal_profiles')->where('user_id',$user->id)->update(['admission_number'=>'P-001']);
        $student=$this->student('P-002','Other Learner',$parent,1000);
        $exam=DB::table('exams')->insertGetId(['name'=>'Term 1','term'=>'Term 1','academic_year'=>2026,'created_at'=>now(),'updated_at'=>now()]);
        $subject=DB::table('subjects')->insertGetId(['name'=>'Mathematics','code'=>'MAT','created_at'=>now(),'updated_at'=>now()]);
        DB::table('results')->insert(['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject,'marks'=>80,'assessment_status'=>'present','grade'=>'EE2','achievement_level'=>'EE2','achievement_points'=>7,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs($user)->get(route('portal.report-cards.show',[$student,$exam]))->assertForbidden();
    }

    public function test_admission_application_is_created_as_pending(): void
    {
        $this->post(route('admissions.store'),['student_name'=>'New Learner','date_of_birth'=>'2015-05-10','requested_class'=>'Grade 5','parent_name'=>'Parent One','parent_phone'=>'+254712345678','parent_email'=>'parent@example.test'])->assertSessionHas('success');
        $this->assertDatabaseHas('admission_applications',['student_name'=>'New Learner','status'=>'pending']);
    }

    public function test_approved_admission_creates_one_idempotent_student(): void
    {
        $application=DB::table('admission_applications')->insertGetId(['student_name'=>'Approved Learner','date_of_birth'=>'2015-05-10','requested_class'=>'Grade 5','parent_name'=>'Parent One','parent_phone'=>'+254712345678','parent_email'=>'parent@example.test','status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $admin=$this->user('admin');
        $this->actingAs($admin)->patch(route('admin.admissions.status',$application),['status'=>'approved'])->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.admissions.status',$application),['status'=>'approved'])->assertSessionHas('success');
        $admission=strtoupper(substr('ApprovedLearner',0,4)).'-'.date('Y').'-'.str_pad((string)$application,4,'0',STR_PAD_LEFT);
        $this->assertSame(1,DB::table('students')->where('admission_number',$admission)->count());
    }

    public function test_student_crud_rejects_duplicate_admission_numbers(): void
    {
        $admin=$this->user('admin');
        $this->student('S-001','Existing Learner');
        $this->actingAs($admin)->post(route('admin.students.store'),['admission_number'=>'S-001','name'=>'Duplicate Learner','class_name'=>'Grade 5','fee_balance'=>0])->assertSessionHasErrors('admission_number');
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
        $this->actingAs($admin)->post(route('admin.operations.results'),['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject,'assessment_status'=>'missed','remarks'=>'Absent'])->assertSessionHas('success');
        $result=DB::table('results')->where(['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject])->first();
        $this->assertNull($result->marks); $this->assertSame('MISSED',$result->achievement_level); $this->assertNull($result->achievement_points);
    }

    public function test_migrations_allow_nullable_result_marks(): void
    {
        $this->assertTrue(Schema::hasTable('results'));
        if (Schema::getConnection()->getDriverName()==='sqlite') {
            $marks=collect(DB::select("PRAGMA table_info('results')"))->firstWhere('name','marks');
            $this->assertNotNull($marks); $this->assertSame(0,(int)$marks->notnull);
        }
    }

    public function test_mpesa_callback_rejects_amount_tampering(): void
    {
        $student=$this->student('S-001','Learner',null,5000);
        $payment=DB::table('payments')->insertGetId(['student_id'=>$student,'parent_phone'=>'254712345678','payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>2000,'account_reference'=>'FEE-1','checkout_request_id'=>'ws_CO_123','status'=>'pending','verification_status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $this->postJson('/api/mpesa/callback',['Body'=>['stkCallback'=>['CheckoutRequestID'=>'ws_CO_123','ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'MpesaReceiptNumber','Value'=>'ABC123'],['Name'=>'Amount','Value'=>1500],['Name'=>'PhoneNumber','Value'=>254712345678]]]]]])->assertOk()->assertJson(['ResultCode'=>0]);
        $this->assertDatabaseHas('payments',['id'=>$payment,'status'=>'failed','verification_status'=>'rejected']);
        $this->assertSame(5000.0,(float)DB::table('students')->where('id',$student)->value('fee_balance'));
    }

    public function test_mpesa_callback_is_idempotent_and_updates_balance_once(): void
    {
        $student=$this->student('S-001','Learner',null,5000);
        $payment=DB::table('payments')->insertGetId(['student_id'=>$student,'parent_phone'=>'254712345678','payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>2000,'account_reference'=>'FEE-1','checkout_request_id'=>'ws_CO_456','status'=>'pending','verification_status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $callback=['Body'=>['stkCallback'=>['CheckoutRequestID'=>'ws_CO_456','ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'MpesaReceiptNumber','Value'=>'ABC456'],['Name'=>'Amount','Value'=>2000],['Name'=>'PhoneNumber','Value'=>254712345678]]]]]];
        $this->withoutMiddleware('throttle:api');
        $first=$this->postJson('/api/mpesa/callback',$callback)->assertOk();
        $second=$this->postJson('/api/mpesa/callback',$callback)->assertOk();
        $this->assertSame(0,$first->json('ResultCode')); $this->assertSame(0,$second->json('ResultCode'));
        $this->assertDatabaseHas('payments',['id'=>$payment,'status'=>'completed','verification_status'=>'verified','mpesa_receipt'=>'ABC456']);
        $this->assertSame(3000.0,(float)DB::table('students')->where('id',$student)->value('fee_balance'));
        $this->assertSame(1,DB::table('payment_audits')->where('payment_id',$payment)->where('event','payment_verified')->count());
    }

    public function test_duplicate_mpesa_receipt_is_rejected(): void
    {
        $student=$this->student('S-001','Learner',null,5000);
        DB::table('payments')->insert(['student_id'=>$student,'payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>1000,'mpesa_receipt'=>'DUP123','status'=>'completed','verification_status'=>'verified','created_at'=>now(),'updated_at'=>now()]);
        $payment=DB::table('payments')->insertGetId(['student_id'=>$student,'parent_phone'=>'254712345678','payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>1000,'account_reference'=>'FEE-2','checkout_request_id'=>'ws_CO_789','status'=>'pending','verification_status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
        $this->postJson('/api/mpesa/callback',['Body'=>['stkCallback'=>['CheckoutRequestID'=>'ws_CO_789','ResultCode'=>0,'CallbackMetadata'=>['Item'=>[['Name'=>'MpesaReceiptNumber','Value'=>'DUP123'],['Name'=>'Amount','Value'=>1000]]]]]])->assertOk();
        $this->assertDatabaseHas('payments',['id'=>$payment,'status'=>'failed','verification_status'=>'rejected']);
    }

    public function test_admin_manual_completed_payment_updates_balance_and_creates_audit(): void
    {
        $admin=$this->user('admin'); $student=$this->student('S-001','Learner',null,5000);
        $this->actingAs($admin)->post(route('admin.payments.store'),['student_id'=>$student,'parent_phone'=>'+254712345678','payment_type'=>'school_fees','amount'=>1000,'account_reference'=>'MANUAL-001','mpesa_receipt'=>'MAN123','status'=>'completed'])->assertRedirect(route('admin.payments.index'));
        $payment=DB::table('payments')->where('mpesa_receipt','MAN123')->first();
        $this->assertSame(4000.0,(float)DB::table('students')->where('id',$student)->value('fee_balance'));
        $this->assertDatabaseHas('payment_audits',['payment_id'=>$payment->id,'event'=>'manual_payment_verified']);
    }

    public function test_cbc_service_does_not_send_duplicate_notifications_for_unchanged_report(): void
    {
        $student=$this->student('S-001','Learner');
        $exam=DB::table('exams')->insertGetId(['name'=>'Term 1','term'=>'Term 1','academic_year'=>2026,'created_at'=>now(),'updated_at'=>now()]);
        $subject=DB::table('subjects')->insertGetId(['name'=>'Mathematics','code'=>'MAT','created_at'=>now(),'updated_at'=>now()]);
        DB::table('results')->insert(['exam_id'=>$exam,'student_id'=>$student,'subject_id'=>$subject,'marks'=>80,'assessment_status'=>'present','grade'=>'EE2','achievement_level'=>'EE2','achievement_points'=>7,'created_at'=>now(),'updated_at'=>now()]);
        $parentUser=$this->user('parent'); DB::table('portal_profiles')->where('user_id',$parentUser->id)->update(['admission_number'=>'S-001']);
        $service=app(CbcReportCardService::class); $first=$service->generateAndNotify($student,$exam); $second=$service->generateAndNotify($student,$exam);
        $this->assertTrue($first['complete']); $this->assertTrue($second['complete']);
        $this->assertSame(1,$first['sent']); $this->assertSame(0,$second['sent']);
        $this->assertDatabaseHas('report_cards',['student_id'=>$student,'exam_id'=>$exam,'notification_status'=>'sent']);
    }
}
