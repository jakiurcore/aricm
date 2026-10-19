<?php

namespace Modules\Results\Services\Engine;

/**
 * One subject of an exam for a class, as the engine sees it. Plain data: no models, no database.
 */
final readonly class SubjectSpec
{
    /**
     * @param  list<ComponentSpec>  $components  Empty for a subject marked as a single paper.
     */
    public function __construct(
        public int $examSubjectId,
        public string $name,
        public float $fullMarks,
        public float $passMarks,
        public bool $includeInGpa = true,
        public bool $isOptional = false,
        public array $components = [],
    ) {}
}
