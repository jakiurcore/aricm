# Academic Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Academic sessions, classes (division kids/junior), sections, subjects and the class↔subject mapping. Later: the school calendar (holidays, next school day) — see the Diary work. Depends on Core and Accounts.

## Public surface
- `Services\AcademicDirectory`: `currentSession()`, `sessions()`, `classes()`, `subjectsOf($classId)`, `sectionBelongsToClass()`, `options()` (dropdown payload, also `GET /api/academic/options`). Downstream modules must use this instead of querying academic tables.
- `Services\SessionManager::makeCurrent()` keeps exactly one current session.
- Models: `AcademicSession`, `SchoolClass` (table `classes`), `Section`, `Subject`; all have factories.
- Permissions: `academic.view`, `academic.manage` (accountant: view).
- JS: `@academic/js/hooks/useAcademicOptions`.

## Rules
- Content columns are `*_bn` (required) and `*_en` (optional); use `localized($model, 'name')`.
- Deleting a record used downstream fails on the foreign key and `GuardsDeletion` turns that into a 422 — downstream tables must keep `restrictOnDelete` FKs to academic tables.
- Never trust a class/section pair from the client: validate with `sectionBelongsToClass()`.
- Demo data: `Database\Seeders\DemoAcademicSeeder` (called by the app's `DemoSeeder`).

## School calendar
- `school_calendar_holidays` plus the setting `academic_weekly_holidays` (default `["friday"]`). `Services\SchoolCalendar`: `isSchoolDay`, `nextSchoolDay`, `previousSchoolDay` (bounded to a year so an all-holiday calendar cannot loop). Admin page "ছুটি ও ক্যালেন্ডার", API `academic.api.holidays.*`.
