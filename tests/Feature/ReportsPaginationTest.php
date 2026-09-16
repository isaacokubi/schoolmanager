<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportsPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Reports Admin',
            'email' => 'reports-admin@example.test',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
        ]);
    }

    public function test_student_report_is_paginated(): void
    {
        $this->admin();
        for ($i = 1; $i <= 26; $i++) {
            DB::table('students')->insert([
                'admission_number' => 'R-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Report Learner ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'fee_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin())->get(route('admin.reports', ['report' => 'students']));
        $response->assertOk()->assertSee('Showing 1–25 of 26 students')->assertSee('Report Learner 01')->assertDontSee('Report Learner 26');

        $response = $this->actingAs(User::where('email', 'reports-admin@example.test')->first())->get(route('admin.reports', ['report' => 'students', 'students_page' => 2]));
        $response->assertOk()->assertSee('Showing 26–26 of 26 students')->assertSee('Report Learner 26')->assertDontSee('Report Learner 01');
    }

    public function test_admissions_report_is_paginated(): void
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 26; $i++) {
            DB::table('admission_applications')->insert([
                'student_name' => 'Applicant ' . $i,
                'date_of_birth' => '2015-01-01',
                'requested_class' => 'Grade 5',
                'parent_name' => 'Parent ' . $i,
                'parent_phone' => '+254712345678',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)->get(route('admin.reports', ['report' => 'admissions', 'admissions_page' => 2]))
            ->assertOk()
            ->assertSee('Applicant 1')
            ->assertSee('Applicant 26');
    }
}
