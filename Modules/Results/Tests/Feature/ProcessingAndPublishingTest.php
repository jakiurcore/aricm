<?php

use Modules\Academic\Models\Section;
use Modules\Results\Models\ExamResult;
use Modules\Results\Services\ResultProcessor;

function processed(array $p, array $setup): void
{
    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);
}

it('processes marks into results with grades and GPA', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p, ['roll' => 1]);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 85, $setup['second']->id => 72]);

    processed($p, $setup);

    $result = ExamResult::first();
    expect($result->gpa)->toBe(4.5)->and($result->grade)->toBe('A')->and($result->is_passed)->toBeTrue()
        ->and($result->total_marks)->toBe(157.0)->and($result->full_marks)->toBe(200.0)->and($result->position_in_class)->toBe(1)
        ->and($result->subject_results)->toHaveCount(2);
});

it('fails a student with a failed compulsory subject and lists absent as failed', function () {
    $p = placement();
    $setup = gradedExam($p);
    $weak = enrolled($p, ['roll' => 1]);
    $absent = enrolled($p, ['roll' => 2]);
    enterMarks($setup['exam'], $p['class']->id, $weak, [$setup['first']->id => 90, $setup['second']->id => 20]);
    enterMarks($setup['exam'], $p['class']->id, $absent, [$setup['first']->id => 90, $setup['second']->id => 'absent']);

    processed($p, $setup);

    expect(ExamResult::where('student_id', $weak->id)->first())->is_passed->toBeFalse()->gpa->toBe(0.0)->grade->toBe('F')->failed_subjects_count->toBe(1)
        ->and(ExamResult::where('student_id', $absent->id)->first()->is_passed)->toBeFalse();
});

it('flags a student with missing marks as incomplete', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 90]);

    $summary = app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);

    expect($summary['incomplete'])->toBe(1)->and(ExamResult::first()->is_incomplete)->toBeTrue();
});

it('ranks by passed, GPA then total and shares a position on a tie', function () {
    $p = placement();
    $setup = gradedExam($p);
    $exam = $setup['exam'];
    $mark = fn (int $roll, int $a, int $b) => enterMarks($exam, $p['class']->id, enrolled($p, ['roll' => $roll, 'name_bn' => "ছাত্র {$roll}"]), [$setup['first']->id => $a, $setup['second']->id => $b]);

    $mark(1, 90, 90);   // 180, GPA 5
    $mark(2, 90, 90);   // tie with roll 1
    $mark(3, 85, 80);   // 165, GPA 5
    $mark(4, 75, 75);   // GPA 4
    $mark(5, 99, 10);   // fails despite a high total

    processed($p, $setup);

    $positions = ExamResult::orderBy('roll')->pluck('position_in_class', 'roll')->all();

    expect($positions)->toBe([1 => 1, 2 => 1, 3 => 3, 4 => 4, 5 => 5]);
});

it('ranks within each section too', function () {
    $p = placement();
    $setup = gradedExam($p);
    $second = Section::factory()->create(['class_id' => $p['class']->id]);
    $a = enrolled($p, ['roll' => 1]);
    $b = enrolled($p, ['roll' => 1, 'section_id' => $second->id]);
    enterMarks($setup['exam'], $p['class']->id, $a, [$setup['first']->id => 90, $setup['second']->id => 90]);
    enterMarks($setup['exam'], $p['class']->id, $b, [$setup['first']->id => 60, $setup['second']->id => 60]);

    processed($p, $setup);

    expect(ExamResult::where('student_id', $a->id)->first())->position_in_class->toBe(1)->position_in_section->toBe(1)
        ->and(ExamResult::where('student_id', $b->id)->first())->position_in_class->toBe(2)->position_in_section->toBe(1);
});

it('is repeatable: processing again rewrites rather than duplicates', function () {
    $p = placement();
    $setup = gradedExam($p);
    $student = enrolled($p);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 90, $setup['second']->id => 90]);

    processed($p, $setup);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['second']->id => 50]);
    processed($p, $setup);

    expect(ExamResult::count())->toBe(1)->and(ExamResult::first()->gpa)->toBe(4.0);
});

it('processes through the API and exposes the preview', function () {
    $p = placement();
    $setup = gradedExam($p);
    enterMarks($setup['exam'], $p['class']->id, enrolled($p, ['roll' => 1]), [$setup['first']->id => 90, $setup['second']->id => 90]);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('results.api.process', $setup['exam']))->assertOk()->assertJsonPath('data.0.students', 1)->assertJsonPath('data.0.passed', 1);
    $this->actingAs($admin)->getJson(route('results.api.preview', [$setup['exam'], $p['class']]))->assertOk()->assertJsonPath('data.0.gpa', 5)->assertJsonPath('published', false);
    $this->actingAs(userWithRole('accountant'))->postJson(route('results.api.process', $setup['exam']))->assertForbidden();
});

it('refuses to publish before every class is processed, then publishes and unpublishes', function () {
    $p = placement();
    $setup = gradedExam($p);
    enterMarks($setup['exam'], $p['class']->id, enrolled($p), [$setup['first']->id => 90, $setup['second']->id => 90]);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('results.api.publish', $setup['exam']))->assertUnprocessable();

    processed($p, $setup);
    $this->actingAs($admin)->postJson(route('results.api.publish', $setup['exam']))->assertOk();
    expect($setup['exam']->fresh()->result_published)->toBeTrue()->and($setup['exam']->fresh()->result_published_at)->not->toBeNull();

    $this->actingAs($admin)->postJson(route('results.api.process', $setup['exam']))->assertUnprocessable();   // locked while published

    $this->actingAs($admin)->postJson(route('results.api.unpublish', $setup['exam']))->assertOk();
    expect($setup['exam']->fresh()->result_published)->toBeFalse();
});

it('only lets users with the publish permission publish', function () {
    $p = placement();
    $setup = gradedExam($p);

    $this->actingAs(userWithRole('accountant'))->postJson(route('results.api.publish', $setup['exam']))->assertForbidden();
});

it('lists processing progress in the overview', function () {
    $p = placement();
    $setup = gradedExam($p);
    enterMarks($setup['exam'], $p['class']->id, enrolled($p), [$setup['first']->id => 90, $setup['second']->id => 90]);
    processed($p, $setup);

    $this->actingAs(userWithRole('admin'))->getJson(route('results.api.overview'))
        ->assertOk()->assertJsonPath('data.0.classes.0.processed', 1)->assertJsonPath('data.0.published', false);
});
