<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            // Null for the class teacher's general note of the day.
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->date('entry_date');
            $table->text('lesson')->nullable();
            $table->text('homework')->nullable();
            $table->text('note')->nullable();
            $table->date('homework_due_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'entry_date', 'status']);
            $table->index(['teacher_id', 'entry_date']);
        });

        Schema::create('diary_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('diary_student_remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('kind', 20)->default('info');
            $table->string('text', 500);
            $table->timestamps();

            $table->index(['student_id', 'diary_entry_id']);
        });

        Schema::create('diary_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['diary_entry_id', 'student_id']);
        });

        Schema::create('teacher_quick_phrases', function (Blueprint $table) {
            $table->id();
            // Null = shared with every teacher; set = that teacher's own phrase.
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
            $table->string('field', 20);
            $table->string('text_bn');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_quick_phrases');
        Schema::dropIfExists('diary_acknowledgements');
        Schema::dropIfExists('diary_student_remarks');
        Schema::dropIfExists('diary_attachments');
        Schema::dropIfExists('diary_entries');
    }
};
