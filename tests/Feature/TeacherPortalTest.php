<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherPortalTest extends TestCase
{
    use RefreshDatabase;

    private function teacherUser(): array
    {
        $user = User::create([
            'name' => 'Alice Wambui',
            'email' => 'alice.wambui@example.test',
            'password' => Hash::make('Password123!'),
            'role' => 'teacher',
        ]);

        DB::table('portal_profiles')->insert([
            'user_id' => $user->id,
            'portal_type' => 'teacher',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherId = DB::table('teachers')->insertGetId([
            'name' => 'Alice Wambui',
            'email' => $user->email,
            'phone' => '0722001001',
            'employee_number' => 'T001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = DB::table('subjects')->insertGetId([
            'name' => 'English',
            'code' => 'ENG',
            'teacher_id' => $teacherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $teacherId, $subjectId];
    }

    public function test_teacher_dashboard_renders_with_teacher_tools_and_real_remarks(): void
    {
        [$user, $teacherId, $subjectId] = $this->teacherUser();
        $studentId = DB::table('students')->insertGetId([
            'admission_number' => 'ADM-001',
            'name' => 'Felix Kamau',
            'fee_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $examId = DB::table('exams')->insertGetId([
            'name' => 'Term 3 End-Term Examination',
            'term' => 'Term 3',
            'academic_year' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('results')->insert([
            'exam_id' => $examId,
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'marks' => 55,
            'grade' => 'ME2',
            'assessment_status' => 'present',
            'achievement_level' => 'ME2',
            'achievement_points' => 5,
            'remarks' => 'Meeting expectation.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Teacher workspace')
            ->assertSee('My learners')
            ->assertSee('Attendance')
            ->assertSee('Enter assessments')
            ->assertSee('Felix Kamau')
            ->assertSee('ME2')
            ->assertSee('Meeting expectation.')
            ->assertSee('Report card');
    }

    public function test_teacher_can_save_attendance_for_assigned_roster(): void
    {
        [$user, $teacherId, $subjectId] = $this->teacherUser();
        $studentId = DB::table('students')->insertGetId([
            'admission_number' => 'ADM-002',
            'name' => 'Kevin Otieno',
            'fee_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $examId = DB::table('exams')->insertGetId([
            'name' => 'Term 3 End-Term Examination',
            'term' => 'Term 3',
            'academic_year' => 2026,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('results')->insert([
            'exam_id' => $examId,
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'marks' => 90,
            'grade' => 'EE1',
            'assessment_status' => 'present',
            'achievement_level' => 'EE1',
            'achievement_points' => 8,
            'remarks' => 'Excellent progress.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $date = '2026-09-17';
        $this->actingAs($user)->post(route('portal.teacher-attendance.store'), [
            'attendance_date' => $date,
            'attendance' => [$studentId => 'late'],
            'notes' => [$studentId => 'Arrived after assembly.'],
        ])->assertRedirect(route('portal.teacher-attendance', ['date' => $date]));

        $this->assertDatabaseHas('attendance', [
            'student_id' => $studentId,
            'attendance_date' => $date,
            'status' => 'late',
            'notes' => 'Arrived after assembly.',
        ]);
    }
}
