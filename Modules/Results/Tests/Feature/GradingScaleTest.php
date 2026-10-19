<?php

use Modules\Results\Database\Seeders\GradingScaleSeeder;
use Modules\Results\Models\ExamResult;
use Modules\Results\Models\GradingScale;
use Modules\Results\Services\ResultProcessor;

function bands(): array
{
    return [
        ['min_percent' => 50, 'max_percent' => 100, 'grade' => 'P', 'grade_point' => 5],
        ['min_percent' => 0, 'max_percent' => 49.99, 'grade' => 'F', 'grade_point' => 0],
    ];
}

it('seeds the Bangladesh default scale', function () {
    (new GradingScaleSeeder)->run();

    $scale = GradingScale::default();

    expect($scale->items)->toHaveCount(7)->and($scale->items->first()->grade)->toBe('A+')->and($scale->is_default)->toBeTrue();
});

it('creates a scale, keeps one default and refuses to delete the default', function () {
    (new GradingScaleSeeder)->run();
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->postJson(route('results.api.scales.store'), ['name' => 'সহজ', 'is_default' => true, 'items' => bands()])->assertCreated()->json('data.id');

    expect(GradingScale::where('is_default', true)->count())->toBe(1)->and(GradingScale::default()->id)->toBe($id);
    $this->actingAs($admin)->deleteJson(route('results.api.scales.destroy', $id))->assertUnprocessable();
    $this->actingAs($admin)->deleteJson(route('results.api.scales.destroy', GradingScale::where('is_default', false)->first()))->assertNoContent();
});

it('requires a band from 0% and distinct starts', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('results.api.scales.store'), ['name' => 'x', 'items' => [
        ['min_percent' => 40, 'max_percent' => 100, 'grade' => 'P', 'grade_point' => 5], ['min_percent' => 20, 'max_percent' => 39, 'grade' => 'F', 'grade_point' => 0],
    ]])->assertJsonValidationErrors('items');

    $this->actingAs($admin)->postJson(route('results.api.scales.store'), ['name' => 'x', 'items' => [
        ['min_percent' => 0, 'max_percent' => 100, 'grade' => 'P', 'grade_point' => 5], ['min_percent' => 0, 'max_percent' => 39, 'grade' => 'F', 'grade_point' => 0],
    ]])->assertJsonValidationErrors('items');
});

it('uses the exam scale when one is set', function () {
    $p = placement();
    $setup = gradedExam($p);
    $scale = GradingScale::create(['name' => 'কঠিন', 'is_default' => false]);
    foreach ([[95, 100, 'A+', 5], [0, 94.99, 'F', 0]] as [$min, $max, $grade, $point]) {
        $scale->items()->create(['min_percent' => $min, 'max_percent' => $max, 'grade' => $grade, 'grade_point' => $point]);
    }
    $setup['exam']->update(['grading_scale_id' => $scale->id]);
    enterMarks($setup['exam'], $p['class']->id, enrolled($p), [$setup['first']->id => 90, $setup['second']->id => 90]);

    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);

    expect(ExamResult::first()->subject_results[0]['grade'])->toBe('F');
});
