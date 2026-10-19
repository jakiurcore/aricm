<?php

use Modules\Exams\Models\ExamSubjectComponent;
use Modules\Results\Models\Mark;

it('shows the grid for the roster with one column per subject', function () {
    $p = placement();
    $setup = gradedExam($p);
    enrolled($p, ['roll' => 1]);
    enrolled($p, ['roll' => 2]);

    $this->actingAs(userWithRole('admin'))->getJson(route('results.api.marks.show', [$setup['exam'], $p['class']]))
        ->assertOk()->assertJsonCount(2, 'data.students')->assertJsonCount(2, 'data.columns')->assertJsonPath('data.published', false);
});

it('saves marks and clears a cell sent empty', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);
    $admin = userWithRole('admin');
    $url = route('results.api.marks.update', [$setup['exam'], $p['class']]);

    $this->actingAs($admin)->putJson($url, ['entries' => [['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => 85.5]]])->assertOk()->assertJsonPath('data.saved', 1);
    expect(Mark::first()->obtained)->toBe(85.5);

    $this->actingAs($admin)->putJson($url, ['entries' => [['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => null]]])->assertOk();
    expect(Mark::count())->toBe(0);
});

it('rejects marks above full marks or negative', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);

    $response = $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => 101],
        ['student_id' => $student->id, 'exam_subject_id' => $setup['second']->id, 'obtained' => -1],
    ]])->assertUnprocessable();

    $response->assertJsonValidationErrors(['entries.0.obtained', 'entries.1.obtained']);
    expect(Mark::count())->toBe(0);
});

it('validates against the component full marks', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);
    $component = ExamSubjectComponent::create(['exam_subject_id' => $setup['first']->id, 'name_bn' => 'মৌখিক', 'full_marks' => 20, 'pass_marks' => 7, 'sort_order' => 1]);
    $setup['first']->update(['full_marks' => 20]);

    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'component_id' => $component->id, 'obtained' => 21],
    ]])->assertJsonValidationErrors('entries.0.obtained');

    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'component_id' => null, 'obtained' => 10],
    ]])->assertJsonValidationErrors('entries.0.component_id');
});

it('does not trust ids from the client: a student or subject of another class is refused', function () {
    $p = placement();
    $setup = gradedExam($p);
    $other = placement();
    $stranger = enrolled($other);
    $foreignSubject = gradedExam($other)['first'];

    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $stranger->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => 50],
    ]])->assertJsonValidationErrors('entries.0.student_id');

    $student = enrolled($p);
    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $foreignSubject->id, 'obtained' => 50],
    ]])->assertJsonValidationErrors('entries.0.student_id');

    expect(Mark::count())->toBe(0);
});

it('stores an absent flag without a mark', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);

    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => 40, 'is_absent' => true],
    ]])->assertOk();

    expect(Mark::first()->is_absent)->toBeTrue()->and(Mark::first()->obtained)->toBeNull();
});

it('needs the enter_marks permission', function () {
    $p = placement();
    $setup = gradedExam($p);

    $this->actingAs(userWithRole('accountant'))->getJson(route('results.api.marks.show', [$setup['exam'], $p['class']]))->assertForbidden();
    $this->actingAs(userWithRole('accountant'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => []])->assertForbidden();
});

it('locks marks while the result is published', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);
    $setup['exam']->forceFill(['result_published' => true])->save();

    $this->actingAs(userWithRole('admin'))->putJson(route('results.api.marks.update', [$setup['exam'], $p['class']]), ['entries' => [
        ['student_id' => $student->id, 'exam_subject_id' => $setup['first']->id, 'obtained' => 50],
    ]])->assertUnprocessable();
});
