<?php

namespace Modules\Exams\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['exam_subject_id', 'name_bn', 'name_en', 'full_marks', 'pass_marks', 'sort_order'])]
class ExamSubjectComponent extends Model
{
    public $timestamps = false;

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }
}
