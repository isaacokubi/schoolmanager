<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_employee_number_unique');
            $table->unique(['employee_number', 'archived_at'], 'teachers_employee_number_archived_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_code_unique');
            $table->unique(['code', 'archived_at'], 'subjects_code_archived_unique');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('results_exam_id_student_id_subject_id_unique');
            $table->unique(['exam_id', 'student_id', 'subject_id', 'archived_at'], 'results_active_record_unique');
        });
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropUnique('results_active_record_unique');
            $table->unique(['exam_id', 'student_id', 'subject_id'], 'results_exam_id_student_id_subject_id_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_code_archived_unique');
            $table->unique('code', 'subjects_code_unique');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_employee_number_archived_unique');
            $table->unique('employee_number', 'teachers_employee_number_unique');
        });
    }
};
