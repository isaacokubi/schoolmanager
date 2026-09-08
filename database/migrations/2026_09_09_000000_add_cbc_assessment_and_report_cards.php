<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->string('assessment_status', 20)->default('present')->after('marks');
            $table->string('achievement_level', 5)->nullable()->after('grade');
            $table->unsignedTinyInteger('achievement_points')->nullable()->after('achievement_level');
            $table->index(['exam_id', 'student_id', 'assessment_status']);
        });

        DB::statement("UPDATE results SET achievement_level = CASE WHEN marks >= 90 THEN 'EE1' WHEN marks >= 75 THEN 'EE2' WHEN marks >= 58 THEN 'ME1' WHEN marks >= 41 THEN 'ME2' WHEN marks >= 31 THEN 'AE1' WHEN marks >= 21 THEN 'AE2' WHEN marks >= 11 THEN 'BE1' ELSE 'BE2' END, achievement_points = CASE WHEN marks >= 90 THEN 8 WHEN marks >= 75 THEN 7 WHEN marks >= 58 THEN 6 WHEN marks >= 41 THEN 5 WHEN marks >= 31 THEN 4 WHEN marks >= 21 THEN 3 WHEN marks >= 11 THEN 2 ELSE 1 END");
        DB::table('results')->whereNotNull('marks')->update(['grade' => DB::raw('achievement_level')]);

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('content_hash', 64);
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->string('notification_status', 20)->default('pending');
            $table->text('notification_error')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'exam_id']);
            $table->index(['notification_status', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['exam_id', 'student_id', 'assessment_status']);
            $table->dropColumn(['assessment_status', 'achievement_level', 'achievement_points']);
        });
    }
};
