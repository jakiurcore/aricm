<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Students\Database\Factories\StudentFactory;
use Modules\Students\Enums\Gender;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Enums\StudentStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A student. Also the authenticatable of the "student" guard (sign in with student_id + password).
 */
#[UseFactory(StudentFactory::class)]
#[Hidden(['password', 'remember_token'])]
class Student extends Authenticatable
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'status' => StudentStatus::class,
            'gender' => Gender::class,
            'residency_type' => ResidencyType::class,
            'roll' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'session_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)->orderByDesc('session_id');
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo ? Storage::disk('public')->url($this->photo) : null);
    }

    public function displayName(): string
    {
        return localized($this, 'name') ?? $this->name_bn;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('students')
            ->logOnly(['student_id', 'name_bn', 'class_id', 'section_id', 'roll', 'status', 'residency_type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
