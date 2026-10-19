<?php

namespace Modules\Results\Services\Engine;

use InvalidArgumentException;

/**
 * Percentage → letter grade and grade point, plus the reverse used for the final grade from a GPA.
 */
final class GradeScale
{
    /** @var list<array{min: float, grade: string, point: float}> Highest band first. */
    private array $bands;

    /**
     * @param  list<array{min: float|int, grade: string, point: float|int}>  $bands
     */
    public function __construct(array $bands, public readonly string $failGrade = 'F')
    {
        if ($bands === []) {
            throw new InvalidArgumentException('A grading scale needs at least one band.');
        }

        usort($bands, fn (array $a, array $b): int => $b['min'] <=> $a['min']);
        $this->bands = array_map(fn (array $band): array => ['min' => (float) $band['min'], 'grade' => $band['grade'], 'point' => (float) $band['point']], $bands);
    }

    /**
     * @return array{grade: string, point: float}
     */
    public function forPercentage(float $percentage): array
    {
        foreach ($this->bands as $band) {
            if ($percentage + 1e-9 >= $band['min']) {
                return ['grade' => $band['grade'], 'point' => $band['point']];
            }
        }

        return ['grade' => $this->failGrade, 'point' => 0.0];
    }

    /**
     * The final letter for a passing student: the best band whose point does not exceed the GPA (4.50 → "A").
     */
    public function gradeForGpa(float $gpa): string
    {
        $bands = $this->bands;
        usort($bands, fn (array $a, array $b): int => $b['point'] <=> $a['point']);

        foreach ($bands as $band) {
            if ($band['point'] > 0 && $gpa + 1e-9 >= $band['point']) {
                return $band['grade'];
            }
        }

        return $this->failGrade;
    }

    public function maxPoint(): float
    {
        return max(array_column($this->bands, 'point'));
    }
}
