<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academic\Database\Factories\SectionFactory;

#[UseFactory(SectionFactory::class)]
#[Fillable(['class_id', 'name_bn', 'name_en'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }
}
