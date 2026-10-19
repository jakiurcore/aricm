# Core Module

Read this before changing the module or using its classes from elsewhere.

## Purpose
Settings (key/value), locale handling, Bangla helpers, shared Blade layouts, shared admin JS (`@core/js`) and the cross-module contracts. Depends on no module. Must never import Accounts or anything downstream.

## Public surface
- Helpers: `bn_digits()`, `bn_number()`, `bn_date()`, `setting()`, `localized()`; class `Modules\Core\Support\Bangla`.
- `Modules\Core\Services\Settings` (get/set/setMany, cached). Defaults live in `config/core.php` under `modules.core.settings`.
- Contracts (`Contracts/`): `ProvidesHomepageSections`, `ProvidesPortalMenu`, `ProvidesPermissions`, `ProvidesVerification` (+ `VerificationHandler`). DTOs in `Support/`: `MenuItem`, `HomepageSection`, `VerificationResult`.
- `/verify/{type}/{hash}` resolved through `VerificationRegistry`.
- Locale: `SetLocale` middleware is added to the web and api groups in `config/laramod.php`; `/locale/{bn|en}` switches.
- Admin i18n: each module's `lang/{locale}/admin.php` is exported to React as `module::admin.key`. The map is inlined in `resources/views/vendor/admin/app.blade.php` as `window.__I18N__`, so `t()` is synchronous. Also served at `GET /api/core/translations`.
- JS: `@core/js/api`, `i18n` (`t`), `number` (`bnDigits`, `formatDate`), `components/{DataTable,ConfirmDialog,Field,Toaster}`, `hooks/usePaginated`.

## Rules
- No hardcoded UI text: add keys to both `lang/bn` and `lang/en`. React text goes in `admin.php`.
- Gate Core routes with `can:` strings, not Accounts classes.
- laramod-admin chrome ("Sign out", "Page not found") is English and lives in vendor; the Dashboard menu labels are translated by the `core-chrome` registration in `resources/js/dashboard.tsx`.
- The admin's design tokens (`--muted`, `--accent`) clash with shadcn's, so design tokens are `ink-muted` / `accent-red` in Tailwind.

## PDFs and QR (Phase 5)
- `Services\PdfService` wraps mPDF with Hind Siliguri (Bangla, OpenType layout on so conjuncts shape) and XB Riyaz (Arabic, bundled with mPDF). Fonts live in `resources/fonts`. **Do not swap in Noto Bengali or Amiri: mPDF 8.3 throws while parsing their tables** ("Undefined array key", "GPOS Lookup Type 5, Format 3 not supported").
- mPDF supports a CSS 2-ish subset: build layouts with tables and `mm` units. Absolute positioning *inside* an absolutely positioned box drifts; use fixed-height tables for card interiors. Never use `preg_split('/\R/')` on Bangla without the `u` flag (byte 0x85 is part of "অ" and gets split).
- Box model is content-box: `height` + padding + border must fit the page.
- `Services\QrCodeService` makes SVG data URIs (no GD needed). `Support\SignedHash` (`{id}-{hmac}`) is the token used in `/verify/{type}/{hash}`; `Support\Branding::forPdf()` gives the letterhead data; `components/pdf/letterhead` is the shared header.
- Controllers use `Http\Controllers\Concerns\ServesPdf`. Documents: ID cards (Students), admit cards (Exams), marksheet/tabulation/merit (Results), receipts (Fees).
- Without GD, `ImageUploader` stores uploads untouched and mPDF skips images it cannot read (`showImageErrors` off); production hosts have GD.

## PDF layout lessons (design port)
- Fixed-page documents (ID card, admit card, marksheet, A5 fee documents) are built from `<x-core::pdf.at x y w h>` boxes: every element is a top-level absolutely positioned div in page millimetres taken from the Claude Design file (never nest absolute boxes). Variable-length parts (tables, notes) flow inside one `at` box.
- mPDF has no CSS rotate, no rounded auto-width boxes, no clip-to-circle and no gradient angle. Substitutes: `Branding::discMask()` (SVG corners over a logo), `<x-core::pdf.pill>` (cap + cell + cap), `PrintDocumentFactory::stamp()` (rotated SVG stamp), `background-gradient: linear c1 c2 x1 y1 x2 y2` where **y=1 is the top**.
- Absolutely positioned `<img>` drifts: put images inside an `at` box. Opacity works on images (watermarks), `restrictColorSpace` hides images without GD (thermal logo uses the pre-greyed `logo-gray.jpeg`).
- Hind Siliguri stands in for Noto Serif/Sans Bengali (mPDF cannot parse Noto); its digit ১ is drawn differently from Noto's.
