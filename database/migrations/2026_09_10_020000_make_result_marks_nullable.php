<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('results') || !Schema::hasColumn('results', 'marks')) return;

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE results MODIFY marks DECIMAL(5,2) NULL');
            return;
        }

        if ($driver === 'sqlite') {
            $columns = DB::select("PRAGMA table_info('results')");
            $marks = collect($columns)->firstWhere('name', 'marks');
            if ($marks && (int) $marks->notnull === 1) {
                DB::statement('PRAGMA foreign_keys=OFF');
                DB::statement("CREATE TABLE results_nullable_tmp (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, exam_id INTEGER NOT NULL REFERENCES exams(id) ON DELETE CASCADE, student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE, subject_id INTEGER NOT NULL REFERENCES subjects(id) ON DELETE CASCADE, marks DECIMAL(5, 2) NULL, grade VARCHAR(5) NULL, remarks TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL, assessment_status VARCHAR(20) NOT NULL DEFAULT 'present', achievement_level VARCHAR(5) NULL, achievement_points INTEGER NULL)");
                DB::statement('INSERT INTO results_nullable_tmp (id, exam_id, student_id, subject_id, marks, grade, remarks, created_at, updated_at, assessment_status, achievement_level, achievement_points) SELECT id, exam_id, student_id, subject_id, marks, grade, remarks, created_at, updated_at, assessment_status, achievement_level, achievement_points FROM results');
                DB::statement('DROP TABLE results');
                DB::statement('ALTER TABLE results_nullable_tmp RENAME TO results');
                DB::statement('CREATE UNIQUE INDEX results_exam_id_student_id_subject_id_unique ON results (exam_id, student_id, subject_id)');
                DB::statement('CREATE INDEX results_exam_id_student_id_assessment_status_index ON results (exam_id, student_id, assessment_status)');
                DB::statement('CREATE INDEX results_exam_id_student_id_index ON results (exam_id, student_id)');
                DB::statement('PRAGMA foreign_keys=ON');
            }
        }
    }

    public function down(): void
    {
        // Nullability is intentionally retained so existing missed assessments
        // remain valid if migrations are rolled back.
    }
};
