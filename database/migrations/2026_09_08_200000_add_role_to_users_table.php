<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Apply this migration to create or update the database schema required by this feature.
     */
    public function up(): void
    {
        // Alter an existing table without dropping its existing records.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('admin')->after('password')->index();
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Alter an existing table without dropping its existing records.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
