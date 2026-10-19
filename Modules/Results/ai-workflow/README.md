# Results Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Grading scales, the marks grid, the result engine, processing (totals/GPA/grade/positions), publish/unpublish, the public result search and marksheet QR verification. Depends on Core, Accounts, Academic, Students, Exams (and Teachers when teacher marks entry is added).

## Public surface
- `Services\Engine\*` — the pure rules (`ResultCalculator`, `GradeScale`, value objects). No models, no database; unit-tested. Rules: a subject fails on any component below pass, subject total below pass, or any absence; missing marks count as 0 and flag the result incomplete; a failed compulsory subject → F / GPA 0.00; optional subjects never fail the student; GPA = average of counted subjects (2 dp, capped at the scale max); optional bonus (points above 2.00) only when setting `results_optional_bonus` is on.
- `Services\MarksService`: `grid()`, `columns()`, `save()` (re-validates every id against exam, class and roster; blocked while published; optional `$onlySubjectIds` for teachers).
- `Services\ResultProcessor::process(exam, classId)` and `assignPositions()` (rank by passed, GPA, total; ties share a position and the next skips).
- `Services\ResultPublisher`, `Services\ResultDirectory` (the only thing students/portals should read: nothing is returned unless the exam is published), `ResultSearch`, `Support\MarksheetHash`.
- Contract `Contracts\ProvidesResultEligibility` (Fees implements it); `ResultDirectory::eligibility()` asks every provider.
- Implements `ProvidesHomepageSections` (search widget), `ProvidesPortalMenu` (route `portal.results`, defined by StudentPortal), `ProvidesVerification` (`marksheet`), `ProvidesStudentSummary` (latest GPA), `ProvidesPermissions` (`results.view|enter_marks|process|publish|grading`).
- Public routes: `GET/POST /results` (POST, throttled, no personal data in the URL; "not found", "wrong DOB" and "not published" look identical).
- The migration adds `exams.result_published/_at`; the Exam model casts them.

## Rules
- Students and the public see results only through `ResultDirectory`.
- Marks and processing are locked while the exam is published.
- `exam_subject_id`/`component_id` FKs are restrict: removing a subject with marks fails (Exams turns it into a 422).

## Teacher marks entry
- `Services\TeacherMarksEntry`, off unless the setting `teacher_marks_entry_enabled` is on (Core settings page). A teacher sees and saves only the exam subjects they teach in their own sections (`TeacherScope::canTeach`); unassigned subjects, other sections and other students are refused, and nothing can change once the result is published. JSON endpoints `results.teacher.*` under `/teacher/api/marks`; the page is in TeacherPanel.
