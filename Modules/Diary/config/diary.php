<?php

return [
    /*
    | Defaults for the diary settings (stored in the Core settings table under a "diary_" prefix).
    */
    'defaults' => [
        'enabled' => true,
        // Published entries stay editable by their teacher for this long.
        'edit_window_hours' => 48,
        // How the edit window ends: "hours" (the line above), "same" (23:59 of the publishing day), "2h", "next9" (09:00 the next day) or "none".
        'edit_window' => 'hours',
        // Whether students see entries dated in the future (teachers cannot write those today, so this only matters once they can).
        'student_sees_future' => false,
        // The largest single attachment, in MB.
        'max_attachment_mb' => 5,
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
