<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('stream')->constrained('teachers')->nullOnDelete();
            $table->index(['teacher_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropIndex(['teacher_id', 'academic_year']);
            $table->dropColumn('teacher_id');
        });
    }
};
