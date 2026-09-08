<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
