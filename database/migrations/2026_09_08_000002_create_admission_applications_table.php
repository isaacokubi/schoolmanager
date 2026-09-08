<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->date('date_of_birth')->nullable();
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->string('requested_class')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('admission_applications'); }
};
