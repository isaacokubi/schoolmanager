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
        Schema::create('portal_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('portal_type', 20)->index();
            $table->string('phone', 30)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->string('admission_number', 100)->nullable()->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Remove this table when rolling back, if it exists.
        Schema::dropIfExists('portal_profiles');
    }
};
