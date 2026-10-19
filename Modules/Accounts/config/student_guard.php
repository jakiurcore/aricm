<?php

/*
| The student session guard. The "students" user provider is declared by the Students module
| (auth.providers.students) because Accounts must not know the Student model.
*/
return [
    'driver' => 'session',
    'provider' => 'students',
];
