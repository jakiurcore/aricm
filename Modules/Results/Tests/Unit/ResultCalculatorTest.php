<?php

use Modules\Results\Services\Engine\ComponentSpec;
use Modules\Results\Services\Engine\GradeScale;
use Modules\Results\Services\Engine\MarkInput;
use Modules\Results\Services\Engine\ResultCalculator;
use Modules\Results\Services\Engine\SubjectSpec;

function bdScale(): GradeScale
{
    return new GradeScale([
        ['min' => 80, 'grade' => 'A+', 'point' => 5.00], ['min' => 70, 'grade' => 'A', 'point' => 4.00], ['min' => 60, 'grade' => 'A-', 'point' => 3.50],
        ['min' => 50, 'grade' => 'B', 'point' => 3.00], ['min' => 40, 'grade' => 'C', 'point' => 2.00], ['min' => 33, 'grade' => 'D', 'point' => 1.00], ['min' => 0, 'grade' => 'F', 'point' => 0.00],
    ]);
}

function single(int $id, string $name = 'বিষয়', bool $gpa = true, bool $optional = false): SubjectSpec
{
    return new SubjectSpec($id, $name, 100, 33, $gpa, $optional);
}

function marked(array $values): array
{
    $marks = [];
    foreach ($values as $id => $value) {
        $marks["{$id}:0"] = $value instanceof MarkInput ? $value : new MarkInput($value);
    }

    return $marks;
}

it('passes a student and averages the points', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2), single(3)], marked([1 => 85, 2 => 72, 3 => 65]), bdScale());

    expect($outcome->passed)->toBeTrue()
        ->and($outcome->gpa)->toBe(4.17)          // (5 + 4 + 3.5) / 3
        ->and($outcome->grade)->toBe('A')
        ->and($outcome->total)->toBe(222.0)
        ->and($outcome->percentage)->toBe(74.0)
        ->and($outcome->subjects[0]->grade)->toBe('A+')
        ->and($outcome->failedSubjects)->toBe(0);
});

it('uses the grade band boundaries inclusively', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2), single(3)], marked([1 => 80, 2 => 33, 3 => 79]), bdScale());

    expect(array_map(fn ($subject) => $subject->grade, $outcome->subjects))->toBe(['A+', 'D', 'A']);
});

it('fails the whole result with GPA 0 when a compulsory subject is failed', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2)], marked([1 => 90, 2 => 32]), bdScale());

    expect($outcome->passed)->toBeFalse()->and($outcome->gpa)->toBe(0.0)->and($outcome->grade)->toBe('F')
        ->and($outcome->failedSubjects)->toBe(1)
        ->and($outcome->subjects[1]->grade)->toBe('F')->and($outcome->subjects[1]->point)->toBe(0.0);
});

it('treats an absent student as failed', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2)], marked([1 => 90, 2 => MarkInput::absent()]), bdScale());

    expect($outcome->passed)->toBeFalse()->and($outcome->subjects[1]->absent)->toBeTrue()->and($outcome->subjects[1]->total)->toBe(0.0);
});

it('fails a subject when one component is below its pass mark even if the total passes', function () {
    $subject = new SubjectSpec(1, 'গণিত', 100, 33, components: [new ComponentSpec(11, 'লিখিত', 70, 23), new ComponentSpec(12, 'মৌখিক', 30, 10)]);

    $outcome = (new ResultCalculator)->calculate([$subject], ['1:11' => new MarkInput(65), '1:12' => new MarkInput(8)], bdScale());

    expect($outcome->subjects[0]->total)->toBe(73.0)
        ->and($outcome->subjects[0]->passed)->toBeFalse()
        ->and($outcome->passed)->toBeFalse();
});

it('passes a subject whose components all pass', function () {
    $subject = new SubjectSpec(1, 'গণিত', 100, 33, components: [new ComponentSpec(11, 'লিখিত', 70, 23), new ComponentSpec(12, 'মৌখিক', 30, 10)]);

    $outcome = (new ResultCalculator)->calculate([$subject], ['1:11' => new MarkInput(55), '1:12' => new MarkInput(20)], bdScale());

    expect($outcome->subjects[0]->passed)->toBeTrue()->and($outcome->subjects[0]->grade)->toBe('A')->and($outcome->subjects[0]->components)->toHaveCount(2);
});

it('fails when a component total is fine but the subject total is under the subject pass mark', function () {
    $subject = new SubjectSpec(1, 'ইংরেজি', 100, 40, components: [new ComponentSpec(11, 'লিখিত', 70, 0), new ComponentSpec(12, 'মৌখিক', 30, 0)]);

    $outcome = (new ResultCalculator)->calculate([$subject], ['1:11' => new MarkInput(25), '1:12' => new MarkInput(10)], bdScale());

    expect($outcome->subjects[0]->passed)->toBeFalse();
});

it('does not fail the student for a failed optional subject and keeps it out of the GPA', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2, 'ঐচ্ছিক', optional: true)], marked([1 => 85, 2 => 10]), bdScale());

    expect($outcome->passed)->toBeTrue()->and($outcome->gpa)->toBe(5.0)->and($outcome->failedSubjects)->toBe(0);
});

it('adds the optional bonus only when enabled and caps at the scale maximum', function () {
    $subjects = [single(1), single(2), single(3, 'ঐচ্ছিক', optional: true)];
    $marks = marked([1 => 70, 2 => 70, 3 => 85]);   // points 4, 4, optional 5

    $off = (new ResultCalculator)->calculate($subjects, $marks, bdScale(), false);
    $on = (new ResultCalculator)->calculate($subjects, $marks, bdScale(), true);
    $capped = (new ResultCalculator)->calculate([single(1), single(2, 'ঐচ্ছিক', optional: true)], marked([1 => 85, 2 => 85]), bdScale(), true);

    expect($off->gpa)->toBe(4.0)->and($on->gpa)->toBe(5.0)   // (4 + 4 + (5 − 2)) / 2 = 5.5 → capped
        ->and($capped->gpa)->toBe(5.0);
});

it('excludes subjects that do not count towards the GPA', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2, 'শারীরিক', gpa: false)], marked([1 => 90, 2 => 40]), bdScale());

    expect($outcome->gpa)->toBe(5.0)->and($outcome->total)->toBe(130.0);
});

it('flags missing marks as incomplete and counts them as zero', function () {
    $outcome = (new ResultCalculator)->calculate([single(1), single(2)], marked([1 => 90]), bdScale());

    expect($outcome->incomplete)->toBeTrue()->and($outcome->passed)->toBeFalse()->and($outcome->subjects[1]->incomplete)->toBeTrue();
});

it('maps a GPA to the final letter grade', function () {
    $scale = bdScale();

    expect($scale->gradeForGpa(5.0))->toBe('A+')->and($scale->gradeForGpa(4.5))->toBe('A')->and($scale->gradeForGpa(3.5))->toBe('A-')
        ->and($scale->gradeForGpa(2.5))->toBe('C')->and($scale->gradeForGpa(1.0))->toBe('D');
});
