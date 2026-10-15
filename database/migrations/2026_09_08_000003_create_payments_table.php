<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Apply this database migration by creating or changing the schema described below.
     */
    public function up(): void {
        // Create a new database table; the callback defines its columns and constraints.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parent_phone')->nullable();
            $table->string('payment_type');
            $table->decimal('amount', 12, 2);
            $table->string('account_reference')->nullable();
            $table->string('checkout_request_id')->nullable()->index();
            $table->string('merchant_request_id')->nullable();
            $table->string('mpesa_receipt')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse this migration's schema changes so it can be rolled back.
     */
    public function down(): void { Schema::dropIfExists('payments'); }
};
