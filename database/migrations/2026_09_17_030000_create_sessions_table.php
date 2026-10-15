<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Apply this migration to add the database structures required by the feature.
     */
    public function up()
    {
        if (!Schema::hasTable('sessions')) {
            // Create the table and define its database columns and constraints.
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down()
    {
        // Drop this table during rollback if it exists.
        Schema::dropIfExists('sessions');
    }
}
