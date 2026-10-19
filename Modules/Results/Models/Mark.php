<?php

namespace Modules\Results\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'exam_subject_id', 'exam_subject_component_id', 'component_key', 'obtained', 'is_absent', 'entered_by'])]
class Mark extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['obtained' => 'float', 'is_absent' => 'boolean'];
    }
}
