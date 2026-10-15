<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheLocksTable extends Migration
{
    /**
     * Apply this migration to add the database structures required by the feature.
     */
    public function up()
    {
        if (!Schema::hasTable('cache_locks')) {
            // Create the table and define its database columns and constraints.
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down()
    {
        // Drop this table during rollback if it exists.
        Schema::dropIfExists('cache_locks');
    }
}
