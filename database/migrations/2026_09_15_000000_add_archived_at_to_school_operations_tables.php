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
        foreach ([
            'parents',
            'school_classes',
            'teachers',
            'subjects',
            'attendance',
            'exams',
            'results',
            'announcements',
            'events',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'archived_at')) {
                // Update the structure of an existing table while preserving its records.
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('archived_at')->nullable()->index();
                });
            }
        }
    }

    /**
     * Reverse the schema changes made by this migration.
     */
    public function down(): void
    {
        foreach ([
            'parents',
            'school_classes',
            'teachers',
            'subjects',
            'attendance',
            'exams',
            'results',
            'announcements',
            'events',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'archived_at')) {
                // Update the structure of an existing table while preserving its records.
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('archived_at');
                });
            }
        }
    }
};
