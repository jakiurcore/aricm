<?php

return [
    'menu' => 'Teachers',
    'group' => 'Teachers',
    'teachers' => [
        'menu' => 'Teacher list', 'title' => 'Teachers', 'new' => 'New teacher', 'edit' => 'Edit teacher',
        'name_bn' => 'Name (Bangla)', 'name_en' => 'Name (English)', 'designation' => 'Designation', 'phone' => 'Mobile', 'is_active' => 'Active',
        'email' => 'Login email', 'password' => 'Password', 'login_hint' => 'Give an email and password to create a login for this teacher (at least 8 characters).',
        'no_login' => 'No login', 'assignments' => 'Assignments', 'make_login' => 'Create login', 'reset_password' => 'Change password', 'new_password' => 'New password', 'save' => 'Save',
    ],
    'assignments' => [
        'menu' => 'Teacher assignments', 'title' => 'Teacher assignments', 'session' => 'Session', 'teacher' => 'Teacher', 'class' => 'Class', 'section' => 'Section', 'subject' => 'Subject',
        'class_teacher' => 'Class teacher', 'add_subject' => 'Assign subject', 'add_class_teacher' => 'Set class teacher', 'choose' => 'Choose', 'empty' => 'No assignments in this session.',
        'copy_title' => 'Copy from an earlier session', 'copy_from' => 'Copy from session', 'copy_action' => 'Copy', 'copied' => ':count assignments copied.', 'remove' => 'Remove',
    ],
];
