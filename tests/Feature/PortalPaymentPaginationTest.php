<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalPaymentPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_payment_history_is_paginated(): void
    {
        $parent = DB::table('parents')->insertGetId(['name'=>'Portal Parent','phone'=>'+254712345678','email'=>'portal-parent@example.test','created_at'=>now(),'updated_at'=>now()]);
        $user = User::create(['name'=>'Portal Parent','email'=>'portal-parent@example.test','password'=>Hash::make('Password123!'),'role'=>'parent']);
        DB::table('portal_profiles')->insert(['user_id'=>$user->id,'portal_type'=>'parent','active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $student = DB::table('students')->insertGetId(['admission_number'=>'P-001','name'=>'Portal Learner','parent_id'=>$parent,'parent_name'=>'Portal Parent','parent_phone'=>'+254712345678','fee_balance'=>50000,'created_at'=>now(),'updated_at'=>now()]);

        for ($i=1; $i<=41; $i++) {
            DB::table('payments')->insert(['student_id'=>$student,'user_id'=>$user->id,'payer_role'=>'parent','payer_name'=>'Portal Parent','parent_phone'=>'254712345678','payment_type'=>'school_fees','channel'=>'mpesa_stk','amount'=>1000,'account_reference'=>'PORTAL-'.$i,'status'=>'completed','verification_status'=>'verified','mpesa_receipt'=>'REC-'.$i,'created_at'=>now()->subMinutes($i),'updated_at'=>now()->subMinutes($i)]);
        }

        $this->actingAs($user)->get(route('portal.payments',['payments_page'=>2]))
            ->assertOk()
            ->assertSeeText('Showing 21')
            ->assertSeeText('40 of 41 payments')
            ->assertSeeHtml('>PORTAL-21</strong>')
            ->assertSeeHtml('>PORTAL-2</strong>')
            ->assertDontSeeHtml('>PORTAL-41</strong>')
            ->assertDontSeeHtml('>PORTAL-1</strong>');
    }
}
