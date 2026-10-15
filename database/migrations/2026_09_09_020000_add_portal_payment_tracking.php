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
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            $table->string('payer_role', 30)->nullable()->after('user_id')->index();
            $table->string('payer_name')->nullable()->after('payer_role');
            $table->string('channel', 30)->default('mpesa_stk')->after('payment_type')->index();
            $table->index(['student_id', 'status']);
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Alter an existing table without dropping its existing records.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'status']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payer_role', 'payer_name', 'channel']);
        });
    }
};
