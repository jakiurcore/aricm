<?php

namespace Modules\Teachers\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounts\Models\User;
use Modules\Teachers\Database\Factories\TeacherFactory;

#[UseFactory(TeacherFactory::class)]
#[Fillable(['user_id', 'name_bn', 'name_en', 'designation', 'phone', 'is_active'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }
}
