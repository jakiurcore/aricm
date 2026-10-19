<?php

namespace Modules\Results\Services\Engine;

final readonly class SubjectOutcome
{
    /**
     * @param  list<array{name: string, full: float, pass: float, obtained: float|null, absent: bool}>  $components
     */
    public function __construct(
        public int $examSubjectId,
        public string $name,
        public float $total,
        public float $fullMarks,
        public float $percentage,
        public string $grade,
        public float $point,
        public bool $passed,
        public bool $absent,
        public bool $incomplete,
        public bool $includeInGpa,
        public bool $isOptional,
        public array $components,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
