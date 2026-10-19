<?php

use Modules\Accounts\Models\User;
use Spatie\Activitylog\Models\Activity;

it('lists, creates and updates users', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('accounts.api.users.store'), [
        'name' => 'রহিম', 'email' => 'rahim@example.com', 'password' => 'secret123', 'roles' => ['teacher'], 'is_active' => true,
    ])->assertCreated()->assertJsonPath('data.roles.0', 'teacher');

    $created = User::where('email', 'rahim@example.com')->firstOrFail();

    $this->actingAs($admin)->putJson(route('accounts.api.users.update', $created), [
        'name' => 'রহিম উদ্দিন', 'email' => 'rahim@example.com', 'roles' => ['accountant'],
    ])->assertOk();

    expect($created->fresh()->name)->toBe('রহিম উদ্দিন')
        ->and($created->fresh()->hasRole('accountant'))->toBeTrue();

    $this->actingAs($admin)->getJson(route('accounts.api.users.index', ['search' => 'rahim@example.com']))
        ->assertOk()->assertJsonCount(1, 'data');
});

it('requires a password for new users and a unique email', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('accounts.api.users.store'), ['name' => 'X', 'email' => $admin->email])
        ->assertJsonValidationErrors(['password', 'email']);
});

it('keeps the old password when none is sent on update', function () {
    $admin = userWithRole('admin');
    $target = User::factory()->create();
    $hash = $target->password;

    $this->actingAs($admin)->putJson(route('accounts.api.users.update', $target), ['name' => 'নতুন', 'email' => $target->email])->assertOk();

    expect($target->fresh()->password)->toBe($hash);
});

it('stops an admin from granting or touching super admin', function () {
    $admin = userWithRole('admin');
    $super = userWithRole('super_admin');

    $this->actingAs($admin)->postJson(route('accounts.api.users.store'), [
        'name' => 'X', 'email' => 'x@example.com', 'password' => 'secret123', 'roles' => ['super_admin'],
    ])->assertJsonValidationErrors('roles.0');

    $this->actingAs($admin)->putJson(route('accounts.api.users.update', $super), ['name' => 'Hack', 'email' => $super->email])->assertForbidden();
    $this->actingAs($admin)->deleteJson(route('accounts.api.users.destroy', $super))->assertForbidden();
});

it('does not let admins delete themselves or deactivate themselves', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->deleteJson(route('accounts.api.users.destroy', $admin))->assertForbidden();

    $this->actingAs($admin)->putJson(route('accounts.api.users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'is_active' => false])->assertOk();
    expect($admin->fresh()->is_active)->toBeTrue();
});

it('logs user changes to the activity log', function () {
    $admin = userWithRole('admin');
    $target = User::factory()->create(['name' => 'আগের']);

    $target->update(['name' => 'পরের']);

    expect(Activity::where('log_name', 'accounts')->latest('id')->first()->attribute_changes->toArray())
        ->toHaveKey('attributes.name', 'পরের');
});
