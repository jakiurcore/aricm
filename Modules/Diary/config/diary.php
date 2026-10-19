<?php

return [
    /*
    | Defaults for the diary settings (stored in the Core settings table under a "diary_" prefix).
    */
    'defaults' => [
        'enabled' => true,
        // Published entries stay editable by their teacher for this long.
        'edit_window_hours' => 48,
        // How many days back a teacher may write an entry for.
        'backdate_days' => 2,
        'max_attachments' => 5,
        // The 6 PM summary SMS to guardians, and the reminder to teachers who have not written yet. Off by default.
        'sms_enabled' => false,
        'sms_teacher_reminder' => false,
    ],

    'attachments' => [
        'disk' => 'public',
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],
];
