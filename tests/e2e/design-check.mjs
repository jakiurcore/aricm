#!/usr/bin/env node
/**
 * Browser check of every surface against the design tokens: pages load, no console errors or failed requests, no
 * horizontal overflow on a phone, the design's fonts and colours are what the browser actually computes, and the
 * main interactions work. Screenshots land in storage/e2e-screenshots/ for a side-by-side look with design/*.dc.html.
 *
 *   php artisan serve --port=8000 &                       # app with the demo data seeded (php artisan migrate:fresh --seed)
 *   pnpm build                                            # the bundle the pages load
 *   node tests/e2e/design-check.mjs [--base=http://127.0.0.1:8000] [--only=public,admin,teacher,portal]
 *
 * Playwright is not a project dependency: the script resolves it from NODE_PATH, e.g.
 *   NODE_PATH=$(mise where npm:playwright)/node_modules node tests/e2e/design-check.mjs
 * (or `pnpm dlx playwright install chromium` and a global install). Exit code 1 when any check fails.
 */
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const { chromium } = require('playwright');

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const base = args.base ?? 'http://127.0.0.1:8000';
const only = (args.only ?? 'public,admin,teacher,portal').split(',');
const shots = join(root, 'storage', 'e2e-screenshots');
mkdirSync(shots, { recursive: true });

// Design tokens (design/madrasa-theme.css) the pages must resolve to.
const TOKENS = { bgLight: 'rgb(250, 250, 245)', brand: 'rgb(11, 107, 58)' };
const SIZES = { desktop: { width: 1440, height: 900 }, tablet: { width: 768, height: 1024 }, phone: { width: 390, height: 844 } };

const failures = [];
const passes = [];
const record = (ok, label, detail = '') => (ok ? passes.push(label) : failures.push(`${label}${detail ? ' — ' + detail : ''}`));

/** Demo-data preparation the browser flows rely on (idempotent). */
function artisan(code) {
    return execFileSync('php', ['artisan', 'tinker', '--execute', code], { cwd: root, encoding: 'utf8' }).trim();
}
function prepare() {
    artisan('Modules\\Students\\Models\\Student::query()->update(["must_change_password" => false]);');
    artisan('app(Modules\\Core\\Services\\Settings::class)->set("teacher_marks_entry_enabled", true);');
    const dob = artisan('echo Modules\\Students\\Models\\Student::orderBy("id")->first()->date_of_birth->format("dmY");');
    const id = artisan('echo Modules\\Students\\Models\\Student::orderBy("id")->first()->student_id;');

    return { studentId: id, studentPassword: dob };
}

async function visit(browser, { name, path, size = 'desktop', theme = 'light', lang = 'bn', login, status = 200, expect: expectations = {} }) {
    const context = await browser.newContext({ viewport: SIZES[size], locale: lang === 'bn' ? 'bn-BD' : 'en-US', storageState: login?.state });
    await context.addCookies([{ name: 'locale', value: lang, url: base }]);
    const page = await context.newPage();
    const problems = [];
    page.on('console', (m) => m.type() === 'error' && !(status === 404 && /404/.test(m.text())) && !/favicon|Failed to load resource.*(fonts|googleapis|gstatic)/.test(m.text()) && problems.push(`console: ${m.text().slice(0, 160)}`));
    page.on('pageerror', (e) => problems.push(`pageerror: ${e.message.slice(0, 160)}`));
    page.on('requestfailed', (r) => !/fonts\.(googleapis|gstatic)/.test(r.url()) && problems.push(`request failed: ${r.url().slice(0, 100)}`));
    page.on('response', (r) => r.status() >= 500 && problems.push(`HTTP ${r.status()}: ${r.url().slice(0, 100)}`));

    const label = `${name} [${size}/${theme}/${lang}]`;
    const response = await page.goto(base + path, { waitUntil: 'load' }).catch((e) => problems.push(`goto: ${e.message}`));
    await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
    await page.waitForTimeout(900);

    record(status === 200 ? (response?.status() ?? 0) < 400 : response?.status() === status, `${label} responds with ${status}`, `status ${response?.status()}`);

    const metrics = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
        bg: getComputedStyle(document.body).backgroundColor,
        font: getComputedStyle(document.body).fontFamily,
        title: document.title,
        h1: document.querySelector('h1')?.textContent?.trim().slice(0, 60) ?? null,
    }));

    record(metrics.overflow <= 1, `${label} has no horizontal overflow`, `${metrics.overflow}px too wide`);
    if (theme === 'light' && !expectations.skipBackground) {
        record(metrics.bg === TOKENS.bgLight || metrics.bg === 'rgb(255, 255, 255)', `${label} uses the design background`, metrics.bg);
    }
    record(/Noto Sans Bengali|Hind Siliguri|Poppins/.test(metrics.font), `${label} uses the design font stack`, metrics.font);
    for (const text of expectations.text ?? []) {
        record((await page.locator(`text=${text}`).count()) > 0, `${label} shows "${text}"`);
    }
    record(problems.length === 0, `${label} is free of console/network errors`, problems.slice(0, 3).join(' | '));

    await page.screenshot({ path: join(shots, `${name.replace(/\W+/g, '-')}_${size}_${theme}_${lang}.png`), fullPage: true });
    await context.close();
}

async function signIn(browser, loginPath, field, user, password) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(base + loginPath);
    await page.fill(`input[name=${field}]`, user);
    await page.fill('input[name=password]', password);
    await Promise.all([page.waitForLoadState('load'), page.click('button[type=submit]')]);
    const state = await context.storageState();
    await context.close();

    return { state };
}

const browser = await chromium.launch();
const demo = prepare();

if (only.includes('public')) {
    for (const size of ['desktop', 'tablet', 'phone']) {
        for (const [name, path, text] of [['home', '/', ['ভর্তি']], ['about', '/about'], ['academics', '/academics'], ['admission', '/admission'], ['notices', '/notices'], ['gallery', '/gallery'], ['results', '/results'], ['contact', '/contact'], ['login', '/login', [], true], ['student-login', '/student/login', [], true], ['not-found', '/no-such-page']]) {
            await visit(browser, { name: `public-${name}`, path, size, status: name === 'not-found' ? 404 : 200, expect: { text: text ?? [] } });
        }
    }
    await visit(browser, { name: 'public-home', path: '/', theme: 'dark' });
    await visit(browser, { name: 'public-home', path: '/', lang: 'en' });
    await visit(browser, { name: 'public-home', path: '/', size: 'phone', lang: 'en' });
}

if (only.includes('admin')) {
    const login = await signIn(browser, '/login', 'email', 'admin@example.com', 'password');
    for (const size of ['desktop', 'tablet', 'phone']) {
        for (const [name, path] of [['dashboard', '/admin/dashboard'], ['students', '/admin/students'], ['classes', '/admin/academic/classes'], ['exams', '/admin/exams'], ['fees', '/admin/fees/collect'], ['invoices', '/admin/fees/invoices'], ['teachers', '/admin/teachers'], ['diary', '/admin/diary'], ['notices', '/admin/notices'], ['settings', '/admin/settings']]) {
            await visit(browser, { name: `admin-${name}`, path, size, login, expect: { skipBackground: true } });
        }
    }
    await visit(browser, { name: 'admin-dashboard', path: '/admin/dashboard', theme: 'dark', login, expect: { skipBackground: true } });
    await visit(browser, { name: 'admin-dashboard', path: '/admin/dashboard', lang: 'en', login, expect: { skipBackground: true } });
}

if (only.includes('teacher')) {
    const login = await signIn(browser, '/login', 'email', 'teacher1@example.com', 'password');
    for (const size of ['phone', 'desktop']) {
        for (const [name, path] of [['home', '/teacher'], ['diary', '/teacher/diary'], ['class-day', '/teacher/class-day'], ['marks', '/teacher/marks']]) {
            await visit(browser, { name: `teacher-${name}`, path, size, login });
        }
    }
    await visit(browser, { name: 'teacher-home', path: '/teacher', theme: 'dark', size: 'phone', login });
}

if (only.includes('portal')) {
    const login = await signIn(browser, '/student/login', 'student_id', demo.studentId, demo.studentPassword);
    for (const size of ['phone', 'desktop']) {
        for (const [name, path] of [['dashboard', '/portal'], ['profile', '/portal/profile'], ['routine', '/portal/routine'], ['admit', '/portal/admit-card'], ['fees', '/portal/fees'], ['pay', '/portal/fees/pay'], ['ledger', '/portal/fees/ledger'], ['notices', '/portal/notices'], ['results', '/portal/results'], ['diary', '/portal/diary']]) {
            await visit(browser, { name: `portal-${name}`, path, size, login });
        }
    }
    await visit(browser, { name: 'portal-dashboard', path: '/portal', theme: 'dark', size: 'phone', login });
}

await browser.close();

console.log(`\n${passes.length} checks passed, ${failures.length} failed. Screenshots: ${shots}`);
for (const failure of failures) {
    console.log(`  ✗ ${failure}`);
}
process.exit(failures.length === 0 ? 0 : 1);
