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
        Schema::create('parents', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone',30); $table->string('email')->nullable(); $table->string('relationship')->nullable(); $table->timestamps(); $table->index('phone');
        });
        // Alter an existing table without dropping its existing records.
        Schema::table('students', function (Blueprint $table) { $table->foreignId('parent_id')->nullable()->after('id')->constrained('parents')->nullOnDelete(); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('school_classes', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('stream')->nullable(); $table->unsignedSmallInteger('academic_year')->nullable(); $table->timestamps(); $table->index(['name','academic_year']); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('teachers', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('email')->nullable(); $table->string('phone',30)->nullable(); $table->string('employee_number',50)->nullable()->unique(); $table->timestamps(); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('subjects', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code',30)->nullable()->unique(); $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete(); $table->timestamps(); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('attendance', function (Blueprint $table) { $table->id(); $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); $table->date('attendance_date'); $table->enum('status',['present','absent','late','excused'])->default('present'); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['student_id','attendance_date']); $table->index('attendance_date'); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('exams', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('term',50); $table->unsignedSmallInteger('academic_year'); $table->date('start_date')->nullable(); $table->date('end_date')->nullable(); $table->timestamps(); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('results', function (Blueprint $table) { $table->id(); $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete(); $table->foreignId('student_id')->constrained('students')->cascadeOnDelete(); $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete(); $table->decimal('marks',5,2)->nullable(); $table->string('grade',5)->nullable(); $table->text('remarks')->nullable(); $table->timestamps(); $table->unique(['exam_id','student_id','subject_id']); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('announcements', function (Blueprint $table) { $table->id(); $table->string('title'); $table->text('body'); $table->boolean('published')->default(true); $table->timestamp('published_at')->nullable(); $table->timestamps(); $table->index(['published','published_at']); });
        // Create a new table; the callback below defines its fields and constraints.
        Schema::create('events', function (Blueprint $table) { $table->id(); $table->string('title'); $table->date('event_date'); $table->string('location')->nullable(); $table->text('description')->nullable(); $table->timestamps(); $table->index('event_date'); });
    }
    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down(): void
    {
        // Remove this table when rolling back, if it exists.
        Schema::dropIfExists('results'); Schema::dropIfExists('exams'); Schema::dropIfExists('attendance'); Schema::dropIfExists('subjects'); Schema::dropIfExists('teachers'); Schema::dropIfExists('school_classes'); Schema::table('students', function (Blueprint $table) { $table->dropConstrainedForeignId('parent_id'); }); Schema::dropIfExists('parents'); Schema::dropIfExists('announcements'); Schema::dropIfExists('events');
    }
};
