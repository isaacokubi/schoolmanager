<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
/**
 * Defines the 2026_09_08_170000_add_indexes_to_school_tables component of the school-management application.
 * This file contains service logic or database schema changes used by the application.
 */

class AddIndexesToSchoolTables extends Migration
{
    /**
     * Apply this database migration by creating or changing the schema described below.
     */
    public function up()
    {
        // Modify an existing database table while preserving its current records.
        Schema::table('students', function (Blueprint $table) {
            $table->index('admission_number');
            $table->index('class_name');
        });

        // Modify an existing database table while preserving its current records.
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->index('status');
            $table->index('requested_class');
        });

        // Modify an existing database table while preserving its current records.
        Schema::table('payments', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('status');
            $table->index('mpesa_receipt');
        });
    }

    /**
     * Reverse this migration's schema changes so it can be rolled back.
     */
    public function down()
    {
        // Modify an existing database table while preserving its current records.
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['admission_number']);
            $table->dropIndex(['class_name']);
        });
        // Modify an existing database table while preserving its current records.
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['requested_class']);
        });
        // Modify an existing database table while preserving its current records.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['mpesa_receipt']);
        });
    }
}
