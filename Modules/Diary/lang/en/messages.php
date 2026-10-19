<?php

return [
    'menu' => 'Diary',
    'general_note' => 'General note',
    'teacher_menu' => ['diary' => 'Diary', 'class_day' => 'Class day'],
    'remark_kinds' => ['praise' => 'Praise', 'concern' => 'Needs attention', 'info' => 'Info', 'no_homework' => 'Homework not brought', 'inattentive' => 'Inattentive', 'absent' => 'Absent', 'other' => 'Other'],
    'edit_window_closed' => 'This diary entry can no longer be edited: its edit window after publishing has passed.',
    'empty_entry' => 'Write the lesson, homework or a note before publishing.',
    'date_out_of_range' => 'A diary can be written for today or up to :days days back, never for a future date.',
    'not_a_school_day' => 'The madrasa is closed on this date.',
    'too_many_attachments' => 'An entry can have at most :max attachments.',
    'student_not_in_section' => 'This student is not in this section.',
    'sms' => [
        'guardian' => ':name — today’s diary has :subjects subject(s), :homework with homework. See the portal for details.',
        'teacher' => 'Today’s diary is not complete yet: :list',
    ],
    'portal' => [
        'title' => 'Diary', 'week_prev' => 'Previous week', 'week_next' => 'Next week', 'today' => 'Today', 'holiday' => 'Holiday — no classes', 'no_entries' => 'No diary for this day',
        'lesson' => 'Today’s lesson', 'homework' => 'Homework', 'due' => 'Due: :date', 'note' => 'Special instruction', 'remarks' => 'Teacher’s remarks', 'attachments' => 'Attachments',
        'acknowledge' => 'Seen', 'acknowledged' => 'Seen', 'acknowledge_all' => 'Mark all seen', 'new' => 'New', 'edited' => 'Edited', 'by' => 'Teacher: :name',
        'holiday_short' => 'Off', 'future' => 'This date’s diary is not visible yet', 'no_entries_body' => 'Entries appear here once teachers publish them.', 'acknowledge_btn' => 'Guardian has seen ✓', 'prep_for' => 'Prepare for next school day', 'personal_remark' => 'Personal remark', 'due_on' => 'Due', 'pending_homework' => 'Pending homework', 'no_pending' => 'No pending homework', 'tomorrow' => 'Tomorrow', 'calendar' => 'Calendar',
    ],
];
