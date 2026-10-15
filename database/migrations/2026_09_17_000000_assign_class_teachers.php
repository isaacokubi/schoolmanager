<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Apply this migration to add the database structures required by the feature.
     */
    public function up(): void
    {
        // Alter the existing table while preserving its current data.
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->after('stream')->constrained('teachers')->nullOnDelete();
            $table->index(['teacher_id', 'academic_year']);
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Alter the existing table while preserving its current data.
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropIndex(['teacher_id', 'academic_year']);
            $table->dropColumn('teacher_id');
        });
    }
};
