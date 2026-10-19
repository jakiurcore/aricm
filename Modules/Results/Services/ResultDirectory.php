<?php

namespace Modules\Results\Services;

use Illuminate\Support\Collection;
use Laramod\Facades\Modules;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamDirectory;
use Modules\Exams\Support\Eligibility;
use Modules\Results\Contracts\ProvidesResultEligibility;
use Modules\Results\Models\ExamResult;
use Modules\Students\Models\Student;

/**
 * Read API for what students and the public may see. Nothing is returned unless the exam's results are published.
 */
class ResultDirectory
{
    public function __construct(protected ExamDirectory $exams) {}

    public function isPublished(Exam $exam): bool
    {
        return (bool) $exam->result_published;
    }

    /**
     * @return Collection<int, Exam>
     */
    public function publishedExamsFor(Student $student): Collection
    {
        return $this->exams->forClass($student->class_id)->filter(fn (Exam $exam): bool => $this->isPublished($exam) && ExamResult::query()->where(['exam_id' => $exam->id, 'student_id' => $student->id])->exists())->values();
    }

    /**
     * The student's published result for an exam, or null.
     */
    public function publishedResult(Exam $exam, Student $student): ?ExamResult
    {
        if (! $this->isPublished($exam)) {
            return null;
        }

        return ExamResult::query()->with(['exam', 'student.schoolClass', 'student.section'])->where(['exam_id' => $exam->id, 'student_id' => $student->id])->first();
    }

    /**
     * Whether this student may be shown the result right now: every eligibility provider must agree.
     */
    public function eligibility(Exam $exam, Student $student): Eligibility
    {
        foreach (Modules::providing(ProvidesResultEligibility::class) as $module) {
            $decision = $module->resultEligibility($exam, $student);

            if (! $decision->allowed) {
                return $decision;
            }
        }

        return Eligibility::allow();
    }

    public function latestPublishedFor(Student $student): ?ExamResult
    {
        $exam = $this->publishedExamsFor($student)->first();

        return $exam ? $this->publishedResult($exam, $student) : null;
    }

    /**
     * Results of a class (optionally one section) for the admin preview, best position first.
     *
     * @return Collection<int, ExamResult>
     */
    public function classResults(Exam $exam, int $classId, ?int $sectionId = null): Collection
    {
        return ExamResult::query()
            ->with(['student', 'section'])
            ->where(['exam_id' => $exam->id, 'class_id' => $classId])
            ->when($sectionId, fn ($query, int $id) => $query->where('section_id', $id))
            ->orderBy('position_in_class')->orderBy('roll')
            ->get();
    }
}
