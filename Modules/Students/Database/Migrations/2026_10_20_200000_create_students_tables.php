<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 30)->unique();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->unsignedInteger('roll')->nullable();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_relation', 50)->nullable();
            $table->date('date_of_birth');
            $table->string('gender', 10);
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 50)->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('photo')->nullable();
            $table->date('admission_date');
            $table->string('status', 20)->default('active')->index();
            $table->string('residency_type', 20)->default('non_residential');
            $table->string('password');
            $table->boolean('must_change_password')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['class_id', 'section_id']);
            $table->unique(['session_id', 'class_id', 'section_id', 'roll'], 'students_roll_unique');
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->unsignedInteger('roll')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['student_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
    }
};
