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
];
