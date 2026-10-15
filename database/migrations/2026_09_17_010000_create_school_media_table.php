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
        Schema::create('school_media', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160)->nullable();
            $table->text('caption')->nullable();
            $table->enum('type', ['image', 'video']);
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index(['published', 'sort_order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Drop this table during rollback if it exists.
        Schema::dropIfExists('school_media');
    }
};
