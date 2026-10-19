<?php

use Modules\Accounts\Models\User;

it('creates a super admin who can sign in', function () {
    $this->artisan('accounts:create-admin', ['email' => 'boss@example.com', '--name' => 'প্রধান', '--password' => 'long-secret-1'])->assertSuccessful();

    $user = User::where('email', 'boss@example.com')->first();
    expect($user->isSuperAdmin())->toBeTrue()->and($user->can('admin.access'))->toBeTrue()->and($user->is_active)->toBeTrue();

    $this->post(route('login.store'), ['email' => 'boss@example.com', 'password' => 'long-secret-1'])->assertRedirect('/admin');
});

it('creates a plain admin when asked', function () {
    $this->artisan('accounts:create-admin', ['email' => 'office@example.com', '--password' => 'long-secret-1', '--role' => 'admin'])->assertSuccessful();

    expect(User::where('email', 'office@example.com')->first()->hasRole('admin'))->toBeTrue();
});

it('resets the password of an existing login instead of duplicating it', function () {
    $this->artisan('accounts:create-admin', ['email' => 'boss@example.com', '--password' => 'long-secret-1'])->assertSuccessful();
    $this->artisan('accounts:create-admin', ['email' => 'boss@example.com', '--password' => 'another-secret-2'])->assertSuccessful();

    expect(User::where('email', 'boss@example.com')->count())->toBe(1)
        ->and(Hash::check('another-secret-2', User::where('email', 'boss@example.com')->first()->password))->toBeTrue();
});

it('refuses a weak password, a bad email and an unknown role', function () {
    $this->artisan('accounts:create-admin', ['email' => 'boss@example.com', '--password' => 'short'])->assertFailed();
    $this->artisan('accounts:create-admin', ['email' => 'not-an-email', '--password' => 'long-secret-1'])->assertFailed();
    $this->artisan('accounts:create-admin', ['email' => 'boss@example.com', '--password' => 'long-secret-1', '--role' => 'teacher'])->assertFailed();

    expect(User::count())->toBe(0);
});
