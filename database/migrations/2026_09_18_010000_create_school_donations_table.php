<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_donations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 8)->default('KES');
            $table->string('purpose', 120)->default('General support');
            $table->text('message')->nullable();
            $table->string('status', 30)->default('pledged')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('school_donations'); }
};