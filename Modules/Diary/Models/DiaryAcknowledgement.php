<?php

namespace Modules\Diary\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

#[Fillable(['diary_entry_id', 'student_id', 'seen_at', 'acknowledged_at'])]
class DiaryAcknowledgement extends Model
{
    protected function casts(): array
    {
        return ['seen_at' => 'datetime', 'acknowledged_at' => 'datetime'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class, 'diary_entry_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
