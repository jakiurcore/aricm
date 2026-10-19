<?php

return [
    'group' => 'Diary',
    'monitor' => [
        'menu' => 'Diary monitor', 'title' => 'Diary monitor', 'date' => 'Date', 'expected' => 'Expected', 'published' => 'Published', 'draft' => 'Draft', 'missing' => 'Missing', 'percent' => 'Done',
        'holiday' => 'This day is a holiday, no diary is expected.', 'teacher' => 'Teacher', 'class' => 'Class', 'section' => 'Section', 'subject' => 'Subject', 'status' => 'Status',
        'status_published' => 'Published', 'status_draft' => 'Draft', 'status_missing' => 'Not written', 'by_teacher' => 'By teacher (last 14 days)', 'entries' => 'Diary entries',
        'lesson' => 'Lesson', 'homework' => 'Homework', 'note' => 'Note', 'seen' => 'Seen', 'acknowledged' => 'Acknowledged', 'remarks' => 'Remarks', 'edited' => 'Edited', 'revisions' => 'Revision history',
        'delete' => 'Delete', 'empty' => 'No diary entries.',
    ],
    'settings' => [
        'menu' => 'Diary settings', 'title' => 'Diary settings', 'enabled' => 'Diary enabled', 'edit_window_hours' => 'Edit window after publishing (hours)', 'backdate_days' => 'How many days back a diary may be written',
        'max_attachments' => 'Maximum attachments', 'sms_enabled' => 'Send guardians an SMS at 6 PM', 'sms_teacher_reminder' => 'SMS reminder to teachers who have not written',
        'sms_hint' => 'Until an SMS provider is configured, messages are only written to the log.',
    ],
    'phrases' => [
        'menu' => 'Quick phrases', 'title' => 'Quick phrases', 'new' => 'New phrase', 'edit' => 'Edit phrase', 'field' => 'Shown for', 'text_bn' => 'Phrase', 'sort_order' => 'Order', 'is_active' => 'Active',
        'fields' => ['lesson' => 'Covered today', 'homework' => 'Homework', 'note' => 'Note', 'remark' => 'Student remark'],
    ],
];
