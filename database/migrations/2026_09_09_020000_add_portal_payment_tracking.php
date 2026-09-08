<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            $table->string('payer_role', 30)->nullable()->after('user_id')->index();
            $table->string('payer_name')->nullable()->after('payer_role');
            $table->string('channel', 30)->default('mpesa_stk')->after('payment_type')->index();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'status']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'payer_role', 'payer_name', 'channel']);
        });
    }
};
