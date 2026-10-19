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
