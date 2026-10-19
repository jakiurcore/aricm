<?php

namespace Modules\Teachers\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;

/**
 * Teacher X teaches subject Y in section Z during session S; or (is_class_teacher, no subject) is the class teacher
 * of that section.
 */
#[Fillable(['teacher_id', 'session_id', 'class_id', 'section_id', 'subject_id', 'is_class_teacher'])]
class TeacherAssignment extends Model
{
    protected function casts(): array
    {
        return ['is_class_teacher' => 'boolean'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
