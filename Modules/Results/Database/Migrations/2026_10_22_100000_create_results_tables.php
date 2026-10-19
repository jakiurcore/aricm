<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('grading_scale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_percent', 5, 2);
            $table->decimal('max_percent', 5, 2);
            $table->string('grade', 10);
            $table->decimal('grade_point', 3, 2);
        });

        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_subject_component_id')->nullable()->constrained()->restrictOnDelete();
            // component id, or 0 for a subject without components: lets one unique index cover both cases.
            $table->unsignedBigInteger('component_key')->default(0);
            $table->decimal('obtained', 6, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'exam_subject_id', 'component_key'], 'marks_cell_unique');
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->unsignedSmallInteger('roll')->nullable();
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->decimal('full_marks', 8, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->decimal('gpa', 3, 2)->default(0);
            $table->string('grade', 10)->default('F');
            $table->boolean('is_passed')->default(false);
            $table->boolean('is_incomplete')->default(false);
            $table->unsignedSmallInteger('position_in_class')->nullable();
            $table->unsignedSmallInteger('position_in_section')->nullable();
            $table->unsignedSmallInteger('failed_subjects_count')->default(0);
            $table->json('subject_results');
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['exam_id', 'class_id']);
        });

        // Publishing is a Results concern, but the flag lives with the exam it belongs to.
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('result_published')->default(false)->after('admit_card_released');
            $table->timestamp('result_published_at')->nullable()->after('result_published');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['result_published', 'result_published_at']);
        });

        Schema::dropIfExists('results');
        Schema::dropIfExists('marks');
        Schema::dropIfExists('grading_scale_items');
        Schema::dropIfExists('grading_scales');
    }
};
