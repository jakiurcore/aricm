# Teachers Module

## Purpose
The teacher register, who teaches which subject in which section (assignments) and the one service the rest of the system asks about a teacher's reach. **Depends on Core, Accounts, Academic.**

## Data
- `teachers` (optional `user_id` → the teacher-role login), `teacher_assignments` (session, class, section, subject; a row with `is_class_teacher` and no subject is the class teacher of that section). Assignments are per session; `copy()` carries them into a new one.

## Public surface
- `Services\TeacherScope`: `teacherFor(user)`, `assignments`, `canTeach(user, class, section, ?subject)`, `isClassTeacher`, `canSee`, `sections` (for menus), `subjectIds`. Current session only; an inactive teacher or a user without a record has an empty scope.
- `Services\TeacherAssignments`: `assignSubject` (checks section ∈ class, subject ∈ class, no duplicates), `setClassTeacher` (replaces the previous one: exactly one per section per session), `remove`, `copy`.
- `Services\TeacherRegistrar`: `register` (+ optional login with the `teacher` role), `createLogin`, `syncLogin`.
- `Contracts\ProvidesTeacherMenu`: implemented by Diary and Results; TeacherPanel renders it.
- Permissions: `teachers.manage`, `teacher_assignments.manage`, `marks.enter_assigned` (the `teacher` role gets the last one).

## Rules
- Other modules never query teacher tables: they ask `TeacherScope`.
- Deleting a teacher deactivates the login (history stays); there is no hard delete from the admin.
- Demo data: `DemoTeacherSeeder` (teacher1…5@example.com / password).
