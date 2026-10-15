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
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Remove this table when rolling back, if it exists.
        Schema::dropIfExists('settings');
    }
};
