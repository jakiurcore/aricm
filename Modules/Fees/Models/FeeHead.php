<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Fees\Database\Factories\FeeHeadFactory;
use Modules\Fees\Enums\FeeHeadType;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[UseFactory(FeeHeadFactory::class)]
#[Fillable(['name_bn', 'name_en', 'type', 'is_active', 'sort_order'])]
class FeeHead extends Model
{
    /** @use HasFactory<FeeHeadFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['type' => FeeHeadType::class, 'is_active' => 'boolean'];
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fees')->logOnly(['name_bn', 'type', 'is_active'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
