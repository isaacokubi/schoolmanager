<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'payer_role')) {
                $table->string('payer_role', 30)->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('payments', 'payer_name')) {
                $table->string('payer_name', 150)->nullable()->after('payer_role');
            }
            if (!Schema::hasColumn('payments', 'channel')) {
                $table->string('channel', 40)->nullable()->after('payment_type')->index();
            }
            if (!Schema::hasColumn('payments', 'verification_status')) {
                $table->string('verification_status', 30)->default('pending')->after('status')->index();
            }
            if (!Schema::hasColumn('payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('verification_status');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['student_id', 'status', 'created_at']);
            $table->index(['checkout_request_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'status', 'created_at']);
            $table->dropIndex(['checkout_request_id', 'status']);
            if (Schema::hasColumn('payments', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('payments', 'verification_status')) {
                $table->dropIndex(['verification_status']);
                $table->dropColumn('verification_status');
            }
            if (Schema::hasColumn('payments', 'channel')) {
                $table->dropIndex(['channel']);
                $table->dropColumn('channel');
            }
            if (Schema::hasColumn('payments', 'payer_name')) {
                $table->dropColumn('payer_name');
            }
            if (Schema::hasColumn('payments', 'payer_role')) {
                $table->dropIndex(['payer_role']);
                $table->dropColumn('payer_role');
            }
            if (Schema::hasColumn('payments', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
