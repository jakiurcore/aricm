# StudentPortal Module

## Purpose
The signed-in student's pages at `/portal` (guard `student`, behind `AuthenticateStudent`, which also forces the first-login password change): dashboard, read-only profile, own ID card PDF, exam routine, admit card, fees (overview, month grid, bills + PDF, 3-step pay, history + receipts, ledger), notices, results (marksheet + PDF). A **consumer**: it reads everything through other modules' services and contracts; it never queries their tables.

## How it composes
- Navigation: `Services\PortalMenu` collects every module's `ProvidesPortalMenu` (Exams: routine/admit; Fees: "বেতন ও ফি" with a due badge; Results; Notices; Diary later). Items whose route does not exist are dropped. On a phone the first three flagged `inBottomNav` go in the bottom bar, the rest behind "More".
- Dashboard cards: `Students\Services\StudentSummaryCollector` (Fees: dues, Results: GPA, Diary: unseen).
- Route names other modules point at: `portal.fees`, `portal.results`, `portal.notices`, `portal.routine`, `portal.admit`.

## Security rules (all tested)
- The student always comes from the session (`$request->user('student')`), never from the URL or form. Invoice/payment lookups go through `StudentFeeDesk`, so a tampered id is a 404.
- Amounts are never taken from the form: `Fees\Services\PaymentService::submitManual` and `GatewayPayments::start` compute them from the invoices. Manual submissions are throttled (6/hour).
- Admit card and marksheet PDFs re-check eligibility/publication on the download (a URL is not a permission). Results appear only after publish and when every `ProvidesResultEligibility` provider agrees.
- Included/partial views must not rely on `@props`; the pay page is Alpine-driven (`payFlow`) and shows an estimate for advance months only for display.

## Diary
- `portal.diary`: a Saturday–Friday week strip, one day of entries per subject, the student's own remarks, "seen" on opening a day and an acknowledge button (per entry or the whole day). The menu item carries the unseen badge (from Diary's `ProvidesPortalMenu`).
