<?php

use Modules\Academic\Models\Subject;
use Modules\Accounts\Models\User;
use Modules\Teachers\Models\Teacher;

it('creates a teacher with a teacher-role login', function () {
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'আব্দুল করিম', 'phone' => '01712345678', 'email' => 'karim@example.com', 'password' => 'secret-pass-1'])->assertCreated();

    $teacher = Teacher::find($response->json('data.id'));
    expect($teacher->user->hasRole('teacher'))->toBeTrue()
        ->and($teacher->user->can('marks.enter_assigned'))->toBeTrue()
        ->and($teacher->user->can('admin.access'))->toBeFalse();
});

it('creates a teacher without a login and adds one later', function () {
    $admin = userWithRole('admin');
    $id = $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'রহিম'])->assertCreated()->json('data.id');

    $this->actingAs($admin)->postJson(route('teachers.api.login', $id), ['email' => 'rahim@example.com', 'password' => 'secret-pass-1'])->assertOk()->assertJsonPath('data.login_email', 'rahim@example.com');
    $this->actingAs($admin)->postJson(route('teachers.api.login', $id), ['email' => 'again@example.com', 'password' => 'secret-pass-1'])->assertUnprocessable();
    $this->actingAs($admin)->putJson(route('teachers.api.password', $id), ['password' => 'new-secret-22'])->assertOk();

    expect(Hash::check('new-secret-22', User::where('email', 'rahim@example.com')->first()->password))->toBeTrue();
});

it('requires an email when a password is given and a unique one', function () {
    $admin = userWithRole('admin');
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'x', 'password' => 'secret-pass-1'])->assertJsonValidationErrors('email');
    $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'x', 'email' => 'taken@example.com', 'password' => 'secret-pass-1'])->assertJsonValidationErrors('email');
});

it('deactivates the login when a teacher is deleted', function () {
    $admin = userWithRole('admin');
    $teacher = Teacher::factory()->withLogin()->create();

    $this->actingAs($admin)->deleteJson(route('teachers.api.destroy', $teacher))->assertNoContent();

    expect($teacher->user->fresh()->is_active)->toBeFalse();
});

it('manages assignments through the API', function () {
    $admin = userWithRole('admin');
    $p = placement();
    $subject = Subject::factory()->create();
    $p['class']->subjects()->attach($subject->id, ['sort_order' => 1]);
    $teacher = Teacher::factory()->create();

    $id = $this->actingAs($admin)->postJson(route('teachers.api.assignments.store'), ['teacher_id' => $teacher->id, 'class_id' => $p['class']->id, 'section_id' => $p['section']->id, 'subject_id' => $subject->id])->assertCreated()->json('data.id');
    $this->actingAs($admin)->postJson(route('teachers.api.assignments.store'), ['teacher_id' => $teacher->id, 'class_id' => $p['class']->id, 'section_id' => $p['section']->id, 'is_class_teacher' => true])->assertCreated();
    $this->actingAs($admin)->getJson(route('teachers.api.assignments.index'))->assertOk()->assertJsonCount(2, 'data');
    $this->actingAs($admin)->getJson(route('teachers.api.assignments.subjects', ['class_id' => $p['class']->id]))->assertJsonPath('data.0.id', $subject->id);
    $this->actingAs($admin)->deleteJson(route('teachers.api.assignments.destroy', $id))->assertNoContent();
});

it('keeps teachers and accountants out of teacher administration', function () {
    $teacher = Teacher::factory()->withLogin()->create();
    $accountant = userWithRole('accountant');

    $this->actingAs($teacher->user)->getJson(route('teachers.api.index'))->assertForbidden();
    $this->actingAs($accountant)->getJson(route('teachers.api.index'))->assertForbidden();
    $this->actingAs($accountant)->postJson(route('teachers.api.store'), ['name_bn' => 'x'])->assertForbidden();
});
