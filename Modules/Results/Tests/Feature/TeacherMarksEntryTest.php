<?php

use Modules\Core\Services\Settings;
use Modules\Results\Models\Mark;
use Modules\Teachers\Services\TeacherAssignments;

/**
 * A graded exam with two subjects; the teacher teaches only the first in the student's section.
 *
 * @return array<string, mixed>
 */
function marksSetup(bool $enabled = true): array
{
    $s = teachingSetup();
    $s['student'] = enrolled($s['p']);
    $g = gradedExam($s['p']);
    $s = [...$s, ...$g];
    app(TeacherAssignments::class)->assignSubject($s['teacher'], $s['p']['session']->id, $s['p']['class']->id, $s['p']['section']->id, $g['first']->subject_id);
    app(Settings::class)->set('teacher_marks_entry_enabled', $enabled);

    return $s;
}

function marksUrl(string $name, array $s, ?int $section = null): string
{
    return route($name, ['exam' => $s['exam']->id, 'class' => $s['p']['class']->id, 'section' => $section ?? $s['p']['section']->id]);
}

it('is off by default', function () {
    $s = marksSetup(false);

    $this->actingAs($s['teacher']->user)->getJson(route('results.teacher.options'))->assertOk()->assertJsonPath('data.enabled', false)->assertJsonCount(0, 'data.options');
    $this->actingAs($s['teacher']->user)->getJson(marksUrl('results.teacher.show', $s))->assertForbidden();
    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $s['student']->id, 'exam_subject_id' => $s['first']->id, 'obtained' => 50]]])->assertForbidden();
});

it('offers a teacher only the exams, sections and subjects they teach', function () {
    $s = marksSetup();

    $options = $this->actingAs($s['teacher']->user)->getJson(route('results.teacher.options'))->assertOk()->json('data.options');

    expect($options)->toHaveCount(1)
        ->and($options[0]['subjects'])->toHaveCount(1)
        ->and($options[0]['subjects'][0]['exam_subject_id'])->toBe($s['first']->id);
});

it('shows only the teacher’s subject columns in the grid', function () {
    $s = marksSetup();

    $grid = $this->actingAs($s['teacher']->user)->getJson(marksUrl('results.teacher.show', $s))->assertOk()->json('data');

    expect(array_column($grid['columns'], 'exam_subject_id'))->toBe([$s['first']->id])
        ->and($grid['students'])->toHaveCount(1);
});

it('saves marks for the teacher’s own subject and records who entered them', function () {
    $s = marksSetup();

    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $s['student']->id, 'exam_subject_id' => $s['first']->id, 'obtained' => 72]]])
        ->assertOk()->assertJsonPath('data.saved', 1);

    $mark = Mark::where(['student_id' => $s['student']->id, 'exam_subject_id' => $s['first']->id])->first();
    expect((float) $mark->obtained)->toBe(72.0)->and($mark->entered_by)->toBe($s['teacher']->user->id);
});

it('refuses marks for a subject the teacher does not teach', function () {
    $s = marksSetup();

    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $s['student']->id, 'exam_subject_id' => $s['second']->id, 'obtained' => 60]]])
        ->assertUnprocessable()->assertJsonValidationErrors('entries.0.student_id');

    expect(Mark::count())->toBe(0);
});

it('refuses a section the teacher is not assigned to and students from another section', function () {
    $s = marksSetup();
    $outsider = enrolled(['session' => $s['p']['session'], 'class' => $s['p']['class'], 'section' => $s['other']]);

    $this->actingAs($s['teacher']->user)->getJson(marksUrl('results.teacher.show', $s, $s['other']->id))->assertForbidden();
    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $outsider->id, 'exam_subject_id' => $s['first']->id, 'obtained' => 60]]])->assertUnprocessable();
});

it('validates the range and refuses writes once the result is published', function () {
    $s = marksSetup();

    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $s['student']->id, 'exam_subject_id' => $s['first']->id, 'obtained' => 101]]])->assertUnprocessable();

    $s['exam']->forceFill(['result_published' => true])->save();
    $this->actingAs($s['teacher']->user)->putJson(marksUrl('results.teacher.update', $s), ['entries' => [['student_id' => $s['student']->id, 'exam_subject_id' => $s['first']->id, 'obtained' => 50]]])->assertUnprocessable();
});

it('keeps non-teachers out', function () {
    $s = marksSetup();

    $this->actingAs(userWithRole('accountant'))->getJson(route('results.teacher.options'))->assertForbidden();
});
