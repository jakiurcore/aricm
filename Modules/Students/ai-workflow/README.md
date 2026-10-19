# Students Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Student records, enrollment history, ID generation (ARICM-YYYY-NNNN), CSV import/export, promotion, and the `students` auth provider behind the `student` guard (the guard and login screens are in Accounts). Depends on Core, Accounts, Academic.

## Public surface
- `Services\StudentDirectory`: `find`, `findByStudentId`, `query(filters)`, `activeInClass`, `enrollmentFor`, `activeCount`. Downstream modules use this, not the tables.
- `Services\StudentRegistrar` (register/update, keeps the enrollment row in step), `StudentIdGenerator`, `StudentPasswords` (default password = DOB as DDMMYYYY, forced change), `PromotionService`, `StudentImporter`/`StudentExporter` (`StudentCsv::COLUMNS`).
- Contract `Contracts\ProvidesStudentSummary` → `Support\StudentSummaryItem`; collected by `StudentSummaryCollector` for the admin profile and the portal dashboard (Fees: dues, Results: GPA, Diary: unseen).
- Enums: `StudentStatus`, `Gender`, `ResidencyType` (residency_type lives here and is read by Fees).
- Permissions: `students.view|manage|import|promote|reset_password|id_card` (accountant: view).

## Rules
- The Student model is the authenticatable of the student guard: Accounts relies on the columns `student_id`, `status` ('active' only may sign in), `password`, `must_change_password`.
- Never accept `student_id`, `password` or `must_change_password` from a request; the registrar sets them.
- Unique roll per (session, class, section).
- Students are soft deleted; IDs are never reused (generator reads trashed rows).
- Demo data: `Database\Seeders\DemoStudentSeeder` (30 students).
