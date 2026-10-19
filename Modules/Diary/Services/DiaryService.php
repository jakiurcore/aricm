<?php

namespace Modules\Diary\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryAttachment;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\DiaryStudentRemark;
use Modules\Students\Models\Student;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Services\TeacherScope;

/**
 * Writes the diary. A teacher may write only for a section and subject assigned to them (the class teacher also for
 * the day's general note); the date must be a school day, not in the future and not older than the backdate limit.
 */
class DiaryService
{
    public function __construct(
        protected TeacherScope $scope,
        protected SchoolCalendar $calendar,
        protected DiarySettings $settings,
        protected AcademicDirectory $academic,
    ) {}

    /**
     * Create or update the teacher's entry for the section, subject and day. Safe to call repeatedly (autosave).
     *
     * @param  array{class_id: int, section_id: int, subject_id?: int|null, entry_date: string, lesson?: string|null, homework?: string|null, note?: string|null, homework_due_date?: string|null}  $data
     */
    public function save(Authenticatable $user, array $data): DiaryEntry
    {
        $teacher = $this->teacherOrFail($user);
        $subjectId = $data['subject_id'] ?? null;
        $this->authorizeSection($user, (int) $data['class_id'], (int) $data['section_id'], $subjectId ? (int) $subjectId : null);

        $date = Carbon::parse($data['entry_date'])->startOfDay();
        $this->checkDate($date);

        $entry = DiaryEntry::query()
            ->where(['teacher_id' => $teacher->id, 'section_id' => $data['section_id'], 'subject_id' => $subjectId])
            ->whereDate('entry_date', $date->toDateString())->first()
            ?? new DiaryEntry(['teacher_id' => $teacher->id, 'section_id' => $data['section_id'], 'subject_id' => $subjectId, 'entry_date' => $date->toDateString()]);

        if ($entry->exists && ! $this->canEdit($user, $entry)) {
            throw ValidationException::withMessages(['entry_date' => __('diary::messages.edit_window_closed')]);
        }

        $wasPublished = $entry->exists && $entry->isPublished();

        $entry->fill([
            'session_id' => $this->academic->currentSession()?->id,
            'class_id' => $data['class_id'],
            'lesson' => $this->clean($data['lesson'] ?? null),
            'homework' => $this->clean($data['homework'] ?? null),
            'note' => $this->clean($data['note'] ?? null),
            'homework_due_date' => $this->dueDate($date, $data['homework'] ?? null, $data['homework_due_date'] ?? null),
        ]);

        if ($wasPublished && $entry->isDirty()) {
            $entry->last_edited_at = now();
        }

        $entry->save();

        return $entry;
    }

    public function publish(Authenticatable $user, DiaryEntry $entry): DiaryEntry
    {
        abort_unless($this->canEdit($user, $entry), 403);

        if (! $entry->hasContent()) {
            throw ValidationException::withMessages(['lesson' => __('diary::messages.empty_entry')]);
        }

        $entry->update(['status' => DiaryStatus::Published, 'published_at' => $entry->published_at ?? now()]);

        return $entry;
    }

    public function delete(Authenticatable $user, DiaryEntry $entry): void
    {
        abort_unless($this->canDelete($user, $entry), 403);

        foreach ($entry->attachments as $attachment) {
            $this->deleteFile($attachment);
        }

        $entry->delete();
    }

    /**
     * The owner may edit a draft any time and a published entry until the edit window closes; diary managers always.
     */
    public function canEdit(?Authenticatable $user, DiaryEntry $entry): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('diary.manage')) {
            return true;
        }

        if (! $this->isOwner($user, $entry)) {
            return false;
        }

        return ! $entry->isPublished() || $entry->published_at === null || $entry->published_at->addHours($this->settings->editWindowHours())->isFuture();
    }

    public function canDelete(?Authenticatable $user, DiaryEntry $entry): bool
    {
        return $user !== null && ($user->can('diary.manage') || ($this->isOwner($user, $entry) && ! $entry->isPublished()));
    }

    public function isOwner(Authenticatable $user, DiaryEntry $entry): bool
    {
        return $this->scope->teacherFor($user)?->id === $entry->teacher_id;
    }

    public function addAttachment(Authenticatable $user, DiaryEntry $entry, UploadedFile $file): DiaryAttachment
    {
        abort_unless($this->canEdit($user, $entry), 403);

        if ($entry->attachments()->count() >= $this->settings->maxAttachments()) {
            throw ValidationException::withMessages(['file' => __('diary::messages.too_many_attachments', ['max' => $this->settings->maxAttachments()])]);
        }

        $path = $file->store("diary/{$entry->id}", config('modules.diary.attachments.disk', 'public'));

        return $entry->attachments()->create(['path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize()]);
    }

    public function removeAttachment(Authenticatable $user, DiaryAttachment $attachment): void
    {
        abort_unless($this->canEdit($user, $attachment->entry), 403);

        $this->deleteFile($attachment);
        $attachment->delete();
    }

    /**
     * A private remark about one student on the day's entry. The entry's owner and the section's class teacher may add them.
     */
    public function addRemark(Authenticatable $user, DiaryEntry $entry, Student $student, RemarkKind $kind, string $text): DiaryStudentRemark
    {
        $teacher = $this->teacherOrFail($user);
        abort_unless($this->isOwner($user, $entry) || $this->scope->isClassTeacher($user, $entry->class_id, $entry->section_id), 403);

        if ($student->class_id !== $entry->class_id || $student->section_id !== $entry->section_id) {
            throw ValidationException::withMessages(['student_id' => __('diary::messages.student_not_in_section')]);
        }

        return $entry->remarks()->create(['student_id' => $student->id, 'teacher_id' => $teacher->id, 'kind' => $kind, 'text' => trim($text)]);
    }

    public function removeRemark(Authenticatable $user, DiaryStudentRemark $remark): void
    {
        abort_unless($user->can('diary.manage') || $this->scope->teacherFor($user)?->id === $remark->teacher_id, 403);

        $remark->delete();
    }

    protected function teacherOrFail(Authenticatable $user): Teacher
    {
        $teacher = $this->scope->teacherFor($user);
        abort_unless($teacher, 403);

        return $teacher;
    }

    protected function authorizeSection(Authenticatable $user, int $classId, int $sectionId, ?int $subjectId): void
    {
        $allowed = $subjectId === null
            ? $this->scope->isClassTeacher($user, $classId, $sectionId)
            : $this->scope->canTeach($user, $classId, $sectionId, $subjectId);

        abort_unless($allowed, 403);
    }

    protected function checkDate(CarbonInterface $date): void
    {
        $today = now()->startOfDay();

        if ($date->greaterThan($today) || $date->lessThan($today->copy()->subDays($this->settings->backdateDays()))) {
            throw ValidationException::withMessages(['entry_date' => __('diary::messages.date_out_of_range', ['days' => $this->settings->backdateDays()])]);
        }

        if (! $this->calendar->isSchoolDay($date)) {
            throw ValidationException::withMessages(['entry_date' => __('diary::messages.not_a_school_day')]);
        }
    }

    /**
     * Homework is due on the next school day unless the teacher picked another day (moved to a school day if needed).
     */
    protected function dueDate(CarbonInterface $entryDate, ?string $homework, ?string $requested): ?string
    {
        if (blank(trim((string) $homework))) {
            return null;
        }

        if (! $requested) {
            return $this->calendar->nextSchoolDay($entryDate)->toDateString();
        }

        $due = Carbon::parse($requested)->startOfDay();

        if ($due->lessThanOrEqualTo($entryDate)) {
            return $this->calendar->nextSchoolDay($entryDate)->toDateString();
        }

        return $this->calendar->isSchoolDay($due) ? $due->toDateString() : $this->calendar->nextSchoolDay($due)->toDateString();
    }

    protected function clean(?string $text): ?string
    {
        $text = trim((string) $text);

        return $text === '' ? null : $text;
    }

    protected function deleteFile(DiaryAttachment $attachment): void
    {
        Storage::disk(config('modules.diary.attachments.disk', 'public'))->delete($attachment->path);
    }
}
