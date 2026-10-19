# Exams Module

Entry point for AI agents. Read this before changing the module or using its classes from elsewhere.

## Purpose

What this module is responsible for, and what it deliberately is not.

## Public Surface

What the application and other modules may rely on: routes, events, contracts, config keys. Everything else is internal and may change.

## Conventions

Rules that only apply inside this module.

## Workflows

Step-by-step guides for recurring tasks live in `workflows/*.md`. List each one here with a line on when to use it.
## Purpose
Exams with free-text names, the classes taking part, per-class subject setup (full/pass marks, include_in_gpa, optional, components such as লিখিত/MCQ/মৌখিক/ব্যবহারিক), routines, and admit card release + eligibility. Depends on Core, Accounts, Academic, Students.

## Public surface
- `Services\ExamDirectory` (read API for Results, Fees, portals): `find`, `forClass`, `examHasClass`, `subjectsFor(exam, classId)` (with components), `routineFor`.
- `Services\ExamConfigurator`: `syncClasses`, `setSubjects` (upserts, keeps ids so marks stay attached; subject total = sum of components), `seedFromClass`, `copyFrom`, `setRoutine`.
- `Services\AdmitCardService`: `eligibility(exam, student, checkProviders, ignoreRelease)`, `roster`, `payload` (data for the card/PDF).
- Contract `Contracts\ProvidesAdmitCardEligibility::admitCardEligibility(Exam, Student): Support\Eligibility` — implemented by Fees; Exams asks every provider through `Modules::providing()`.
- `exams.grading_scale_id` is a plain column (the table belongs to downstream Results; no FK).
- JS: `registerExamTab()` from `resources/js/tabs.ts` lets other modules add a tab to the exam detail page (Fees adds "ফি").
- Permissions: `exams.view|manage|admit_card` (accountant: view).

## Rules
- Downstream tables (marks, invoices) must reference `exam_subjects`/`exam_subject_components`/`exams` with restrictOnDelete; `ExamConfigurator` turns FK violations into a 422.
- Components are matched by id on update so marks never lose their component.
