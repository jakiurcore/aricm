<?php

use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentRegistrar;

function makeStudent(array $overrides = []): Student
{
    $placement = placement();

    return app(StudentRegistrar::class)->register([
        'session_id' => $placement['session']->id, 'class_id' => $placement['class']->id, 'section_id' => null,
        'name_bn' => 'রহিম', 'father_name' => 'করিম', 'date_of_birth' => '2015-03-07', 'gender' => 'male',
        'admission_date' => '2026-01-10', 'residency_type' => 'non_residential', ...$overrides,
    ]);
}

it('signs a student in with the id and the date of birth password', function () {
    $student = makeStudent();

    $this->post(route('student.login.store'), ['student_id' => strtolower($student->student_id), 'password' => '07032015'])
        ->assertRedirect('/portal');

    $this->assertAuthenticatedAs($student, 'student');
});

it('rejects a wrong password in Bangla', function () {
    $student = makeStudent();

    $this->post(route('student.login.store'), ['student_id' => $student->student_id, 'password' => 'wrong'])
        ->assertSessionHasErrors(['student_id' => 'আইডি বা পাসওয়ার্ড সঠিক নয়।']);

    $this->assertGuest('student');
});

it('does not sign in inactive or deleted students', function () {
    $inactive = makeStudent();
    $inactive->update(['status' => StudentStatus::Inactive]);

    $this->post(route('student.login.store'), ['student_id' => $inactive->student_id, 'password' => '07032015'])->assertSessionHasErrors('student_id');
    $this->assertGuest('student');
});

it('locks out after repeated failures', function () {
    $student = makeStudent();

    foreach (range(1, 5) as $ignored) {
        $this->post(route('student.login.store'), ['student_id' => $student->student_id, 'password' => 'nope']);
    }

    $this->post(route('student.login.store'), ['student_id' => $student->student_id, 'password' => '07032015'])->assertSessionHasErrors('student_id');
    $this->assertGuest('student');
});

it('forces a first-login password change before anything else', function () {
    $student = makeStudent();

    $this->actingAs($student, 'student')->get(route('student.password.edit'))->assertOk()->assertSee(__('accounts::messages.student.must_change'));

    $this->actingAs($student, 'student')->put(route('student.password.update'), [
        'current_password' => '07032015', 'password' => 'newsecret', 'password_confirmation' => 'newsecret',
    ])->assertRedirect('/portal');

    expect($student->fresh()->must_change_password)->toBeFalse();
    expect(Hash::check('newsecret', $student->fresh()->password))->toBeTrue();
});

it('validates the new password', function () {
    $student = makeStudent();

    $this->actingAs($student, 'student')->put(route('student.password.update'), [
        'current_password' => 'wrong', 'password' => '123', 'password_confirmation' => '999',
    ])->assertSessionHasErrors(['current_password', 'password']);

    $this->actingAs($student, 'student')->put(route('student.password.update'), [
        'current_password' => '07032015', 'password' => '07032015', 'password_confirmation' => '07032015',
    ])->assertSessionHasErrors('password');
});

it('keeps students out of the admin and admin users out of the student guard', function () {
    $student = makeStudent();
    $this->post(route('student.login.store'), ['student_id' => $student->student_id, 'password' => '07032015']);

    $this->get('/admin')->assertRedirect(route('login'));
});

it('keeps admin users out of the student area', function () {
    $this->actingAs(userWithRole('admin'))->get(route('student.password.edit'))->assertRedirect(route('student.login'));
});

it('signs a student out', function () {
    $student = makeStudent();
    $student->forceFill(['must_change_password' => false])->save();

    $this->actingAs($student, 'student')->post(route('student.logout'))->assertRedirect(route('student.login'));

    $this->assertGuest('student');
});
