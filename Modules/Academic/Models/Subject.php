<?php

namespace Modules\Academic\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Academic\Database\Factories\SubjectFactory;

#[UseFactory(SubjectFactory::class)]
#[Fillable(['name_bn', 'name_en', 'code'])]
class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }
}
