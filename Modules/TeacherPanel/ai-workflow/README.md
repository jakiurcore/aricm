# TeacherPanel Module

## Purpose
The teacher's mobile-first pages at `/teacher` (Blade + Tailwind + Alpine). A consumer module: it may use any module, owns no data. Guard: signed-in user (web guard) who is active and has an active teacher record (`Http\Middleware\EnsureTeacher`).

## Pages
- `teacher.home`: classes and subjects, today's diary progress and what is still to write.
- `teacher.diary` / `teacher.diary.write`: the day's rows with status, then the editor: quick-phrase chips, autosave every 20 s, a localStorage copy of every keystroke (restored if newer than the server), attachments, student remarks.
- `teacher.class-day`: the class teacher's view of one section and day (every subject, general note, remarks).
- `teacher.marks` / `teacher.marks.entry`: marks entry for the teacher's own subjects, only while `teacher_marks_entry_enabled` is on.

## Navigation
`Services\TeacherMenu` collects `ProvidesTeacherMenu` from every module (Diary, Results), drops items whose route does not exist, and renders a desktop sidebar and a phone bottom bar with a "More" drawer. A new teacher page = implement `ProvidesTeacherMenu` in its module and add the route here.

## Rules
- The pages call the Diary (`/teacher/diary/…`) and Results (`/teacher/api/marks/…`) JSON endpoints; every check is enforced there, the page only hides what is not allowed.
