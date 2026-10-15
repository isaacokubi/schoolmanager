<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private $tables = ['parents', 'school_classes', 'teachers', 'subjects', 'exams', 'announcements', 'events'];

    /**
     * Apply this migration to update the schema for the feature named in this file.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'archived_at')) {
                continue;
            }

            // Update the structure of an existing table while preserving its records.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->timestamp('archived_at')->nullable()->index();
            });
        }
    }

    /**
     * Reverse the schema changes made by this migration.
     */
    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'archived_at')) {
                continue;
            }

            // Update the structure of an existing table while preserving its records.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['archived_at']);
                $blueprint->dropColumn('archived_at');
            });
        }
    }
};
