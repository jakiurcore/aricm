<?php

it('redirects guests from the admin to login', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('shows a Bangla 403 page to a teacher opening the admin', function () {
    $this->actingAs(userWithRole('teacher'))
        ->get('/admin')
        ->assertForbidden()
        ->assertSee(__('core::messages.errors.403_title'));
});

it('lets admins and accountants into the admin', function (string $role) {
    $this->actingAs(userWithRole($role))->get('/admin')->assertOk();
})->with(['admin', 'accountant', 'super_admin']);

it('keeps the admin API away from teachers', function () {
    $this->actingAs(userWithRole('teacher'))->getJson(route('accounts.api.users.index'))->assertForbidden();
});

it('keeps user management away from accountants', function () {
    $this->actingAs(userWithRole('accountant'))->getJson(route('accounts.api.users.index'))->assertForbidden();
});

it('lets a super admin pass checks for permissions nobody declared', function () {
    expect(userWithRole('super_admin')->can('something.undeclared'))->toBeTrue()
        ->and(userWithRole('admin')->can('something.undeclared'))->toBeFalse();
});

it('grants admins every declared permission', function () {
    $admin = userWithRole('admin');

    expect($admin->can('users.manage'))->toBeTrue()
        ->and($admin->can('settings.manage'))->toBeTrue();
});

it('keeps an inactive admin out of the admin', function () {
    $user = userWithRole('admin');
    $user->update(['is_active' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
