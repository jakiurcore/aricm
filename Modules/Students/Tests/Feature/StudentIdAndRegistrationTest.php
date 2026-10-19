<?php

use Illuminate\Support\Facades\DB;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentIdGenerator;
use Modules\Students\Services\StudentPasswords;
use Modules\Students\Services\StudentRegistrar;

function studentData(array $placement, array $overrides = []): array
{
    return [
        'session_id' => $placement['session']->id,
        'class_id' => $placement['class']->id,
        'section_id' => $placement['section']->id,
        'name_bn' => 'রহিম', 'father_name' => 'করিম', 'date_of_birth' => '2015-03-07', 'gender' => 'male',
        'admission_date' => '2026-01-10', 'residency_type' => 'non_residential',
        ...$overrides,
    ];
}

it('generates sequential ids per admission year', function () {
    $placement = placement();
    $registrar = app(StudentRegistrar::class);

    $first = $registrar->register(studentData($placement));
    $second = $registrar->register(studentData($placement));
    $other = $registrar->register(studentData($placement, ['admission_date' => '2027-02-01']));

    expect($first->student_id)->toBe('ARICM-2026-0001')
        ->and($second->student_id)->toBe('ARICM-2026-0002')
        ->and($other->student_id)->toBe('ARICM-2027-0001');
});

it('never reuses the id of a deleted student', function () {
    $placement = placement();
    $registrar = app(StudentRegistrar::class);

    $registrar->register(studentData($placement))->delete();

    expect($registrar->register(studentData($placement))->student_id)->toBe('ARICM-2026-0002');
});

it('sets the date of birth as the first password and forces a change', function () {
    $student = app(StudentRegistrar::class)->register(studentData(placement()));

    expect(Hash::check('07032015', $student->password))->toBeTrue()
        ->and($student->must_change_password)->toBeTrue()
        ->and(app(StudentPasswords::class)->defaultFor($student))->toBe('07032015');
});

it('writes an enrollment row and keeps it in step with updates', function () {
    $placement = placement();
    $student = app(StudentRegistrar::class)->register(studentData($placement, ['roll' => 5]));

    expect($student->enrollments)->toHaveCount(1)
        ->and($student->enrollments->first()->roll)->toBe(5);

    app(StudentRegistrar::class)->update($student, ['roll' => 9]);

    expect($student->fresh()->enrollments->first()->roll)->toBe(9);
});

it('formats the generator output with four digits', function () {
    expect(app(StudentIdGenerator::class)->next(2030))->toBe('ARICM-2030-0001');
});

it('is a soft-deleting model', function () {
    $student = app(StudentRegistrar::class)->register(studentData(placement()));
    $student->delete();

    expect(Student::count())->toBe(0)->and(Student::withTrashed()->count())->toBe(1);
    expect(DB::table('students')->count())->toBe(1);
});
