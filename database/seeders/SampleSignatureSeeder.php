<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SampleSignatureSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $disk = Storage::disk('public');

        $disk->put('signatures/teachers/alice-wambui-sample.svg', <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="120" viewBox="0 0 420 120">
  <path d="M18 82 C30 58,38 35,52 31 C65 27,60 72,72 80 C84 88,91 48,104 44 C119 39,116 76,128 81 C141 86,151 55,164 48 C178 40,181 70,192 76 C204 82,214 55,225 50 C239 44,241 70,254 75 C267 80,281 55,294 49 C309 42,307 71,319 77 C333 84,343 61,357 54 C370 48,380 59,397 54" fill="none" stroke="#17324d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M24 94 C105 101,198 101,300 94 C337 91,366 89,398 82" fill="none" stroke="#17324d" stroke-width="3" stroke-linecap="round"/>
</svg>
SVG
        );

        $disk->put('signatures/institution/school-administrator-sample.svg', <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="120" viewBox="0 0 420 120">
  <path d="M18 79 C31 55,39 35,53 34 C67 33,61 75,75 81 C89 87,94 51,108 47 C124 43,120 75,134 80 C148 85,157 54,171 49 C186 44,185 71,199 77 C213 83,222 54,236 49 C251 44,253 72,267 77 C281 82,291 55,305 50 C321 44,318 72,332 77 C346 82,358 59,372 55 C385 51,392 58,402 53" fill="none" stroke="#17324d" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M25 94 C112 101,204 99,302 94 C341 92,370 87,399 81" fill="none" stroke="#17324d" stroke-width="3" stroke-linecap="round"/>
</svg>
SVG
        );

        $teacherId = DB::table('teachers')->where('employee_number', 'T001')->value('id');
        if ($teacherId) {
            DB::table('teachers')->where('id', $teacherId)->update([
                'signature_path' => 'signatures/teachers/alice-wambui-sample.svg',
                'updated_at' => $now,
            ]);
        }

        $headId = DB::table('users')->where('email', 'admin@schoolmanager.test')->value('id');
        if ($headId) {
            DB::table('users')->where('id', $headId)->update([
                'signature_path' => 'signatures/institution/school-administrator-sample.svg',
                'updated_at' => $now,
            ]);
            DB::table('settings')->updateOrInsert(['key' => 'head_of_institution_user_id'], [
                'value' => (string) $headId,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        if ($teacherId) {
            DB::table('school_classes')
                ->where('name', 'Grade 1')
                ->where('academic_year', 2026)
                ->update(['class_teacher_id' => $teacherId, 'updated_at' => $now]);
        }
    }
}
