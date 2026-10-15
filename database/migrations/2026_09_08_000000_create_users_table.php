<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Defines the 2026_09_08_000000_create_users_table component of the school-management application.
 * This file contains service logic or database schema changes used by the application.
 */

class CreateUsersTable extends Migration
{
    /**
     * Apply this database migration by creating or changing the schema described below.
     */
    public function up()
    {
        // Create a new database table; the callback defines its columns and constraints.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse this migration's schema changes so it can be rolled back.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
