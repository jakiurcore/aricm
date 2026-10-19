<?php

namespace Modules\Exams\Services;

use Illuminate\Support\Collection;
use Laramod\Facades\Modules;
use Modules\Exams\Contracts\ProvidesAdmitCardEligibility;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamRoutine;
use Modules\Exams\Support\Eligibility;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentDirectory;

/**
 * Decides who may get an admit card and gathers what a card shows.
 */
class AdmitCardService
{
    public function __construct(protected ExamDirectory $exams, protected StudentDirectory $students) {}

    /**
     * Whether a student may receive the card. Basic rules always apply; $checkProviders asks every module that can
     * hold a card back (Fees) and lets an admin override them when false. $ignoreRelease previews the roster
     * before the release switch is turned on.
     */
    public function eligibility(Exam $exam, Student $student, bool $checkProviders = true, bool $ignoreRelease = false): Eligibility
    {
        if (! $ignoreRelease && ! $exam->admit_card_released) {
            return Eligibility::deny(__('exams::messages.admit.not_released'));
        }

        if ($student->status !== StudentStatus::Active) {
            return Eligibility::deny(__('exams::messages.admit.inactive'));
        }

        if (! $this->exams->examHasClass($exam, $student->class_id) || $student->session_id !== $exam->session_id) {
            return Eligibility::deny(__('exams::messages.admit.not_in_exam'));
        }

        if ($this->exams->routineFor($exam, $student->class_id)->isEmpty()) {
            return Eligibility::deny(__('exams::messages.admit.no_routine'));
        }

        if (! $checkProviders) {
            return Eligibility::allow();
        }

        foreach (Modules::providing(ProvidesAdmitCardEligibility::class) as $module) {
            $decision = $module->admitCardEligibility($exam, $student);

            if (! $decision->allowed) {
                return $decision;
            }
        }

        return Eligibility::allow();
    }

    /**
     * Students of a class (and section) with their eligibility, for the admin generation screen.
     *
     * @return Collection<int, array{student: Student, eligibility: Eligibility}>
     */
    public function roster(Exam $exam, int $classId, ?int $sectionId = null, bool $checkProviders = true, bool $ignoreRelease = false): Collection
    {
        return $this->students->activeInClass($classId, $sectionId, $exam->session_id)
            ->map(fn (Student $student): array => ['student' => $student, 'eligibility' => $this->eligibility($exam, $student, $checkProviders, $ignoreRelease)]);
    }

    /**
     * Everything one card shows.
     *
     * @return array{exam: Exam, student: Student, routine: Collection<int, ExamRoutine>}
     */
    public function payload(Exam $exam, Student $student): array
    {
        return [
            'exam' => $exam,
            'student' => $student->loadMissing(['schoolClass', 'section']),
            'routine' => $this->exams->routineFor($exam, $student->class_id),
        ];
    }
}
