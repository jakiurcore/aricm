<?php

namespace Modules\Exams\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Subject;

#[Fillable(['exam_id', 'class_id', 'subject_id', 'full_marks', 'pass_marks', 'include_in_gpa', 'is_optional', 'sort_order'])]
class ExamSubject extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['include_in_gpa' => 'boolean', 'is_optional' => 'boolean', 'full_marks' => 'integer', 'pass_marks' => 'integer'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ExamSubjectComponent::class)->orderBy('sort_order');
    }
}
