# Fees Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Fee heads, per-session/class (and per-residency) fee structure, concessions, invoices (monthly, one-time, exam), payments (counter, manual wallet/bank, optional online gateway), receipts, reports and late fines. Replaces the old exam_fees/fee_payments design. Depends on Core, Accounts, Academic, Students, Exams and Results (it implements Results' eligibility contract).

## Money rules
- Every amount is an **integer of poisha** (৳1 = 100) in `unsignedBigInteger` columns; models cast through `Casts\MoneyCast` to `Support\Money`. Never use floats. Convert taka input with `Money::taka()` at the edge only.
- Never trust an amount, invoice id or student id from the client: online and manual payments take their amount from the invoices (`PaymentService::submitManual`, `GatewayPayments::start`). The counter desk takes the cash actually received.
- Anything that moves balances runs in a transaction with `lockForUpdate` on the rows (`PaymentAllocator`, `PaymentService`, `LateFineApplier`).
- `FeeInvoice::recalculate()` rebuilds total/paid/due/status from items and allocations: call it after every change; `due` is stored.
- SQLite stores `date`-cast columns as `Y-m-d 00:00:00`: query them with `whereDate()`.

## Services (the public surface)
- `FeeCalculator` (pure `discount()`/`structureAmount()` + DB `quote()`): residency-specific row beats the generic one; concessions add up, capped at the gross; validity months are inclusive.
- `InvoiceGenerator`: `generateMonthly` (idempotent, skips pre-admission months, dry run = preview), `generateExam`, `generateOneOff`, `ensureAdvanceMonths` (current session only). Credit is applied on creation.
- `PaymentAllocator` (oldest-due-first or chosen invoices; remainder → `student_credits`; credit allocations have `payment_id = null`, `source = credit`).
- `PaymentService`: `collectAtCounter`, `submitManual` (pending; unique (method, txn id); format per method in `TransactionRules`), `approve(payment, ?User)` (null = system, after a gateway confirmed), `reject`, `waive`. Notifies the student (database notification) and the guardian by SMS when `fees_sms_enabled`.
- `LateFineApplier` (idempotent; never touches paid/waived/cancelled), `ReceiptService` (A5 + 80 mm PDF, amount in words, QR), `FeeReports` + `ReportExporter` (PDF/Excel), `StudentFeeDesk` (everything the portal shows; always scoped to the student), `EligibilityChecker`.
- Gateways: `Contracts\PaymentGateway`, `Gateway\{Manual,SslCommerz,Bkash}Gateway`, `GatewayManager` (`extend()` for tests), `GatewayPayments` (`start`, `settle`, `fail`). A payment is approved **only** by `verify()` asking the provider's server (success, exact amount, BDT, our reference); callbacks/IPNs are untrusted and repeatable. Credentials only in `.env` (`config/fees.php`). The drivers follow the providers' docs but have **not** been run against a live sandbox here (no credentials): confirm bKash's `Authorization` header form and production URL, and SSLCOMMERZ's flow, in the sandbox first.
- Settings: `FeeSettings` over the Core settings table (`fees_*` keys; `result_requires_no_dues` is Core's `result_requires_fee_paid`).

## Contracts implemented (others never import Fees)
`ProvidesAdmitCardEligibility` (Exams), `ProvidesResultEligibility` (Results), `ProvidesStudentSummary` (Students), `ProvidesPortalMenu` ("বেতন ও ফি", route `portal.fees`), `ProvidesVerification` (`receipt`), `ProvidesPermissions`, `ProvidesCommands` (`fees:generate-monthly`, `fees:apply-late-fines`, `fees:due-reminders`; scheduled in `routes/console.php`, one cPanel cron line runs `schedule:run`).

## RBAC
`fees.view|collect|verify|manage_structure|concession|waive|generate|reports|settings`. Accountant: view, collect, verify, generate, reports. Admin/super_admin: all. Teacher: none (403 on every fee route, tested).

## Admin UI
`resources/js/dashboard.tsx`: collection desk, verification queue, invoices (+generate preview→confirm, one-off, waive), heads, structure matrix, concessions, reports, settings, dashboard widgets, and a "ফি" tab on the exam page via `registerExamTab()`.
