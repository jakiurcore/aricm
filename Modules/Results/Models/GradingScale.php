<?php

namespace Modules\Results\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Results\Services\Engine\GradeScale;

#[Fillable(['name', 'is_default'])]
class GradingScale extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GradingScaleItem::class)->orderByDesc('min_percent');
    }

    public static function default(): ?self
    {
        return self::query()->where('is_default', true)->first() ?? self::query()->first();
    }

    public function toEngine(): GradeScale
    {
        return new GradeScale($this->items->map(fn (GradingScaleItem $item): array => [
            'min' => (float) $item->min_percent, 'grade' => $item->grade, 'point' => (float) $item->grade_point,
        ])->all());
    }
}
