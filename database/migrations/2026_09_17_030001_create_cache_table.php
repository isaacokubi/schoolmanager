<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheTable extends Migration
{
    /**
     * Apply this migration to add the database structures required by the feature.
     */
    public function up()
    {
        if (!Schema::hasTable('cache')) {
            // Create the table and define its database columns and constraints.
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
                $table->index('expiration');
            });
        }
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down()
    {
        // Drop this table during rollback if it exists.
        Schema::dropIfExists('cache');
    }
}
