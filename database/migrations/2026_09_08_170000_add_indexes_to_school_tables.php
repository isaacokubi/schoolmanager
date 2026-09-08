<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToSchoolTables extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('admission_number');
            $table->index('class_name');
        });

        Schema::table('admission_applications', function (Blueprint $table) {
            $table->index('status');
            $table->index('requested_class');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('status');
            $table->index('mpesa_receipt');
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['admission_number']);
            $table->dropIndex(['class_name']);
        });
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['requested_class']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['mpesa_receipt']);
        });
    }
}
