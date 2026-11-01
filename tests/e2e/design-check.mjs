#!/usr/bin/env node
/**
 * Browser check of every surface against the design tokens: pages load, no console errors or failed requests, no
 * horizontal overflow on a phone, the design's fonts and colours are what the browser actually computes, and the
 * main interactions work. Screenshots land in storage/e2e-screenshots/ for a side-by-side look with design/*.dc.html.
 *
 *   php artisan serve --port=8000 &                       # app with the demo data seeded (php artisan migrate:fresh --seed)
 *   pnpm build                                            # the bundle the pages load
 *   node tests/e2e/design-check.mjs [--base=http://127.0.0.1:8000] [--only=public,admin,admin-website,teacher,portal,brand,hero]
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
// 'admin-website' edits the demo database (texts, a price, a menu), so it only runs when asked for with --only.
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

/** A 1x1 PNG, enough for the upload endpoint (it re-encodes the picture). */
const TINY_PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');

/** The Website admin page: edit a text, upload a picture, add/reorder/delete an item, a price, a section and a menu link. */
async function websiteAdminFlow(browser, login, size) {
    const context = await browser.newContext({ viewport: SIZES[size], locale: 'en-US', storageState: login.state });
    await context.addCookies([{ name: 'locale', value: 'en', url: base }]);
    const page = await context.newPage();
    const problems = [];
    page.on('console', (m) => m.type() === 'error' && !/favicon|fonts\.(googleapis|gstatic)/.test(m.text()) && problems.push(`console: ${m.text().slice(0, 160)}`));
    page.on('pageerror', (e) => problems.push(`pageerror: ${e.message.slice(0, 160)}`));
    page.on('response', (r) => r.status() >= 400 && r.url().includes('/api/') && problems.push(`HTTP ${r.status()}: ${r.url().slice(0, 100)}`));
    const label = (what) => `admin-website [${size}] ${what}`;
    const shot = (name) => page.screenshot({ path: join(shots, `admin-website-${name}_${size}.png`), fullPage: true });
    const tab = (name) => page.getByRole('tab', { name });
    const marker = `Hello ${size} ${Date.now() % 100000}`;

    await page.goto(base + '/locale/en', { waitUntil: 'load' });
    await page.goto(base + '/admin/website', { waitUntil: 'load' });
    await page.getByText('Hero').first().waitFor();
    record((await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)) <= 1, label('has no horizontal overflow'));
    await shot('home');

    // A text: edit, save, confirm it reloads from the server and shows on the public page.
    const arabic = page.locator('input[id="hero.tag1-en"]');
    await arabic.fill(marker);
    record(await page.getByText('1 unsaved').isVisible(), label('shows the unsaved indicator'));
    await page.getByRole('button', { name: 'Save changes' }).click();
    await page.getByText('Saved', { exact: false }).first().waitFor({ timeout: 5000 }).catch(() => {});
    await page.reload({ waitUntil: 'load' });
    await page.locator('input[id="hero.tag1-en"]').waitFor();
    record((await page.locator('input[id="hero.tag1-en"]').inputValue()) === marker, label('keeps a saved text after reload'));
    const publicPage = await context.newPage();
    await publicPage.goto(base + '/', { waitUntil: 'load' });
    record((await publicPage.getByText(marker).count()) > 0, label('shows the edited text on the public site'));
    await publicPage.close();

    // A picture: upload, then reset to the default.
    await page.locator('#img-hero_image').setInputFiles({ name: 'tiny.png', mimeType: 'image/png', buffer: TINY_PNG });
    await page.getByText('Your picture is shown on the website.').waitFor({ timeout: 8000 }).then(() => record(true, label('uploads a picture')), () => record(false, label('uploads a picture')));
    await page.getByRole('button', { name: 'Use the standard picture' }).click();
    await page.getByText('The standard picture is shown', { exact: false }).waitFor({ timeout: 8000 }).then(() => record(true, label('resets a picture to the default')), () => record(false, label('resets a picture to the default')));

    // An item: add, reorder, toggle, delete.
    const rows = page.locator('[data-testid=item-programs]');
    const before = await rows.count();
    await page.getByRole('button', { name: 'Add' }).first().click();
    await page.locator('#i-title_bn').fill('পরীক্ষামূলক পয়েন্ট');
    await page.locator('#i-title_en').fill('Test point');
    await page.getByRole('dialog').getByRole('button', { name: 'Save', exact: true }).click();
    await page.waitForFunction((n) => document.querySelectorAll('[data-testid=item-programs]').length === n, before + 1, { timeout: 8000 }).then(() => record(true, label('adds an item')), () => record(false, label('adds an item')));
    await rows.last().getByRole('button', { name: 'Move up' }).click();
    await page.waitForTimeout(600);
    record((await rows.nth(before - 1).innerText()).includes('Test point'), label('reorders an item'));
    await rows.nth(before - 1).getByRole('switch').click();
    await page.waitForTimeout(600);
    record((await rows.nth(before - 1).innerText()).includes('Hidden'), label('hides an item'));
    await rows.nth(before - 1).getByRole('button', { name: 'Delete' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Delete' }).click();
    await page.waitForFunction((n) => document.querySelectorAll('[data-testid=item-programs]').length === n, before, { timeout: 8000 }).then(() => record(true, label('deletes an item')), () => record(false, label('deletes an item')));
    await shot('home-lists');

    // A price.
    await tab('Admission and prices').click();
    const price = page.getByLabel(/ - /).first();
    await price.waitFor();
    await price.fill(String(1000 + (Date.now() % 9000)));
    await page.getByRole('button', { name: 'Save changes' }).last().click();
    await page.waitForFunction(() => !document.body.innerText.includes('Not saved'), null, { timeout: 8000 }).then(() => record(true, label('saves a price')), () => record(false, label('saves a price')));
    await shot('admission');

    // A section toggle.
    await tab('Menu and sections').click();
    const section = page.locator('[data-testid=section-row]').first();
    await section.getByRole('switch').click();
    await page.getByRole('button', { name: 'Save changes' }).first().click();
    await page.waitForTimeout(800);
    record((await section.innerText()).includes('Hidden'), label('hides a home section'));
    await section.getByRole('switch').click();
    await page.getByRole('button', { name: 'Save changes' }).first().click();
    await page.waitForTimeout(800);

    // A menu link, then back to the standard menu.
    await page.getByRole('button', { name: 'Customize menu' }).click();
    await page.locator('[data-testid=menu-header]').first().waitFor();
    await page.getByRole('button', { name: 'Add' }).last().click();
    await page.locator('#m-bn').fill('পরীক্ষা');
    await page.locator('#m-en').fill('Exam link');
    await page.getByRole('dialog').getByRole('button', { name: 'Save', exact: true }).click();
    await page.getByRole('button', { name: 'Save changes' }).last().click();
    await page.getByText('Unsaved changes').waitFor({ state: 'hidden', timeout: 8000 });
    const publicMenu = await context.newPage();
    await publicMenu.goto(base + '/', { waitUntil: 'load' });
    record((await publicMenu.getByText('Exam link').count()) > 0, label('shows the new menu link on the public site'));
    await publicMenu.close();
    await shot('menu');
    await page.getByRole('button', { name: 'Restore standard menu' }).first().click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Restore standard menu' }).click();
    await page.getByText('The standard menu is in use').waitFor({ timeout: 8000 }).then(() => record(true, label('restores the standard menu')), () => record(false, label('restores the standard menu')));

    // Put the edited text back so the demo data stays as it was.
    await tab('Home').click();
    await page.locator('input[id="hero.tag1-en"]').fill('');
    await page.getByRole('button', { name: 'Save changes' }).first().click();
    await page.waitForTimeout(800);

    await page.goto(base + '/admin/settings', { waitUntil: 'load' });
    await page.getByLabel('Phone 5').waitFor();
    await shot('settings');

    record(problems.length === 0, label('is free of console/network errors'), problems.slice(0, 3).join(' | '));
    await context.close();
}

if (only.includes('admin-website')) {
    const login = await signIn(browser, '/login', 'email', 'admin@example.com', 'password');
    for (const size of ['desktop', 'phone']) {
        await websiteAdminFlow(browser, login, size);
    }
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

// 'brand' (only with --only=brand): header brand block per breakpoint, the program line, and reduced motion.
if (only.includes('brand')) {
    for (const [size, lang, theme] of [['phone', 'bn', 'light'], ['phone', 'en', 'dark'], ['tablet', 'bn', 'light'], ['desktop', 'en', 'light'], ['desktop', 'bn', 'dark']]) {
        for (const path of ['/', '/login']) {
            const label = `brand ${path} [${size}/${theme}/${lang}]`;
            const context = await browser.newContext({ viewport: SIZES[size], locale: lang === 'bn' ? 'bn-BD' : 'en-US', reducedMotion: 'reduce' });
            const page = await context.newPage();
            await page.goto(`${base}/locale/${lang}`, { waitUntil: 'load' });
            await page.goto(base + path, { waitUntil: 'load' });
            await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
            await page.waitForTimeout(500);
            await page.screenshot({ path: join(shots, `brand${path === '/' ? '-home' : path.replace('/', '-')}-${size}-${theme}-${lang}.png`) });

            const info = await page.evaluate(() => {
                const text = document.querySelector('.pub-brand-text');
                const program = document.body.innerText;
                return {
                    hasBrandText: !!text,
                    brandVisible: text ? getComputedStyle(text).display !== 'none' : false,
                    label: document.querySelector('header a[aria-label]')?.getAttribute('aria-label') ?? null,
                    program: document.querySelector('.pub-program')?.textContent?.trim() ?? '',
                    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
                    running: document.getAnimations().filter((a) => a.playState === 'running' && /^(ico-|arm-)/.test(a.animationName ?? '')).length,
                    text: program.length,
                };
            });

            if (path === '/') {
                record(info.hasBrandText, `${label} header has the brand text block`);
                record(size === 'phone' ? !info.brandVisible : info.brandVisible, `${label} brand text is ${size === 'phone' ? 'hidden' : 'visible'}`);
                record(!!info.label, `${label} brand link has an aria-label`, String(info.label));
            }
            record(await page.evaluate(() => document.documentElement.lang) === lang, `${label} renders in ${lang}`);
            record(info.program.length > 0, `${label} shows the program line`);
            record(info.overflow <= 1, `${label} has no horizontal overflow`, `${info.overflow}px`);
            record(info.running === 0, `${label} runs no icon/brain animations under prefers-reduced-motion`, `${info.running} running`);
            await context.close();
        }
    }
}

// 'hero' (only with --only=hero): the poster hero per breakpoint, language and theme.
if (only.includes('hero')) {
    for (const lang of ['bn', 'en']) {
        for (const theme of ['light', 'dark']) {
            for (const size of ['phone', 'tablet', 'desktop']) {
                const label = `hero [${size}/${theme}/${lang}]`;
                const context = await browser.newContext({ viewport: SIZES[size], locale: lang === 'bn' ? 'bn-BD' : 'en-US' });
                const page = await context.newPage();
                await page.goto(`${base}/locale/${lang}`, { waitUntil: 'load' });
                await page.goto(base + '/', { waitUntil: 'networkidle' });
                await page.evaluate((t) => document.documentElement.setAttribute('data-theme', t), theme);
                await page.waitForTimeout(500);
                await page.screenshot({ path: join(shots, `hero-${size}-${theme}-${lang}.png`), fullPage: false });
                if (size !== 'tablet' && theme === 'light') {
                    await page.screenshot({ path: join(shots, `hero-full-${size}-${lang}.png`), clip: { x: 0, y: 0, width: SIZES[size].width, height: size === 'phone' ? 2300 : 1250 }, fullPage: true });
                }

                const info = await page.evaluate(() => {
                    const visible = (selector) => { const el = document.querySelector(selector); return !!el && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().height > 0; };
                    const bar = document.querySelector('.pub-callbar');
                    return {
                        h1: document.querySelector('h1')?.textContent?.trim() ?? '',
                        h1Count: document.querySelectorAll('h1').length,
                        name: document.querySelector('meta[property="og:title"]')?.getAttribute('content') ?? '',
                        ribbon: visible('.pub-hero-ribbon'), free: visible('.pub-hero-free'), founder: visible('.pub-hero-founder'), pills: document.querySelectorAll('.pub-hero-pill').length,
                        banner: visible('.pub-hero-banner'), call: visible('.pub-hero-callpill'),
                        bar: bar ? getComputedStyle(bar).display !== 'none' && getComputedStyle(bar).position === 'fixed' : false,
                        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
                        brainAnimating: document.getAnimations().some((a) => a.playState === 'running' && /^arm-/.test(a.animationName ?? '')),
                    };
                });

                record(info.h1Count === 1 && info.h1.length > 0 && info.h1 === info.name, `${label} H1 is the institute name`, `${info.h1} / ${info.name}`);
                record(info.ribbon && info.free && info.founder && info.banner && info.call, `${label} shows ribbon, banner, free-class badge, founder card and call pill`, JSON.stringify(info));
                record(info.pills === 4, `${label} shows four benefit pills`, String(info.pills));
                record(info.overflow <= 1, `${label} has no horizontal overflow`, `${info.overflow}px`);
                record(size === 'phone' ? info.bar : !info.bar, `${label} fixed call bar is ${size === 'phone' ? 'shown' : 'hidden'}`);
                record(info.brainAnimating, `${label} brain animates`);
                await context.close();
            }
        }
    }

    const quiet = await browser.newContext({ viewport: SIZES.phone, reducedMotion: 'reduce' });
    const quietPage = await quiet.newPage();
    await quietPage.goto(base + '/', { waitUntil: 'networkidle' });
    const running = await quietPage.evaluate(() => document.getAnimations().filter((a) => a.playState === 'running' && /^(ico-|arm-)/.test(a.animationName ?? '') && a.animationName !== 'arm-tick').length);
    record(running === 0, 'hero runs no animations under prefers-reduced-motion', `${running} running`);
    await quiet.close();
}

await browser.close();

console.log(`\n${passes.length} checks passed, ${failures.length} failed. Screenshots: ${shots}`);
for (const failure of failures) {
    console.log(`  ✗ ${failure}`);
}
process.exit(failures.length === 0 ? 0 : 1);
