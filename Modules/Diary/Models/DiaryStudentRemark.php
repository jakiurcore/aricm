<?php

namespace Modules\Diary\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Diary\Enums\RemarkKind;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teacher;

#[Fillable(['diary_entry_id', 'student_id', 'teacher_id', 'kind', 'text'])]
class DiaryStudentRemark extends Model
{
    protected function casts(): array
    {
        return ['kind' => RemarkKind::class];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class, 'diary_entry_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
