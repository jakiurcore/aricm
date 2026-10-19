<?php

use Modules\Accounts\Models\User;

it('signs an admin in and lands on the admin', function () {
    $user = userWithRole('admin');

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('sends a teacher to the teacher panel', function () {
    $user = userWithRole('teacher');

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/teacher');
});

it('rejects a wrong password with a Bangla message', function () {
    $user = userWithRole('admin');

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'nope'])
        ->assertSessionHasErrors(['email' => 'ইমেইল বা পাসওয়ার্ড সঠিক নয়।']);

    $this->assertGuest();
});

it('does not let an inactive user sign in', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
});

it('locks out after too many failed attempts', function () {
    $user = userWithRole('admin');

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'nope']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('signs out', function () {
    $this->actingAs(userWithRole('admin'))->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});
