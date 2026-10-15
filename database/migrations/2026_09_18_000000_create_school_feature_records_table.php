<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Apply this migration to add the database structures required by the feature.
     */
    public function up(): void
    {
        // Create the table and define its database columns and constraints.
        Schema::create('school_feature_records', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 60)->index();
            $table->string('title', 220);
            $table->string('status', 30)->default('active')->index();
            $table->string('audience', 40)->default('all')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('published')->default(true)->index();
            $table->json('payload')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
            $table->index(['feature', 'published', 'archived_at']);
            $table->index(['feature', 'audience', 'status']);
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Drop this table during rollback if it exists.
        Schema::dropIfExists('school_feature_records');
    }
};