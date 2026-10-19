<?php

namespace Modules\Students\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\ImageUploader;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentEnrollment;

/**
 * Creates and updates students together with their enrollment history row.
 */
class StudentRegistrar
{
    public function __construct(
        protected StudentIdGenerator $ids,
        protected StudentPasswords $passwords,
        protected ImageUploader $images,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated attributes (no student_id/password).
     */
    public function register(array $data, ?UploadedFile $photo = null): Student
    {
        return DB::transaction(function () use ($data, $photo): Student {
            $admission = $data['admission_date'] ?? now()->toDateString();
            $student = new Student($data);
            $student->student_id = $this->ids->next((int) date('Y', strtotime((string) $admission)));
            $student->password = $this->passwords->defaultFor($student);
            $student->must_change_password = true;

            if ($photo) {
                $student->photo = $this->images->store($photo, 'students', 800);
            }

            $student->save();
            $this->syncEnrollment($student);

            // Pick up the column defaults (status, residency type…) the database applied.
            return $student->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data, ?UploadedFile $photo = null): Student
    {
        return DB::transaction(function () use ($student, $data, $photo): Student {
            $oldPhoto = $student->photo;

            $student->fill($data);

            if ($photo) {
                $student->photo = $this->images->store($photo, 'students', 800);
            }

            $student->save();
            $this->syncEnrollment($student);

            if ($photo) {
                $this->images->delete($oldPhoto);
            }

            return $student;
        });
    }

    /**
     * Keep the enrollment row of the student's current session in line with the student record.
     */
    public function syncEnrollment(Student $student): void
    {
        StudentEnrollment::query()->updateOrCreate(
            ['student_id' => $student->id, 'session_id' => $student->session_id],
            ['class_id' => $student->class_id, 'section_id' => $student->section_id, 'roll' => $student->roll, 'status' => 'active'],
        );
    }
}
