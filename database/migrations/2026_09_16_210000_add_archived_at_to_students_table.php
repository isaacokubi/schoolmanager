<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('students') || Schema::hasColumn('students', 'archived_at')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index()->after('updated_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'archived_at')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
