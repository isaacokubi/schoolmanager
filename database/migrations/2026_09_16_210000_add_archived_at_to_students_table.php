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
        if (!Schema::hasTable('students') || Schema::hasColumn('students', 'archived_at')) {
            return;
        }

        // Alter the existing table while preserving its current data.
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index()->after('updated_at');
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'archived_at')) {
            return;
        }

        // Alter the existing table while preserving its current data.
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
