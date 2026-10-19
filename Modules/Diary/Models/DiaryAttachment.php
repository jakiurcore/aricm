<?php

namespace Modules\Diary\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['diary_entry_id', 'path', 'original_name', 'mime', 'size'])]
class DiaryAttachment extends Model
{
    public function entry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class, 'diary_entry_id');
    }

    public function url(): string
    {
        return Storage::disk(config('modules.diary.attachments.disk', 'public'))->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
