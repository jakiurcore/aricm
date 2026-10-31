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

it('creates the first admin from the environment only while there are no users', function () {
    putenv('INITIAL_ADMIN_EMAIL=first@example.com');
    putenv('INITIAL_ADMIN_PASSWORD=long-secret-1');
    $_ENV['INITIAL_ADMIN_EMAIL'] = 'first@example.com';
    $_ENV['INITIAL_ADMIN_PASSWORD'] = 'long-secret-1';

    $this->artisan('accounts:create-admin', ['--if-none' => true])->assertSuccessful();
    expect(User::where('email', 'first@example.com')->first()?->isSuperAdmin())->toBeTrue();

    // A second deploy must not reset the password or add anyone.
    $_ENV['INITIAL_ADMIN_PASSWORD'] = 'changed-secret-2';
    putenv('INITIAL_ADMIN_PASSWORD=changed-secret-2');
    $this->artisan('accounts:create-admin', ['--if-none' => true])->assertSuccessful();

    expect(User::count())->toBe(1)->and(Hash::check('long-secret-1', User::first()->password))->toBeTrue();

    putenv('INITIAL_ADMIN_EMAIL');
    putenv('INITIAL_ADMIN_PASSWORD');
    unset($_ENV['INITIAL_ADMIN_EMAIL'], $_ENV['INITIAL_ADMIN_PASSWORD']);
});

it('does nothing when there are no users and no initial admin values', function () {
    $this->artisan('accounts:create-admin', ['--if-none' => true])->assertSuccessful();

    expect(User::count())->toBe(0);
});
