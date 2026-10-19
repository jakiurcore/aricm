<?php

namespace Modules\Results\Services\Engine;

/**
 * The result rules, with no database in sight.
 *
 *  - A subject fails if any component is below its pass mark, the subject total is below the subject pass mark,
 *    or the student was absent in any part. Marks that were never entered count as zero (and flag the result
 *    incomplete).
 *  - Subject grade and point come from the grading scale by percentage of the subject's full marks; a failed
 *    subject is always F / 0.00.
 *  - A failed compulsory subject makes the student fail: final grade F, GPA 0.00. A failed optional subject
 *    does not.
 *  - GPA is the average point of the subjects that count towards it (2 decimals, never above the scale's maximum).
 *    With $optionalBonus, the points of a passed optional subject above 2.00 are added on top of the sum before
 *    averaging, the Bangladeshi "4th subject" rule.
 */
class ResultCalculator
{
    public const float OPTIONAL_BASE_POINT = 2.0;

    /**
     * @param  list<SubjectSpec>  $subjects
     * @param  array<string, MarkInput>  $marks  Keyed "{examSubjectId}:{componentId or 0}".
     */
    public function calculate(array $subjects, array $marks, GradeScale $scale, bool $optionalBonus = false): StudentOutcome
    {
        $outcomes = array_map(fn (SubjectSpec $subject): SubjectOutcome => $this->subject($subject, $marks, $scale), $subjects);

        $total = array_sum(array_map(fn (SubjectOutcome $outcome): float => $outcome->total, $outcomes));
        $full = array_sum(array_map(fn (SubjectOutcome $outcome): float => $outcome->fullMarks, $outcomes));
        $failedCompulsory = count(array_filter($outcomes, fn (SubjectOutcome $outcome): bool => ! $outcome->passed && ! $outcome->isOptional));
        $passed = $failedCompulsory === 0 && $outcomes !== [];

        $gpa = $passed ? $this->gpa($outcomes, $scale, $optionalBonus) : 0.0;

        return new StudentOutcome(
            subjects: $outcomes,
            total: $total,
            fullMarks: $full,
            percentage: $full > 0 ? round($total / $full * 100, 2) : 0.0,
            gpa: $gpa,
            grade: $passed ? $scale->gradeForGpa($gpa) : $scale->failGrade,
            passed: $passed,
            failedSubjects: $failedCompulsory,
            incomplete: (bool) array_filter($outcomes, fn (SubjectOutcome $outcome): bool => $outcome->incomplete),
        );
    }

    /**
     * @param  array<string, MarkInput>  $marks
     */
    protected function subject(SubjectSpec $subject, array $marks, GradeScale $scale): SubjectOutcome
    {
        $parts = $subject->components !== []
            ? array_map(fn (ComponentSpec $component): array => ['key' => "{$subject->examSubjectId}:{$component->id}", 'name' => $component->name, 'full' => $component->fullMarks, 'pass' => $component->passMarks], $subject->components)
            : [['key' => "{$subject->examSubjectId}:0", 'name' => $subject->name, 'full' => $subject->fullMarks, 'pass' => 0.0]];

        $total = 0.0;
        $anyAbsent = false;
        $anyBelowPass = false;
        $incomplete = false;
        $components = [];

        foreach ($parts as $part) {
            $mark = $marks[$part['key']] ?? null;
            $absent = $mark?->absent ?? false;
            $obtained = $absent ? null : $mark?->obtained;

            $incomplete = $incomplete || ($mark === null || (! $absent && $obtained === null));
            $anyAbsent = $anyAbsent || $absent;
            $value = $obtained ?? 0.0;
            $total += $value;
            $anyBelowPass = $anyBelowPass || $value + 1e-9 < $part['pass'];

            $components[] = ['name' => $part['name'], 'full' => $part['full'], 'pass' => $part['pass'], 'obtained' => $obtained, 'absent' => $absent];
        }

        $passed = ! $anyAbsent && ! $anyBelowPass && ! $incomplete && $total + 1e-9 >= $subject->passMarks;
        $percentage = $subject->fullMarks > 0 ? $total / $subject->fullMarks * 100 : 0.0;
        $band = $scale->forPercentage($percentage);

        return new SubjectOutcome(
            examSubjectId: $subject->examSubjectId,
            name: $subject->name,
            total: $total,
            fullMarks: $subject->fullMarks,
            percentage: round($percentage, 2),
            grade: $passed ? $band['grade'] : $scale->failGrade,
            point: $passed ? $band['point'] : 0.0,
            passed: $passed,
            absent: $anyAbsent,
            incomplete: $incomplete,
            includeInGpa: $subject->includeInGpa,
            isOptional: $subject->isOptional,
            components: $components,
        );
    }

    /**
     * @param  list<SubjectOutcome>  $outcomes
     */
    protected function gpa(array $outcomes, GradeScale $scale, bool $optionalBonus): float
    {
        $counted = array_values(array_filter($outcomes, fn (SubjectOutcome $outcome): bool => $outcome->includeInGpa && ! $outcome->isOptional));

        if ($counted === []) {
            return 0.0;
        }

        $points = array_sum(array_map(fn (SubjectOutcome $outcome): float => $outcome->point, $counted));

        if ($optionalBonus) {
            foreach ($outcomes as $outcome) {
                if ($outcome->isOptional && $outcome->passed) {
                    $points += max(0.0, $outcome->point - self::OPTIONAL_BASE_POINT);
                }
            }
        }

        return min($scale->maxPoint(), round($points / count($counted), 2));
    }
}
