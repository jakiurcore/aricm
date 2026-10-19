<?php

return [
    'panel' => 'Teacher panel', 'logout' => 'Log out', 'menu' => 'Menu', 'close' => 'Close', 'back' => 'Go back', 'theme' => 'Theme', 'photo' => 'Photo',
    'greeting' => 'Assalamu alaikum,', 'today' => 'Today', 'next_day' => 'Next class day', 'holiday' => 'Today is a holiday — no diary is needed today.',
    'class_teacher' => 'Class teacher', 'no_assignments' => 'You have not been given any class or subject yet. Please contact the office.',
    'write_diary' => 'Write diary', 'section_label' => ':class – Section :section',
    'nav' => ['home' => 'Home', 'write' => 'Diary', 'remarks' => 'Remarks', 'history' => 'History', 'class_day' => 'Class diary', 'profile' => 'Profile'],
    'titles' => [
        'home' => 'Today’s diary', 'write_new' => 'Write diary', 'write_edit' => 'Edit diary', 'remarks' => 'Private remarks', 'history' => 'My diary history',
        'class_day' => 'Class teacher’s diary', 'profile' => 'Profile', 'password' => 'Change password',
    ],
    'status' => ['published' => 'Written', 'draft' => 'Draft', 'missing' => 'Pending'],
    'home' => [
        'empty_title' => 'No diary has been written today', 'empty_body' => 'Write and publish the diary in two minutes after each class. Guardians see it right away.', 'empty_cta' => 'Write the first diary',
        'summary' => ':done of :total published', 'not_written' => 'Not written yet today', 'draft_saved' => 'Draft saved · :time', 'published_meta' => 'Published :time · :seen/:total guardians have seen it',
        'write' => 'Write', 'edit' => 'Edit', 'class_teacher_banner' => 'Class teacher: :label', 'class_teacher_short' => 'Today :done of :total subjects have a diary · :unseen guardians have not seen it yet', 'full_day' => 'Full-day diary',
    ],
    'diary' => [
        'holiday' => 'The madrasa was closed on this day.', 'general' => 'General note', 'locked' => 'This date can no longer be written',
        'date' => 'Date', 'class_section' => 'Class and section', 'subject' => 'Subject', 'due' => 'Due date',
        'fields' => ['lesson' => 'Today’s lesson', 'next_lesson' => 'Reading for tomorrow', 'homework' => 'Homework', 'note' => 'Special instructions'],
        'placeholders' => ['lesson' => 'What was taught in class today', 'next_lesson' => 'Preparation for the next class day', 'homework' => 'Write the homework', 'note' => 'e.g. Bring a geometry box tomorrow'],
        'own_phrase' => 'My own phrase', 'phrase_placeholder' => 'e.g. Submit your copy', 'save_phrase' => 'Save', 'phrase_saved' => 'Phrase saved',
        'copy_previous' => 'Copy from the previous day’s entry', 'no_previous' => 'No earlier entry was found', 'copied' => 'Copied from the entry of :date — adjust as needed',
        'note_holiday' => ':day is a holiday — no diary can be written for this day.', 'note_past' => 'An entry for an earlier date — guardians will see it on that day’s page.',
        'save_draft' => 'Save draft', 'publish' => 'Publish', 'update' => 'Publish update', 'publish_many' => 'Publish (:count sections)',
        'saved_at' => '✓ Saved automatically · :time', 'unsaved' => '● Not saved yet', 'saving' => '● Saving…', 'autosave_on' => '○ Autosave is on',
        'draft_saved' => 'Draft saved', 'published_toast' => 'Diary published — guardians can see it ✓', 'published_many' => 'Published in :count sections ✓',
        'empty' => 'Write something in at least one box.', 'reloading' => 'Loading again…',
        'restored' => 'Unsaved text kept on this phone was restored. Please save it.', 'offline' => 'No connection. Your text is kept on this phone; save again when you are back online.', 'save_first' => 'Write something and save before adding attachments.',
        'load_failed' => 'The diary could not be loaded', 'load_failed_help' => 'Check your internet connection and try again. Your draft is kept on this phone.', 'retry' => 'Try again',
        'attachments' => 'Attachments', 'attachments_hint' => 'A book page or worksheet · up to :mb MB', 'take_photo' => 'Take a photo', 'pdf_file' => 'PDF / file', 'remove' => 'Remove', 'too_big' => 'The file is larger than :mb MB',
        'also_title' => 'Also publish in other sections', 'also_hint' => 'The same text will be published in the selected sections too',
        'edit_until' => 'After publishing it can be edited for :hours hours.',
    ],
    'history' => [
        'class' => 'Class', 'all_classes' => 'All classes', 'all_subjects' => 'All subjects', 'clear' => 'Clear filters', 'count' => ':count entries',
        'empty_title' => 'No entries found', 'empty_body' => 'Change the filters or write a new diary.',
        'published_at' => 'Published :time', 'saved_at' => 'Saved :time', 'seen' => ':seen/:total guardians have seen it', 'edit' => 'Edit', 'locked' => 'Editing time is over',
    ],
    'remarks' => [
        'bulk' => 'Select several', 'cancel_bulk' => 'Cancel selection', 'hint' => 'Tap a student’s name to add a remark', 'hint_bulk' => 'Select the students who did not bring their homework',
        'roll' => 'Roll', 'selected' => ':count selected', 'no_homework' => 'Homework not brought', 'pick_first' => 'Select students first',
        'dialog' => 'Private remark', 'kind' => 'Kind of remark', 'text' => 'Remark (optional)', 'placeholder' => 'Write a short remark for the guardian', 'previous' => 'Earlier remarks', 'save' => 'Save remark',
        'saved' => 'Remark saved — the guardian will see it in the diary', 'bulk_saved' => '“Homework not brought” added for :count students',
        'holiday' => 'The madrasa is closed today, so remarks cannot be added.', 'no_students' => 'There are no active students in this section.',
    ],
    'class_day' => [
        'written' => 'Subjects written', 'seen' => 'Guardians who saw it', 'missing_count' => 'No diary', 'none_title' => 'No diary has been written for this day yet', 'none_body' => 'Use the list below to remind the subject teachers.',
        'missing_title' => 'Subjects without a diary', 'remind_all' => 'Remind everyone', 'state' => ['draft' => 'Draft', 'missing' => 'Pending'], 'all_done' => 'Every subject has its diary',
        'not_seen_title' => 'Guardians who have not seen it (:count)', 'send_sms' => 'Send SMS', 'full_day' => 'Full-day diary', 'draft_note' => 'Draft — guardians cannot see it', 'due' => 'Due: :date',
        'reminded' => 'Reminder sent to :count teachers', 'guardians_texted' => 'SMS sent to :count guardians', 'guardian_sms' => ':name — today’s diary has not been seen yet. Please check the portal.',
    ],
    'profile' => [
        'personal' => 'Personal details', 'id' => 'Teacher ID', 'phone' => 'Mobile', 'email' => 'Email', 'joined' => 'Joined', 'language' => 'Language', 'change_password' => 'Change password',
        'assignments' => 'My responsibilities', 'read_only' => 'View only', 'notices' => 'Notices', 'no_notices' => 'There are no notices.',
    ],
    'password' => ['current' => 'Current password', 'new' => 'New password', 'confirm' => 'Confirm password', 'save' => 'Save', 'changed' => 'Your password was changed.'],
    'marks' => [
        'title' => 'Enter marks', 'off' => 'Marks entry is not switched on. When the office turns it on, your subjects will appear here.', 'none' => 'There is no exam for you to enter marks for.', 'enter' => 'Enter marks',
        'roll' => 'Roll', 'absent' => 'Absent', 'save' => 'Save', 'saved' => 'Marks saved.', 'full' => 'Full marks', 'published' => 'The result is published, so marks cannot be changed.', 'changed' => ':count changes not saved',
    ],
];
