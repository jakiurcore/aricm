<?php

use Illuminate\Support\Facades\Storage;
use Modules\Academic\Models\Subject;
use Modules\Accounts\Models\User;
use Modules\Core\Services\Sms\SmsManager;
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

it('stores the profile fields of the admin form and maps the status to the active flag', function () {
    $admin = userWithRole('admin');

    $id = $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'করিম', 'email' => 'karim.contact@example.com', 'joined_on' => '2020-01-15', 'status' => 'leave'])->assertCreated()
        ->assertJsonPath('data.status', 'leave')->assertJsonPath('data.joined_on', '2020-01-15')->assertJsonPath('data.email', 'karim.contact@example.com')->json('data.id');

    expect(Teacher::find($id)->is_active)->toBeTrue();

    $this->actingAs($admin)->putJson(route('teachers.api.update', $id), ['name_bn' => 'করিম', 'status' => 'inactive'])->assertOk()->assertJsonPath('data.is_active', false);
    $this->actingAs($admin)->putJson(route('teachers.api.update', $id), ['name_bn' => 'করিম', 'is_active' => true])->assertOk()->assertJsonPath('data.status', 'active');
});

it('uploads a teacher photo', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->post(route('teachers.api.store'), ['name_bn' => 'ছবিওয়ালা', 'photo' => tinyPng('face.png')], ['Accept' => 'application/json'])->assertCreated();

    expect($response->json('data.photo_url'))->not->toBeNull();
});

it('creates a login with a temporary password and sends it by SMS', function () {
    $sent = [];
    $this->mock(SmsManager::class, function ($mock) use (&$sent): void {
        $mock->shouldReceive('send')->once()->andReturnUsing(function (string $to, string $message) use (&$sent): bool {
            $sent = [$to, $message];

            return true;
        });
    });
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->postJson(route('teachers.api.store'), ['name_bn' => 'নতুন', 'phone' => '01712345678', 'email' => 'temp@example.com', 'create_login' => true])->assertCreated();

    $temporary = $response->json('temporary_password');
    expect($temporary)->toHaveLength(8)
        ->and(Hash::check($temporary, User::where('email', 'temp@example.com')->first()->password))->toBeTrue()
        ->and($sent[0])->toBe('01712345678')
        ->and($sent[1])->toContain($temporary);
});

it('resets a password to a temporary one when none is chosen', function () {
    $admin = userWithRole('admin');
    $teacher = Teacher::factory()->withLogin()->create();

    $response = $this->actingAs($admin)->putJson(route('teachers.api.password', $teacher), [])->assertOk();

    expect(Hash::check($response->json('data.temporary_password'), $teacher->user->fresh()->password))->toBeTrue();
});

it('needs an email before a login can be created', function () {
    $admin = userWithRole('admin');
    $teacher = Teacher::factory()->create();

    $this->actingAs($admin)->postJson(route('teachers.api.login', $teacher), [])->assertUnprocessable();
});

it('draws the assignment grid from the class subjects and sets one teacher per cell', function () {
    $admin = userWithRole('admin');
    $p = placement();
    $subject = Subject::factory()->create();
    $p['class']->subjects()->attach($subject->id, ['sort_order' => 1]);
    [$first, $second] = [Teacher::factory()->create(), Teacher::factory()->create()];
    $cell = ['session_id' => $p['session']->id, 'class_id' => $p['class']->id, 'section_id' => $p['section']->id, 'subject_id' => $subject->id];

    $this->actingAs($admin)->getJson(route('teachers.api.assignments.grid'))->assertOk()
        ->assertJsonPath('data.rows.0.section_id', $p['section']->id)->assertJsonPath('data.rows.0.subject_ids.0', $subject->id)->assertJsonFragment(['id' => $subject->id]);

    $this->actingAs($admin)->putJson(route('teachers.api.assignments.cell'), [...$cell, 'teacher_id' => $first->id])->assertOk();
    $this->actingAs($admin)->putJson(route('teachers.api.assignments.cell'), [...$cell, 'teacher_id' => $second->id])->assertOk();
    $this->actingAs($admin)->getJson(route('teachers.api.assignments.index', ['session_id' => $p['session']->id]))->assertJsonCount(1, 'data')->assertJsonPath('data.0.teacher_id', $second->id);

    $this->actingAs($admin)->putJson(route('teachers.api.assignments.cell'), [...$cell, 'is_class_teacher' => true, 'teacher_id' => $first->id])->assertOk();
    $this->actingAs($admin)->putJson(route('teachers.api.assignments.cell'), [...$cell, 'teacher_id' => null])->assertOk();
    $this->actingAs($admin)->putJson(route('teachers.api.assignments.cell'), [...$cell, 'is_class_teacher' => true, 'teacher_id' => null])->assertOk();
    $this->actingAs($admin)->getJson(route('teachers.api.assignments.index', ['session_id' => $p['session']->id]))->assertJsonCount(0, 'data');

    $this->actingAs(userWithRole('accountant'))->putJson(route('teachers.api.assignments.cell'), $cell)->assertForbidden();
});
