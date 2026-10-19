<?php

use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;

it('promotes students keeping the old placement as history', function () {
    $from = placement();
    $registrar = app(StudentRegistrar::class);
    $students = collect([1, 2, 3])->map(fn (int $roll) => $registrar->register([
        'session_id' => $from['session']->id, 'class_id' => $from['class']->id, 'section_id' => $from['section']->id, 'roll' => $roll,
        'name_bn' => "ছাত্র {$roll}", 'father_name' => 'x', 'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-01-01', 'residency_type' => 'non_residential',
    ]));

    $nextSession = AcademicSession::factory()->create(['name' => '2027']);
    $nextClass = SchoolClass::factory()->create();
    $nextSection = Section::factory()->create(['class_id' => $nextClass->id]);

    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.promote'), [
        'student_ids' => $students->pluck('id')->all(), 'session_id' => $nextSession->id, 'class_id' => $nextClass->id, 'section_id' => $nextSection->id, 'roll_mode' => 'sequential',
    ])->assertOk()->assertJsonPath('data.promoted', 3);

    $first = Student::find($students[0]->id);
    expect($first->class_id)->toBe($nextClass->id)->and($first->roll)->toBe(1)
        ->and(Student::find($students[2]->id)->roll)->toBe(3)
        ->and($first->enrollments)->toHaveCount(2)
        ->and($first->enrollments->firstWhere('session_id', $from['session']->id)->status)->toBe('promoted');
});

it('rejects a section from another class when promoting', function () {
    $from = placement();
    $student = app(StudentRegistrar::class)->register(['session_id' => $from['session']->id, 'class_id' => $from['class']->id, 'section_id' => null, 'name_bn' => 'x', 'father_name' => 'x', 'date_of_birth' => '2015-01-01', 'gender' => 'male', 'admission_date' => '2026-01-01', 'residency_type' => 'non_residential']);

    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.promote'), [
        'student_ids' => [$student->id], 'session_id' => $from['session']->id, 'class_id' => $from['class']->id,
        'section_id' => Section::factory()->create()->id, 'roll_mode' => 'keep',
    ])->assertJsonValidationErrors('section_id');
});

it('requires the promote permission', function () {
    $this->actingAs(userWithRole('accountant'))->postJson(route('students.api.promote'), [])->assertForbidden();
});
