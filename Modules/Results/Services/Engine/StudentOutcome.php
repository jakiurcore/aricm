<?php

namespace Modules\Results\Services\Engine;

final readonly class StudentOutcome
{
    /**
     * @param  list<SubjectOutcome>  $subjects
     */
    public function __construct(
        public array $subjects,
        public float $total,
        public float $fullMarks,
        public float $percentage,
        public float $gpa,
        public string $grade,
        public bool $passed,
        public int $failedSubjects,
        public bool $incomplete,
    ) {}
}
