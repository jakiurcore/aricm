<?php

return [
    'login' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],

    // Where a user lands after signing in without an intended URL.
    'home' => [
        'admin' => '/admin',
        'teacher' => '/teacher',
        'student' => '/portal',
    ],

    // Read by `accounts:create-admin --if-none` on the first deploy, which only acts while there are no users.
    // Set both in the sealed environment, deploy once, then remove them.
    'initial_admin' => [
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],
];
