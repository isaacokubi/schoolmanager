<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Apply this migration to update the schema for the feature named in this file.
     */
    public function up(): void
    {
        // Update the structure of an existing table while preserving its records.
        Schema::table('school_classes', function (Blueprint $table) {
            $table->foreignId('class_teacher_id')->nullable()->after('academic_year')->constrained('teachers')->nullOnDelete();
        });
    }

    /**
     * Reverse the schema changes made by this migration.
     */
    public function down(): void
    {
        // Update the structure of an existing table while preserving its records.
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_teacher_id');
        });
    }
};
