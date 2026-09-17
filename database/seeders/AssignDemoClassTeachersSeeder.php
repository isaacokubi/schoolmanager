<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignDemoClassTeachersSeeder extends Seeder
{
    public function run(): void
    {
        $aliceId = DB::table('teachers')->where('email', 'alice.wambui@example.test')->value('id');
        if (!$aliceId) {
            return;
        }

        DB::table('school_classes')
            ->where('academic_year', 2026)
            ->update(['teacher_id' => null, 'updated_at' => now()]);

        $gradeOneId = DB::table('school_classes')
            ->where('name', 'Grade 1')
            ->where('academic_year', 2026)
            ->value('id');

        if ($gradeOneId) {
            DB::table('school_classes')
                ->where('id', $gradeOneId)
                ->update(['teacher_id' => $aliceId, 'updated_at' => now()]);
        }
    }
}
