<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('employee_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
        });

        Schema::table('report_cards', function (Blueprint $table) {
            $table->string('parent_signature_path')->nullable()->after('notification_error');
            $table->foreignId('parent_signed_by')->nullable()->after('parent_signature_path')->constrained('users')->nullOnDelete();
            $table->timestamp('parent_signed_at')->nullable()->after('parent_signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('report_cards', function (Blueprint $table) {
            $table->dropForeign(['parent_signed_by']);
            $table->dropColumn(['parent_signature_path', 'parent_signed_by', 'parent_signed_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
