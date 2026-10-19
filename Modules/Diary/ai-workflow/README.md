# Diary Module

## Purpose
What each teacher taught and assigned, per subject per day. Teachers write it (TeacherPanel), students and guardians read it (StudentPortal), the office watches compliance. **Depends on Core, Accounts, Academic, Students, Teachers.**

## Data
`diary_entries` (one per teacher + section + subject + day; null subject = class teacher's general note; `status` draft/published; `last_edited_at` set when edited after publishing), `diary_attachments`, `diary_student_remarks`, `diary_acknowledgements` (`seen_at`, `acknowledged_at`), `teacher_quick_phrases` (null teacher = shared). Entries are logged with activitylog (`diary` log): that is the revision trail.

## Public surface
- `Services\DiaryService`: `save` (upsert, so autosave is safe to repeat), `publish`, `delete`, `canEdit/canDelete`, attachments, remarks. Enforces: assigned section+subject (`TeacherScope`), school day (`SchoolCalendar`), not future, not older than `backdate_days`, edit window after publishing (`edit_window_hours`; diary managers are exempt). Homework due date = next school day unless the teacher picked one.
- `Services\DiaryFeed`: the student side: `forDay`, `week` (Saturday–Friday), `unseenCount`, `markSeen`, `acknowledge`, `acknowledgeDay`. A student sees published entries of their own section and only their own remarks.
- `Services\DiaryCompliance`: expected vs published/draft/missing per school day, `missing()`, `byTeacher()`.
- `Services\DailyDiarySummary` + `Jobs\SendDailyDiarySummary` + `diary:send-daily-summary`: the 6 PM SMS (scheduled in `routes/console.php`): guardian summary and teacher reminder, each behind its own setting (`diary_sms_enabled`, `diary_sms_teacher_reminder`, off by default). Sends through Core's `SmsManager` (log driver by default).
- `Policies\DiaryEntryPolicy`; settings in `Services\DiarySettings` (`diary_*` keys).
- Implements `ProvidesPortalMenu` (with the unseen badge), `ProvidesTeacherMenu`, `ProvidesPermissions` (`diary.write` for teachers, `diary.monitor`, `diary.manage`).
- Teacher JSON endpoints `diary.teacher.*` under `/teacher/diary/…`; admin API `diary.api.*` (compliance, entries, revisions, settings, shared phrases).

## Rules
- The pages live in TeacherPanel and StudentPortal; this module only owns data, rules and JSON.
- Dates compare with `whereDate` (SQLite stores them with a time part).
