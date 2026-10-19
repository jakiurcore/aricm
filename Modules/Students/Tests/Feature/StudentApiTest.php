<?php

use Illuminate\Http\UploadedFile;
use Modules\Academic\Models\Section;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;

function payload(array $placement, array $overrides = []): array
{
    return [
        'session_id' => $placement['session']->id, 'class_id' => $placement['class']->id, 'section_id' => $placement['section']->id,
        'roll' => 1, 'name_bn' => 'আব্দুল্লাহ', 'father_name' => 'আব্দুর রহিম', 'date_of_birth' => '2016-04-01',
        'gender' => 'male', 'admission_date' => '2026-01-10', 'residency_type' => 'non_residential',
        ...$overrides,
    ];
}

it('keeps teachers out and lets accountants only read', function () {
    $this->actingAs(userWithRole('teacher'))->getJson(route('students.api.index'))->assertForbidden();

    $accountant = userWithRole('accountant');
    $this->actingAs($accountant)->getJson(route('students.api.index'))->assertOk();
    $this->actingAs($accountant)->postJson(route('students.api.store'), payload(placement()))->assertForbidden();
});

it('creates a student, generating the id and the default password', function () {
    $placement = placement();

    $response = $this->actingAs(userWithRole('admin'))->postJson(route('students.api.store'), payload($placement))->assertCreated();

    $response->assertJsonPath('data.student_id', 'ARICM-2026-0001')->assertJsonPath('data.class_name', $placement['class']->name_bn);
    expect(Student::count())->toBe(1);
});

it('ignores a student id or password sent by the client', function () {
    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.store'), payload(placement(), ['student_id' => 'HACK-1', 'password' => 'x', 'must_change_password' => false]))->assertCreated();

    $student = Student::first();
    expect($student->student_id)->toBe('ARICM-2026-0001')->and($student->must_change_password)->toBeTrue();
});

it('rejects a section that belongs to another class', function () {
    $placement = placement();
    $foreign = Section::factory()->create();

    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.store'), payload($placement, ['section_id' => $foreign->id]))
        ->assertJsonValidationErrors('section_id');
});

it('rejects a duplicate roll in the same class and section but allows it elsewhere', function () {
    $placement = placement();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('students.api.store'), payload($placement))->assertCreated();
    $this->actingAs($admin)->postJson(route('students.api.store'), payload($placement))->assertJsonValidationErrors('roll');

    $this->actingAs($admin)->postJson(route('students.api.store'), payload($placement, ['section_id' => null]))->assertCreated();
});

it('validates required fields, enums and a future birth date', function () {
    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.store'), [
        'name_bn' => '', 'gender' => 'x', 'date_of_birth' => now()->addDay()->toDateString(), 'residency_type' => 'x', 'blood_group' => 'Z+',
    ])->assertJsonValidationErrors(['name_bn', 'gender', 'date_of_birth', 'residency_type', 'blood_group', 'session_id', 'class_id', 'father_name']);
});

it('uploads a photo and replaces it on update', function () {
    Storage::fake('public');
    $placement = placement();
    $admin = userWithRole('admin');

    $created = $this->actingAs($admin)->postJson(route('students.api.store'), payload($placement, ['photo' => tinyPng()]))->assertCreated()->json('data');
    $first = Student::first()->photo;
    Storage::disk('public')->assertExists($first);
    expect($created['photo_url'])->not->toBeNull();

    $this->actingAs($admin)->post(route('students.api.update', Student::first()), [...payload($placement, ['roll' => 2, 'status' => 'active', 'photo' => tinyPng('new.png')]), '_method' => 'PUT'], ['Accept' => 'application/json'])->assertOk();

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists(Student::first()->photo);
});

it('rejects a non-image photo', function () {
    $this->actingAs(userWithRole('admin'))->postJson(route('students.api.store'), payload(placement(), ['photo' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')]))
        ->assertJsonValidationErrors('photo');
});

it('filters and searches the list', function () {
    $placement = placement();
    $registrar = app(StudentRegistrar::class);
    $registrar->register(payload($placement, ['roll' => 1, 'name_bn' => 'রাকিব']));
    $registrar->register(payload($placement, ['roll' => 2, 'name_bn' => 'সাকিব']));
    $other = placement();
    $registrar->register(payload($other, ['name_bn' => 'অন্য', 'roll' => 1]));

    $admin = userWithRole('admin');

    $this->actingAs($admin)->getJson(route('students.api.index', ['class_id' => $placement['class']->id]))->assertJsonCount(2, 'data');
    $this->actingAs($admin)->getJson(route('students.api.index', ['search' => 'সাকিব']))->assertJsonCount(1, 'data');
    $this->actingAs($admin)->getJson(route('students.api.index', ['search' => 'ARICM-2026-0003']))->assertJsonCount(1, 'data');
});

it('soft deletes and resets a password', function () {
    $student = app(StudentRegistrar::class)->register(payload(placement()));
    $student->forceFill(['password' => 'changed', 'must_change_password' => false])->save();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('students.api.reset-password', $student))->assertOk();
    expect($student->fresh()->must_change_password)->toBeTrue()->and(Hash::check('01042016', $student->fresh()->password))->toBeTrue();

    $this->actingAs($admin)->deleteJson(route('students.api.destroy', $student))->assertNoContent();
    expect(Student::count())->toBe(0);
});

it('shows a profile with enrollment history', function () {
    $student = app(StudentRegistrar::class)->register(payload(placement()));

    $this->actingAs(userWithRole('admin'))->getJson(route('students.api.show', $student))
        ->assertOk()->assertJsonCount(1, 'enrollments')->assertJsonPath('data.student_id', $student->student_id);
});

it('blocks deleting a class that has students', function () {
    $placement = placement();
    app(StudentRegistrar::class)->register(payload($placement));

    $this->actingAs(userWithRole('admin'))->deleteJson(route('academic.api.classes.destroy', $placement['class']))
        ->assertUnprocessable()->assertJsonPath('message', __('academic::messages.in_use'));
});
