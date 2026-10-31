<?php

namespace Modules\Exams\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Exams\Database\Factories\ExamFactory;
use Modules\Exams\Enums\ExamStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[UseFactory(ExamFactory::class)]
#[Fillable(['name_bn', 'name_en', 'session_id', 'start_date', 'end_date', 'status', 'admit_card_released', 'grading_scale_id', 'description'])]
class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ExamStatus::class,
            'admit_card_released' => 'boolean',
            // Added by the Results module's migration; cast here so every reader of an exam gets real booleans.
            'result_published' => 'boolean',
            'result_published_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'exam_class', 'exam_id', 'class_id')->orderBy('numeric_order');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ExamSubject::class)->orderBy('sort_order');
    }

    public function routines(): HasMany
    {
        return $this->hasMany(ExamRoutine::class)->orderBy('exam_date')->orderBy('start_time');
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('exams')->logOnly(['name_bn', 'status', 'admit_card_released'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
