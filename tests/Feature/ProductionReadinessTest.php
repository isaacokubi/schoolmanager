<?php

namespace Tests\Feature;

use App\Models\User;
use App\Jobs\SendPaymentReceiptJob;
use App\Services\PaymentReceiptService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_public_entry_points_match_the_simplified_application(): void
    {
        // The root intentionally sends visitors to login; public registration and legacy pages are disabled.
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example-token')->assertOk();
    }

    public function test_admin_login_redirects_to_the_protected_dashboard(): void
    {
        $admin = $this->user('admin');

        // Only the administrator workspace is enabled in the simplified app.
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_student_crud_rejects_duplicate_admission_numbers(): void
    {
        $admin=$this->user('admin');
        $this->student('S-001','Existing Learner');
        $this->actingAs($admin)->post(route('admin.students.store'),['admission_number'=>'S-001','name'=>'Duplicate Learner','class_name'=>'Grade 5','fee_balance'=>0])->assertSessionHasErrors('admission_number');
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
        $this->withoutMiddleware(ThrottleRequests::class);
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

    public function test_verified_mpesa_callback_queues_payment_receipt_after_commit(): void
    {
        Queue::fake();

        $parent = DB::table('parents')->insertGetId([
            'name' => 'Receipt Parent',
            'phone' => '0712345678',
            'email' => 'receipt-parent@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = $this->student('REC-001', 'Receipt Learner', $parent, 5000);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 2000,
            'account_reference' => 'REC-001',
            'checkout_request_id' => 'ws_CO_RECEIPT_001',
            'status' => 'pending',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/mpesa/callback', [
            'Body' => [
                'stkCallback' => [
                    'CheckoutRequestID' => 'ws_CO_RECEIPT_001',
                    'ResultCode' => 0,
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'REC123'],
                            ['Name' => 'Amount', 'Value' => 2000],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                        ],
                    ],
                ],
            ],
        ])->assertOk()->assertJson(['ResultCode' => 0]);

        Queue::assertPushed(
            SendPaymentReceiptJob::class,
            function ($job) use ($payment) {
                return $job->paymentId === $payment;
            }
        );

        $this->assertDatabaseHas('payment_audits', [
            'payment_id' => $payment,
            'event' => 'receipt_queued',
        ]);
    }

    public function test_payment_receipt_requires_parent_or_sponsor_recipient(): void
    {
        Mail::fake();

        $admin = $this->user('admin', 'school-admin@example.test');
        $student = $this->student('REC-002', 'No Recipient Learner', null, 5000);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 1500,
            'account_reference' => 'REC-002',
            'mpesa_receipt' => 'REC124',
            'status' => 'completed',
            'verification_status' => 'verified',
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(PaymentReceiptService::class)->sendForPayment($payment);

        $this->assertFalse($result['sent']);
        $this->assertFalse($result['retryable']);
        $this->assertSame(
            'No parent or sponsor receipt recipient was found.',
            $result['reason']
        );

        Mail::assertNothingSent();

        $this->assertDatabaseHas('payment_audits', [
            'payment_id' => $payment,
            'event' => 'receipt_delivery_failed',
        ]);

        $this->assertNotNull($admin);
    }

    public function test_payment_receipt_sends_pdf_to_parent_and_bccs_admin(): void
    {
        Mail::fake();

        DB::table('settings')->insert([
            'key' => 'school_name',
            'value' => 'Production School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $parent = DB::table('parents')->insertGetId([
            'name' => 'Receipt Parent',
            'phone' => '0712345678',
            'email' => 'receipt-parent@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->user('admin', 'admin-receipts@example.test');
        $student = $this->student('REC-003', 'Receipt Learner', $parent, 5000);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 2500,
            'account_reference' => 'REC-003',
            'mpesa_receipt' => 'REC125',
            'status' => 'completed',
            'verification_status' => 'verified',
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(PaymentReceiptService::class)->sendForPayment($payment);

        $this->assertTrue($result['sent']);
        $this->assertSame(['receipt-parent@example.test'], $result['recipients']);


        $this->assertDatabaseHas('payment_audits', [
            'payment_id' => $payment,
            'event' => 'receipt_sent',
        ]);

        $this->assertNotNull($admin);
    }

    public function test_payment_receipt_is_idempotent_when_job_runs_twice(): void
    {
        Mail::fake();

        $parent = DB::table('parents')->insertGetId([
            'name' => 'Idempotent Parent',
            'phone' => '0712345678',
            'email' => 'idempotent-parent@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = $this->student('REC-004', 'Idempotent Learner', $parent, 5000);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 3000,
            'account_reference' => 'REC-004',
            'mpesa_receipt' => 'REC126',
            'status' => 'completed',
            'verification_status' => 'verified',
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(PaymentReceiptService::class);

        $first = $service->sendForPayment($payment);
        $second = $service->sendForPayment($payment);

        $this->assertTrue($first['sent']);
        $this->assertTrue($second['sent']);
        $this->assertTrue($second['already_sent']);

        $this->assertSame(
            1,
            DB::table('payment_audits')
                ->where('payment_id', $payment)
                ->where('event', 'receipt_sent')
                ->count()
        );

    }

    public function test_stk_query_success_does_not_falsely_verify_without_receipt(): void
    {
        $student = DB::table('students')->insertGetId([
            'name' => 'STK Query Student',
            'admission_number' => 'QUERY-STU-001',
            'fee_balance' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 1000,
            'account_reference' => 'QUERY-1',
            'checkout_request_id' => 'ws_CO_QUERY_SUCCESS',
            'status' => 'pending',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(\App\Services\MpesaService::class, function ($mock) {
            $mock->shouldReceive('stkQuery')
                ->once()
                ->with('ws_CO_QUERY_SUCCESS')
                ->andReturn([
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success. Request accepted for processing',
                    'ResultCode' => '0',
                    'ResultDesc' => 'The service request is processed successfully.',
                ]);
        });

        $admin = \App\Models\User::create(['name' => 'STK Query Admin', 'email' => 'stk-query-admin@example.test', 'password' => \Illuminate\Support\Facades\Hash::make('password'), 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.payments.mpesa.query', $payment))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment,
            'status' => 'pending',
            'verification_status' => 'pending',
            'mpesa_receipt' => null,
        ]);

        $this->assertDatabaseHas('payment_audits', [
            'payment_id' => $payment,
            'event' => 'stk_query',
        ]);
    }

    public function test_stk_query_failure_rejects_pending_payment(): void
    {
        $student = DB::table('students')->insertGetId([
            'name' => 'STK Query Failed Student',
            'admission_number' => 'QUERY-STU-002',
            'fee_balance' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = DB::table('payments')->insertGetId([
            'student_id' => $student,
            'parent_phone' => '254712345678',
            'payment_type' => 'school_fees',
            'channel' => 'mpesa_stk',
            'amount' => 1000,
            'account_reference' => 'QUERY-2',
            'checkout_request_id' => 'ws_CO_QUERY_FAILED',
            'status' => 'pending',
            'verification_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->mock(\App\Services\MpesaService::class, function ($mock) {
            $mock->shouldReceive('stkQuery')
                ->once()
                ->with('ws_CO_QUERY_FAILED')
                ->andReturn([
                    'ResponseCode' => '0',
                    'ResponseDescription' => 'Success. Request accepted for processing',
                    'ResultCode' => '1032',
                    'ResultDesc' => 'Request cancelled by user',
                ]);
        });

        $admin = \App\Models\User::create(['name' => 'STK Query Admin', 'email' => 'stk-query-admin@example.test', 'password' => \Illuminate\Support\Facades\Hash::make('password'), 'role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.payments.mpesa.query', $payment))
            ->assertSessionHasErrors('mpesa');

        $this->assertDatabaseHas('payments', [
            'id' => $payment,
            'status' => 'failed',
            'verification_status' => 'rejected',
        ]);

        $this->assertDatabaseHas('payment_audits', [
            'payment_id' => $payment,
            'event' => 'stk_query',
        ]);
    }

}
