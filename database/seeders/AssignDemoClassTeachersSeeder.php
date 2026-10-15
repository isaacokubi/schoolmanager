<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
/**
 * Defines the assign demo class teachers seeder routine.
 * This file contains database migration or development-seeding logic used by the application.
 */

class AssignDemoClassTeachersSeeder extends Seeder
{
    /**
     * Execute the seeder's data-preparation task and create the records needed by the configured demo or initial environment.
     */
    public function run(): void
    {
        $aliceId = DB::table('teachers')->where('email', 'alice.wambui@example.test')->value('id');
        if (!$aliceId) {
            return;
        }

        // Start a query against the named table; subsequent calls specify the filter or operation.
        DB::table('school_classes')
            ->where('academic_year', 2026)
            ->update(['teacher_id' => null, 'updated_at' => now()]);

        $gradeOneId = DB::table('school_classes')
            ->where('name', 'Grade 1')
            ->where('academic_year', 2026)
            ->value('id');

        if ($gradeOneId) {
            // Start a query against the named table; subsequent calls specify the filter or operation.
            DB::table('school_classes')
                ->where('id', $gradeOneId)
                ->update(['teacher_id' => $aliceId, 'updated_at' => now()]);
        }
    }
}
