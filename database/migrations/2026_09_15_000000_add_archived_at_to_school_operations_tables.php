<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('archived_at')->nullable()->index();
                });
            }
        }
    }

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
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('archived_at');
                });
            }
        }
    }
};
