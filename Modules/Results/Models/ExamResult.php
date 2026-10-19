<?php

namespace Modules\Results\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Exams\Models\Exam;
use Modules\Students\Models\Student;

#[Fillable(['exam_id', 'student_id', 'class_id', 'section_id', 'roll', 'total_marks', 'full_marks', 'percentage', 'gpa', 'grade', 'is_passed', 'is_incomplete', 'position_in_class', 'position_in_section', 'failed_subjects_count', 'subject_results'])]
class ExamResult extends Model
{
    protected $table = 'results';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_marks' => 'float', 'full_marks' => 'float', 'percentage' => 'float', 'gpa' => 'float',
            'is_passed' => 'boolean', 'is_incomplete' => 'boolean', 'subject_results' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
