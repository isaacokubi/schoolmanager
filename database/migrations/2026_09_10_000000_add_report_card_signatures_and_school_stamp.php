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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('employee_number');
        });

        // Update the structure of an existing table while preserving its records.
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
        });

        // Update the structure of an existing table while preserving its records.
        Schema::table('report_cards', function (Blueprint $table) {
            $table->string('parent_signature_path')->nullable()->after('notification_error');
            $table->foreignId('parent_signed_by')->nullable()->after('parent_signature_path')->constrained('users')->nullOnDelete();
            $table->timestamp('parent_signed_at')->nullable()->after('parent_signed_by');
        });
    }

    /**
     * Reverse the schema changes made by this migration.
     */
    public function down(): void
    {
        // Update the structure of an existing table while preserving its records.
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['parent_signed_by']);
            $table->dropColumn(['parent_signature_path', 'parent_signed_by', 'parent_signed_at']);
        });

        // Update the structure of an existing table while preserving its records.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });

        // Update the structure of an existing table while preserving its records.
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
