# আর রহমান ইন্টারন্যাশনাল ক্যাডেট মাদরাসা, গাইবান্ধা

Madrasa management and public website: admissions, students, exams, results, fees, a daily diary, a teacher panel and a student portal. Bangla first (English fallback), built to run on ordinary cPanel hosting.

Laravel 13, PHP 8.3+, a **Laramod** modular monolith (`Modules/*`), the **laramod-admin** React admin at `/admin`, a Blade + Tailwind 4 + Alpine public site, mPDF for every PDF.

## Contents

- [Modules](#modules)
- [Run it locally](#run-it-locally)
- [Sign-in addresses and demo logins](#sign-in-addresses-and-demo-logins)
- [Everyday commands](#everyday-commands)
- [Deploying to cPanel](#deploying-to-cpanel)
- [Settings you will want to change](#settings-you-will-want-to-change)
- [Backups](#backups)
- [Things to know before relying on it](#things-to-know-before-relying-on-it)

## Modules

Each module lives in `Modules/<Name>` with its own routes, migrations, views, translations, tests and an `ai-workflow/README.md` that says what it owns and the rules for changing it. The wiring order is `bootstrap/modules.php`.

| Module | What it does |
| --- | --- |
| Core | Settings, locale switch, mPDF + Bangla fonts, QR, image upload, SMS manager (log driver by default), the contracts other modules plug into, QR verification (`/verify/{type}/{hash}`) |
| Accounts | Admin users, roles and permissions, admin login, student login guard |
| Academic | Sessions, classes, sections, subjects, school calendar (holidays, weekly holiday, next school day) |
| Teachers | Teacher register, per-session assignments, one class teacher per section, `TeacherScope` |
| Students | Admission register, student IDs (`ARICM-YYYY-NNNN`), CSV import/export, promotion, ID cards (PDF) |
| Exams | Exams, per-class subject setup with components, routines, admit cards (PDF) |
| Results | Marks entry (admin, and teachers if switched on), the result engine, positions, publish, public result search, marksheets with QR |
| Fees | Heads, structures, concessions, invoices, counter and manual payments, SSLCommerz and bKash drivers, receipts, reports |
| Diary | What each teacher taught and assigned per subject per day, remarks, acknowledgements, compliance monitor, 6 PM SMS |
| Notices, Gallery, Admission | Public content and the online admission form |
| Website | The public site: home composed from every module, pages, sitemap, robots |
| StudentPortal | `/portal`: profile, exam routine, admit card, fees and payment, notices, results, diary |
| TeacherPanel | `/teacher`: mobile-first diary writing, class-teacher day view, marks entry |

Dependencies point one way (`Core ← Accounts ← Academic ← Teachers / Students ← Exams ← Results ← Fees`; Diary sits on Students + Teachers; Website, StudentPortal and TeacherPanel may use anything). `tests/Feature/ArchitectureTest.php` fails the build if a module imports one that is downstream of it, or if module registration order breaks the direction. When a module needs something from downstream it defines a contract in the upstream module and asks `Modules::providing(...)`.

## Run it locally

You need PHP 8.3+ (with `gd`, `mbstring`, `zip`, `pdo_sqlite` or `pdo_mysql`), Composer, Node 22+ and pnpm.

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed        # modules' own data + demo data (not in production)
pnpm install && pnpm build
php artisan storage:link
php artisan serve                       # or: composer dev  (server + vite)
```

SQLite is the default for local work. To use MySQL set `DB_CONNECTION=mysql` and the `DB_*` values in `.env`.

Seeding takes about 30 seconds because every demo student and teacher gets a bcrypt hash.

## Sign-in addresses and demo logins

| Who | Address | Demo login (local only) |
| --- | --- | --- |
| Admin / accountant | `/login`, then `/admin` | `admin@example.com` / `password` (super admin), `accountant@example.com` / `password` |
| Teacher | `/login`, then `/teacher` | `teacher1@example.com` … `teacher5@example.com` / `password` |
| Student or guardian | `/student/login`, then `/portal` | student ID (for example `ARICM-2026-0001`) and date of birth as `ddmmyyyy`; a new password is required at first sign-in |
| Everyone | `/` public site, `/results` result search, `/admission` online form | none |

The demo data (30 students, 5 teachers with assignments, two weeks of diary, an exam with results, fee invoices and payments) is only seeded when `APP_ENV` is not `production`.

## Everyday commands

```bash
vendor/bin/pest                          # all tests (about 460)
vendor/bin/pest Modules/Fees             # one module
vendor/bin/pint --format agent           # fix code style
pnpm types:check                         # TypeScript
pnpm build                               # production assets into public/build
php artisan laramod:list                 # what each module provides
php artisan accounts:create-admin you@example.com   # first admin / reset a password (asks for the password)
php artisan fees:generate-monthly        # monthly invoices (idempotent)
php artisan diary:send-daily-summary     # the 6 PM diary SMS, run by hand
```

`composer ci:check` runs Pint, `tsc` and the tests.

## Deploying to cPanel

The app needs no Redis, no queue worker and no Node on the server: sessions and cache use files, the queue is `sync`, and the frontend is built on your computer.

### 1. Build on your computer

```bash
composer install --no-dev --optimize-autoloader
pnpm install && pnpm build
```

Zip the project **including `vendor/` and `public/build/`**, and leaving out `node_modules/`, `.env`, `.git`, `storage/logs/*` and any local `database/*.sqlite`. Composer needs `ext-gd` (mPDF); if your computer lacks it you can install with `--ignore-platform-req=ext-gd`, but then test PDFs on the server.

### 2. Create the site

1. In cPanel choose **PHP 8.3 or newer** (MultiPHP Manager) and enable `gd`, `mbstring`, `zip`, `fileinfo`, `pdo_mysql`, `intl` if offered.
2. Create a MySQL database and user (MySQL® Databases) and give the user all privileges.
3. Upload and extract the zip **outside** the public folder, for example `/home/USER/madrasa`.
4. Point the domain's **document root at `/home/USER/madrasa/public`** (Domains → Document Root). If the host will not let you change it, ask them to; do not copy `public/` into `public_html` and leave the rest where it can be browsed.
5. Create `.env` from `.env.example` and set at least:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_LOCALE=bn
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_LEVEL=warning
```

6. In cPanel's **Terminal** (or SSH) from the project folder:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force                     # roles and permissions, institute settings, grading scale, fee heads, diary phrases (no demo data in production)
php artisan accounts:create-admin you@example.com --name="Your Name"
php artisan storage:link                        # if the host blocks symlinks: ln -s ../storage/app/public public/storage
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

`APP_KEY` must never change after go-live: it signs the QR verification links on printed marksheets, receipts and ID cards, and encrypts sessions.

If the host gives you no terminal at all, run the first three commands once on your computer against the remote MySQL (if the host allows remote connections) or import a SQL dump made from a local MySQL.

### 3. The one cron job

In **Cron Jobs** add a single entry, every minute:

```
* * * * * cd /home/USER/madrasa && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Everything scheduled hangs off it: late fines (01:00), monthly invoices on the 1st (06:00), fee SMS reminders (09:00) and the diary SMS (18:00). The fee and diary jobs do nothing until you switch them on in their settings. Use the PHP binary path your host shows for cron (`which php`, or the one under `/opt/cpanel/ea-php83/root/usr/bin/php`).

### 4. Folders that must be writable

`storage/` and `bootstrap/cache/` (permissions 775, owned by the PHP user). After an update that changes configuration, run `php artisan config:clear` and cache again.

### Updating later

Build again on your computer, upload over the old files (keep `.env` and `storage/`), then `php artisan migrate --force` and `php artisan config:cache route:cache view:cache`.

## Settings you will want to change

Admin → **Settings**: institute name, address, phones, logo, principal's message and signature, admit card rules, "hide results until fees are paid", "let teachers enter their own marks".

Other settings live next to the feature: **Fees → Settings** (due day, late fine, manual payment methods, gateway on/off, SMS), **Diary → Settings** (edit window after publishing, how far back a diary may be written, attachments, SMS), **Academic → Holidays** (weekly holiday, default Friday, and the holiday list).

### SMS

`SMS_DRIVER=log` (default) only writes messages to `storage/logs`. For real delivery set `SMS_DRIVER=bulk` and fill `SMS_BULK_URL`, `SMS_BULK_API_KEY`, `SMS_BULK_SENDER_ID`. `Modules/Core/Services/Sms/BulkSmsGateway.php` is a **placeholder shaped like a typical Bangladeshi bulk-SMS HTTP API**: check it against your provider's documentation and adjust the request before relying on it.

### Payment gateways

Credentials come from `.env` only: `SSLCOMMERZ_STORE_ID`, `SSLCOMMERZ_STORE_PASSWORD`, `SSLCOMMERZ_SANDBOX`, `BKASH_APP_KEY`, `BKASH_APP_SECRET`, `BKASH_USERNAME`, `BKASH_PASSWORD`, `BKASH_SANDBOX`. Keep `*_SANDBOX=true` until a sandbox payment has completed end to end, then switch the flag and enable the gateway in Fees → Settings. Manual payments (bKash/Nagad/bank transaction ID with an accountant approving) work without any gateway.

## Backups

There are two things to back up: the **database** and **`storage/app/public`** (logo, student photos, gallery, notice attachments, diary attachments, payment screenshots). Everything else is in the code.

- cPanel → **Backup** → download a full backup, or at least a MySQL database backup, and keep copies off the server.
- A nightly database dump from cron is cheap insurance: `mysqldump --single-transaction -u USER -p'PASS' DBNAME | gzip > /home/USER/backups/madrasa-$(date +\%F).sql.gz`, then delete files older than 30 days.
- Back up `.env` separately and keep it safe: it holds `APP_KEY`.
- Test a restore on a spare database now and then; an untested backup is a hope, not a backup.

## Things to know before relying on it

- **Image processing needs `ext-gd` (or Imagick).** Without it, uploads are stored as they are (no resize or thumbnails) and mPDF cannot embed PNG images. Check the PHP extensions on the host.
- **PDFs use Hind Siliguri (Bangla) and XB Riyaz (Arabic)**, bundled in `Modules/Core/resources/fonts` (see the README there for licences). Regenerating a marksheet, receipt, admit card or ID card in another font is a one-place change in `PdfService`.
- **The admin's own chrome ("Sign out", "Page not found") is English**, because it comes from the laramod-admin package. All application text is translated through `lang/bn` and `lang/en` in each module.
- **The React admin and the payment gateway drivers have not been exercised in a real browser or against live gateways in this repository**: the tests cover the API, services, Blade pages and the gateway flow against faked HTTP. Click through the admin and run a sandbox payment before going live.
- Result processing treats missing marks as zero and flags the result incomplete; marks are locked while a result is published (unpublish to correct).
- The online admission form is rate limited and has a honeypot; result search is rate limited. Keep `APP_DEBUG=false` in production.
