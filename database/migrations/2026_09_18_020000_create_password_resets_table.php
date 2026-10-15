<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Defines the 2026_09_18_020000_create_password_resets_table routine.
 * This file contains database migration or development-seeding logic used by the application.
 */

class CreatePasswordResetsTable extends Migration
{
    /**
     * Apply this database migration to create or update the schema.
     */
    public function up()
    {
        if (!Schema::hasTable('password_resets')) {
            // Create a database table and define its columns and constraints in the callback.
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down()
    {
        Schema::dropIfExists('password_resets');
    }
}
