<?php

use Modules\Accounts\Models\Role;

it('lists roles and permissions grouped by module', function () {
    $this->actingAs(userWithRole('admin'))->getJson(route('accounts.api.permissions'))
        ->assertOk()->assertJsonStructure(['data' => ['admin', 'users', 'roles', 'settings']]);
});

it('creates, updates and deletes a custom role', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->postJson(route('accounts.api.roles.store'), ['name' => 'librarian', 'permissions' => ['admin.access']])->assertCreated();

    $role = Role::findByName('librarian');
    $this->actingAs($admin)->putJson(route('accounts.api.roles.update', $role), ['permissions' => []])->assertOk();
    expect($role->fresh()->permissions)->toBeEmpty();

    $this->actingAs($admin)->deleteJson(route('accounts.api.roles.destroy', $role))->assertNoContent();
});

it('protects system roles', function () {
    $admin = userWithRole('admin');
    $teacher = Role::findByName('teacher');

    $this->actingAs($admin)->deleteJson(route('accounts.api.roles.destroy', $teacher))->assertForbidden();
    $this->actingAs($admin)->putJson(route('accounts.api.roles.update', $teacher), ['name' => 'renamed'])->assertJsonValidationErrors('name');
    $this->actingAs($admin)->putJson(route('accounts.api.roles.update', Role::findByName('super_admin')), ['permissions' => []])->assertForbidden();
});

it('validates role names', function () {
    $this->actingAs(userWithRole('admin'))->postJson(route('accounts.api.roles.store'), ['name' => 'Bad Name'])->assertJsonValidationErrors('name');
});
