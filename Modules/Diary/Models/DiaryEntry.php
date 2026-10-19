<?php

namespace Modules\Diary\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Diary\Database\Factories\DiaryEntryFactory;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Teachers\Models\Teacher;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * One teacher's diary for one subject (or the class teacher's general note) in one section on one day.
 * Every change is logged, so edits after publishing leave a revision trail.
 */
#[UseFactory(DiaryEntryFactory::class)]
#[Fillable(['session_id', 'class_id', 'section_id', 'subject_id', 'teacher_id', 'entry_date', 'lesson', 'homework', 'note', 'homework_due_date', 'status', 'published_at', 'last_edited_at'])]
class DiaryEntry extends Model
{
    /** @use HasFactory<DiaryEntryFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date:Y-m-d',
            'homework_due_date' => 'date:Y-m-d',
            'status' => DiaryStatus::class,
            'published_at' => 'datetime',
            'last_edited_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DiaryAttachment::class);
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(DiaryStudentRemark::class);
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(DiaryAcknowledgement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === DiaryStatus::Published;
    }

    public function hasContent(): bool
    {
        return filled(trim((string) $this->lesson)) || filled(trim((string) $this->homework)) || filled(trim((string) $this->note));
    }

    public function subjectName(): string
    {
        return $this->subject?->displayName() ?? __('diary::messages.general_note');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('diary')
            ->logOnly(['lesson', 'homework', 'note', 'homework_due_date', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
