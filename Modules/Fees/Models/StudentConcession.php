<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Enums\ConcessionReason;
use Modules\Students\Models\Student;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['student_id', 'fee_head_id', 'kind', 'value', 'reason_type', 'note', 'valid_from_month', 'valid_to_month', 'approved_by'])]
class StudentConcession extends Model
{
    use LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'kind' => ConcessionKind::class,
            'reason_type' => ConcessionReason::class,
            'valid_from_month' => 'date',
            'valid_to_month' => 'date',
            'value' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fees')->logOnly(['student_id', 'fee_head_id', 'kind', 'value', 'reason_type', 'valid_from_month', 'valid_to_month'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
