<?php

use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Subject;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamSubject;

it('keeps exams away from teachers and lets accountants only read', function () {
    $this->actingAs(userWithRole('teacher'))->getJson(route('exams.api.index'))->assertForbidden();

    $accountant = userWithRole('accountant');
    $this->actingAs($accountant)->getJson(route('exams.api.index'))->assertOk();
    $this->actingAs($accountant)->postJson(route('exams.api.store'), [])->assertForbidden();
});

it('creates an exam with a free-text name and its classes', function () {
    $p = placement();

    $this->actingAs(userWithRole('admin'))->postJson(route('exams.api.store'), [
        'name_bn' => 'মডেল টেস্ট', 'session_id' => $p['session']->id, 'status' => 'draft',
        'start_date' => '2026-06-01', 'end_date' => '2026-06-10', 'class_ids' => [$p['class']->id],
    ])->assertCreated()->assertJsonPath('data.name_bn', 'মডেল টেস্ট')->assertJsonPath('data.class_ids.0', $p['class']->id);
});

it('validates dates, status and classes', function () {
    $this->actingAs(userWithRole('admin'))->postJson(route('exams.api.store'), [
        'name_bn' => '', 'status' => 'nope', 'start_date' => '2026-06-10', 'end_date' => '2026-06-01', 'class_ids' => [999],
    ])->assertJsonValidationErrors(['name_bn', 'session_id', 'status', 'end_date', 'class_ids.0']);
});

it('configures subjects per class with components that sum to the subject total', function () {
    $p = placement();
    $exam = examFor($p, withRoutine: false);
    ExamSubject::query()->delete();
    $subject = Subject::factory()->create();
    $p['class']->subjects()->attach($subject->id);

    $response = $this->actingAs(userWithRole('admin'))->putJson(route('exams.api.setup.subjects', [$exam, $p['class']]), ['subjects' => [[
        'subject_id' => $subject->id, 'pass_marks' => 33, 'include_in_gpa' => true,
        'components' => [['name_bn' => 'লিখিত', 'full_marks' => 70, 'pass_marks' => 23], ['name_bn' => 'মৌখিক', 'full_marks' => 30, 'pass_marks' => 10]],
    ]]])->assertOk();

    $response->assertJsonPath('subjects.0.full_marks', 100)->assertJsonCount(2, 'subjects.0.components');
});

it('rejects pass marks above full marks and subjects not mapped to the class', function () {
    $p = placement();
    $exam = examFor($p);
    $stranger = Subject::factory()->create();

    $this->actingAs(userWithRole('admin'))->putJson(route('exams.api.setup.subjects', [$exam, $p['class']]), ['subjects' => [
        ['subject_id' => $stranger->id, 'full_marks' => 100, 'pass_marks' => 33],
    ]])->assertJsonValidationErrors('subjects.0.subject_id');

    $mapped = $p['class']->subjects()->first();
    $this->actingAs(userWithRole('admin'))->putJson(route('exams.api.setup.subjects', [$exam, $p['class']]), ['subjects' => [
        ['subject_id' => $mapped->id, 'full_marks' => 50, 'pass_marks' => 60],
    ]])->assertJsonValidationErrors('subjects.0.pass_marks');
});

it('updates components in place so ids stay stable', function () {
    $p = placement();
    $exam = examFor($p);
    $subject = $p['class']->subjects()->first();
    $admin = userWithRole('admin');
    $body = fn (array $components) => ['subjects' => [['subject_id' => $subject->id, 'pass_marks' => 33, 'components' => $components]]];

    $first = $this->actingAs($admin)->putJson(route('exams.api.setup.subjects', [$exam, $p['class']]), $body([['name_bn' => 'লিখিত', 'full_marks' => 100]]))->json('subjects.0.components.0.id');
    $second = $this->actingAs($admin)->putJson(route('exams.api.setup.subjects', [$exam, $p['class']]), $body([['id' => $first, 'name_bn' => 'লিখিত (সংশোধিত)', 'full_marks' => 100]]))->json('subjects.0.components.0');

    expect($second['id'])->toBe($first)->and($second['name_bn'])->toBe('লিখিত (সংশোধিত)');
});

it('seeds subjects from the class mapping and copies from another exam', function () {
    $p = placement();
    $source = examFor($p);
    $target = Exam::factory()->create(['session_id' => $p['session']->id]);
    $target->classes()->attach($p['class']->id);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('exams.api.setup.seed', [$target, $p['class']]))->assertOk()->assertJsonCount(1, 'subjects');
    ExamSubject::where('exam_id', $target->id)->delete();

    $this->actingAs($admin)->postJson(route('exams.api.setup.copy', [$target, $p['class'], $source]))->assertOk()->assertJsonCount(1, 'subjects');
    $this->actingAs($admin)->postJson(route('exams.api.setup.copy', [$target, $p['class'], $target]))->assertUnprocessable();
});

it('keeps a class that is not part of the exam out of the setup', function () {
    $p = placement();
    $exam = examFor($p);
    $other = SchoolClass::factory()->create();

    $this->actingAs(userWithRole('admin'))->getJson(route('exams.api.setup.show', [$exam, $other]))->assertNotFound();
});

it('saves a routine inside the exam dates and rejects other subjects', function () {
    $p = placement();
    $exam = examFor($p, withRoutine: false);
    $subject = $p['class']->subjects()->first();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->putJson(route('exams.api.setup.routine', [$exam, $p['class']]), ['routine' => [
        ['subject_id' => $subject->id, 'exam_date' => '2026-06-03', 'start_time' => '10:00', 'end_time' => '12:00', 'room' => '১০১'],
    ]])->assertOk()->assertJsonPath('routine.0.room', '১০১');

    $this->actingAs($admin)->putJson(route('exams.api.setup.routine', [$exam, $p['class']]), ['routine' => [
        ['subject_id' => $subject->id, 'exam_date' => '2026-07-01', 'start_time' => '12:00', 'end_time' => '10:00'],
    ]])->assertJsonValidationErrors(['routine.0.exam_date', 'routine.0.end_time']);

    $this->actingAs($admin)->putJson(route('exams.api.setup.routine', [$exam, $p['class']]), ['routine' => [
        ['subject_id' => Subject::factory()->create()->id, 'exam_date' => '2026-06-03', 'start_time' => '10:00', 'end_time' => '12:00'],
    ]])->assertJsonValidationErrors('routine.0.subject_id');
});

it('removing a class from an exam clears its setup', function () {
    $p = placement();
    $exam = examFor($p);

    $this->actingAs(userWithRole('admin'))->putJson(route('exams.api.update', $exam), [
        'name_bn' => $exam->name_bn, 'session_id' => $exam->session_id, 'status' => 'draft', 'class_ids' => [],
    ])->assertOk();

    expect(ExamSubject::where('exam_id', $exam->id)->count())->toBe(0);
});

it('tells the exam list how many distinct subjects and whether the result is out', function () {
    $p = placement();
    $exam = examFor($p, withRoutine: false);

    $this->actingAs(userWithRole('admin'))->getJson(route('exams.api.index'))
        ->assertOk()
        ->assertJsonPath('data.0.subjects_count', $exam->subjects()->pluck('subject_id')->unique()->count())
        ->assertJsonPath('data.0.result_published', false);
});
