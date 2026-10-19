<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fees\Casts\MoneyCast;
use Modules\Students\Enums\ResidencyType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['session_id', 'class_id', 'fee_head_id', 'residency_type', 'amount'])]
class FeeStructure extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::saving(function (self $structure): void {
            $structure->scope = $structure->residency_type?->value ?? 'all';
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'residency_type' => ResidencyType::class];
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fees')->logOnly(['session_id', 'class_id', 'fee_head_id', 'residency_type', 'amount'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
