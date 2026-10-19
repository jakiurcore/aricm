<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->foreignId('session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('admit_card_released')->default(false);
            // The grading_scales table belongs to Results, which is downstream: a plain column, no foreign key.
            $table->unsignedBigInteger('grading_scale_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();

            $table->unique(['exam_id', 'class_id']);
        });

        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->unsignedSmallInteger('full_marks')->default(100);
            $table->unsignedSmallInteger('pass_marks')->default(33);
            $table->boolean('include_in_gpa')->default(true);
            $table->boolean('is_optional')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['exam_id', 'class_id', 'subject_id']);
        });

        Schema::create('exam_subject_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_subject_id')->constrained()->cascadeOnDelete();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->unsignedSmallInteger('full_marks');
            $table->unsignedSmallInteger('pass_marks')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::create('exam_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 50)->nullable();

            $table->unique(['exam_id', 'class_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_routines');
        Schema::dropIfExists('exam_subject_components');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exam_class');
        Schema::dropIfExists('exams');
    }
};
