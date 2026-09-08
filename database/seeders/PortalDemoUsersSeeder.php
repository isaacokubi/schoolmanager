<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PortalDemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $password = Hash::make('Admin@12345');

        $students = DB::table('students')->orderBy('admission_number')->get();
        foreach ($students as $student) {
            $email = strtolower(str_replace([' ', '.'], '.', $student->name)) . '@pupil.schoolmanager.test';
            $userId = DB::table('users')->where('email', $email)->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $student->name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'pupil',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update(['password' => $password, 'role' => 'pupil', 'updated_at' => $now]);
            }
            DB::table('portal_profiles')->updateOrInsert(
                ['user_id' => $userId],
                ['portal_type' => 'pupil', 'phone' => $student->parent_phone, 'relationship' => null, 'admission_number' => $student->admission_number, 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $parents = DB::table('parents')->orderBy('id')->get();
        foreach ($parents as $parent) {
            $student = DB::table('students')->where('parent_id', $parent->id)->orderBy('admission_number')->first();
            if (!$student) {
                continue;
            }
            $email = $parent->email ?: ('parent' . $parent->id . '@schoolmanager.test');
            $userId = DB::table('users')->where('email', $email)->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $parent->name,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'parent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update(['name' => $parent->name, 'password' => $password, 'role' => 'parent', 'updated_at' => $now]);
            }
            DB::table('portal_profiles')->updateOrInsert(
                ['user_id' => $userId],
                ['portal_type' => 'parent', 'phone' => $parent->phone, 'relationship' => $parent->relationship, 'admission_number' => $student->admission_number, 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $sponsorRows = [
            ['name' => 'Equity Education Sponsor', 'email' => 'equity.sponsor@schoolmanager.test', 'phone' => '0744001001', 'admission' => 'MA2026-001'],
            ['name' => 'Mwangaza Foundation Sponsor', 'email' => 'mwangaza.sponsor@schoolmanager.test', 'phone' => '0744001002', 'admission' => 'MA2026-005'],
            ['name' => 'Future Leaders Sponsor', 'email' => 'future.leaders@schoolmanager.test', 'phone' => '0744001003', 'admission' => 'MA2026-009'],
        ];
        foreach ($sponsorRows as $sponsor) {
            $userId = DB::table('users')->where('email', $sponsor['email'])->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $sponsor['name'],
                    'email' => $sponsor['email'],
                    'password' => $password,
                    'role' => 'sponsor',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update(['name' => $sponsor['name'], 'password' => $password, 'role' => 'sponsor', 'updated_at' => $now]);
            }
            DB::table('portal_profiles')->updateOrInsert(
                ['user_id' => $userId],
                ['portal_type' => 'sponsor', 'phone' => $sponsor['phone'], 'relationship' => 'Sponsor', 'admission_number' => $sponsor['admission'], 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $teachers = DB::table('teachers')->orderBy('employee_number')->get();
        foreach ($teachers as $teacher) {
            if (!$teacher->email) {
                continue;
            }
            $userId = DB::table('users')->where('email', $teacher->email)->value('id');
            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'password' => $password,
                    'role' => 'teacher',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update(['name' => $teacher->name, 'password' => $password, 'role' => 'teacher', 'updated_at' => $now]);
            }
            DB::table('portal_profiles')->updateOrInsert(
                ['user_id' => $userId],
                ['portal_type' => 'teacher', 'phone' => $teacher->phone, 'relationship' => null, 'admission_number' => null, 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
