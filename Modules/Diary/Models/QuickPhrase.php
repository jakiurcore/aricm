<?php

namespace Modules\Diary\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Diary\Enums\PhraseField;
use Modules\Teachers\Models\Teacher;

#[Fillable(['teacher_id', 'field', 'text_bn', 'sort_order', 'is_active'])]
class QuickPhrase extends Model
{
    protected $table = 'teacher_quick_phrases';

    protected function casts(): array
    {
        return ['field' => PhraseField::class, 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
