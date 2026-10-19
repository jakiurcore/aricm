<?php

use Modules\Results\Models\ExamResult;

it('marks each listed student paid or due from the fee provider', function () {
    $p = placement();
    feeSetup($p);
    $owing = enrolled($p, ['roll' => 1, 'name_bn' => 'বকেয়াদার']);
    invoicesFor($owing, ['2026-03-01']);
    $clear = enrolled($p, ['roll' => 2, 'name_bn' => 'পরিষ্কার']);

    $rows = collect($this->actingAs(userWithRole('admin'))->getJson(route('students.api.index'))->assertOk()->json('data'))->pluck('fee_badge', 'id');

    expect($rows[$owing->id])->toBe('due')->and($rows[$clear->id])->toBe('paid');
});

it('finds a student by roll, pages by per_page and carries the latest published GPA on request', function () {
    $p = placement();
    $first = enrolled($p, ['roll' => 7, 'name_bn' => 'প্রথম']);
    enrolled($p, ['roll' => 8, 'name_bn' => 'দ্বিতীয়']);
    $graded = gradedExam($p);
    $graded['exam']->forceFill(['result_published' => true])->save();
    ExamResult::create(['exam_id' => $graded['exam']->id, 'student_id' => $first->id, 'class_id' => $p['class']->id, 'section_id' => $p['section']->id, 'roll' => 7, 'total_marks' => 180, 'full_marks' => 200, 'percentage' => 90, 'gpa' => 4.5, 'grade' => 'A', 'is_passed' => true, 'is_incomplete' => false, 'subject_results' => []]);

    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('students.api.index', ['search' => '7']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $first->id);
    $this->actingAs($admin)->getJson(route('students.api.index', ['per_page' => 1]))->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
    $this->actingAs($admin)->getJson(route('students.api.index', ['with_gpa' => 1]))->assertJsonPath('data.0.latest_gpa', 4.5)->assertJsonPath('data.1.latest_gpa', null);
    $this->actingAs($admin)->getJson(route('students.api.index'))->assertJsonMissingPath('data.0.latest_gpa');
});

it('stores the birth registration number', function () {
    $admin = userWithRole('admin');
    $p = placement();

    $this->actingAs($admin)->postJson(route('students.api.store'), ['session_id' => $p['session']->id, 'class_id' => $p['class']->id, 'roll' => 1, 'name_bn' => 'আব্দুল্লাহ', 'father_name' => 'রহিম', 'date_of_birth' => '2016-04-01', 'gender' => 'male', 'admission_date' => '2026-01-10', 'residency_type' => 'non_residential', 'birth_registration_no' => '20160123456789'])->assertCreated()->assertJsonPath('data.birth_registration_no', '20160123456789');
});
