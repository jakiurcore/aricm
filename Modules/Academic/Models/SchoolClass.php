<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Database\Factories\SchoolClassFactory;
use Modules\Academic\Enums\Division;

#[UseFactory(SchoolClassFactory::class)]
#[Fillable(['name_bn', 'name_en', 'numeric_order', 'division', 'is_active'])]
class SchoolClass extends Model
{
    /** @use HasFactory<SchoolClassFactory> */
    use HasFactory;

    protected $table = 'classes';

    protected function casts(): array
    {
        return ['division' => Division::class, 'is_active' => 'boolean', 'numeric_order' => 'integer'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id')->orderBy('name_bn');
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot(['is_optional', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }
}
