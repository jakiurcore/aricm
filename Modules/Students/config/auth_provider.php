<?php

use Modules\Students\Models\Student;

/*
| The "students" user provider behind the student guard declared by Accounts (auth.guards.student).
*/
return [
    'driver' => 'eloquent',
    'model' => Student::class,
];
