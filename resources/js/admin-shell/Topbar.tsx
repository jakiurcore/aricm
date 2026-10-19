import { ExternalLinkIcon, LogOutIcon } from 'lucide-react';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { logoutUrl, viewer } from '@admin/js/lib/boot';
import { locale, t } from '@core/js/i18n';

import { useTheme } from './theme';

interface TopbarProps {
    title: string;
    onToggleMenu: () => void;
    menuOpen: boolean;
}

/** A form post rather than a request: where the viewer lands afterwards is the application's call. */
function signOut(url: string): void {
    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const form = document.createElement('form');
    form.method = 'post';
    form.action = url;
    form.append(token);

    document.body.append(form);
    form.submit();
}

function initial(name: string): string {
    return Array.from(name.trim())[0]?.toUpperCase() ?? '?';
}

/** Where the locale switch points: the route sets the session and sends the viewer back, so the page reloads in the new language. */
function localeUrl(code: 'bn' | 'en'): string {
    return `/locale/${code}`;
}

function LanguageSwitch() {
    const current = locale();
    const options: Array<{ code: 'bn' | 'en'; label: string; english: boolean }> = [
        { code: 'bn', label: 'বাং', english: false },
        { code: 'en', label: 'EN', english: true },
    ];

    return (
        <div role="group" aria-label={t('core::admin.shell.language')} className="flex" style={{ padding: 3, borderRadius: 999, background: 'var(--surface-2)', border: '1px solid var(--line)' }}>
            {options.map((option) => {
                const active = current === option.code;

                return (
                    <a
                        key={option.code}
                        href={localeUrl(option.code)}
                        hrefLang={option.code}
                        aria-current={active ? 'true' : undefined}
                        className="inline-flex items-center justify-center"
                        style={{
                            minWidth: 40,
                            height: 34,
                            borderRadius: 999,
                            fontWeight: 700,
                            fontSize: 13,
                            fontFamily: option.english ? 'Poppins, sans-serif' : undefined,
                            background: active ? 'var(--green-700)' : 'transparent',
                            color: active ? '#fff' : 'var(--ds-muted)',
                            textDecoration: 'none',
                        }}
                    >
                        {option.label}
                    </a>
                );
            })}
        </div>
    );
}

/** The design's top bar: menu toggle, page title, BN/EN switch, theme toggle and the avatar (with the account menu). */
export function Topbar({ title, onToggleMenu, menuOpen }: TopbarProps) {
    const [theme, toggleTheme] = useTheme();

    return (
        <header className="adm-topbar sticky top-0 z-30 flex items-center" style={{ background: 'var(--surface)', borderBottom: '1px solid var(--line)', padding: '10px 16px', gap: 10 }}>
            <button
                type="button"
                onClick={onToggleMenu}
                aria-label={t('core::admin.shell.menu')}
                aria-expanded={menuOpen}
                aria-controls="admin-sidebar"
                className="inline-flex shrink-0 cursor-pointer items-center justify-center"
                style={{ width: 44, height: 44, borderRadius: 12, border: '1px solid var(--line)', background: 'var(--surface)', fontSize: 18, color: 'var(--ink)' }}
            >
                ☰
            </button>

            <strong className="min-w-0 flex-1 truncate" style={{ fontSize: 19, color: 'var(--heading)' }}>
                {title}
            </strong>

            <LanguageSwitch />

            <button
                type="button"
                onClick={toggleTheme}
                aria-label={theme === 'dark' ? t('core::admin.shell.theme_light') : t('core::admin.shell.theme_dark')}
                title={t('core::admin.shell.theme')}
                className="inline-flex shrink-0 cursor-pointer items-center justify-center"
                style={{ width: 44, height: 44, borderRadius: 999, border: '1px solid var(--line)', background: 'var(--surface)', color: 'var(--ink)' }}
            >
                {theme === 'dark' ? '☀' : '☾'}
            </button>

            {viewer && (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        aria-label={t('core::admin.shell.account')}
                        className="grid shrink-0 cursor-pointer place-items-center"
                        style={{ width: 40, height: 40, borderRadius: '50%', background: 'var(--navy-800)', color: '#fff', fontWeight: 700 }}
                    >
                        {initial(viewer.name)}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="min-w-56">
                        <DropdownMenuLabel className="grid gap-0.5 font-normal">
                            <span className="truncate text-[15px] font-bold text-foreground">{viewer.name}</span>
                            <span className="truncate text-[13px] text-muted-foreground">{viewer.email}</span>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem render={<a href="/" target="_blank" rel="noreferrer" />}>
                            <ExternalLinkIcon /> {t('core::admin.shell.view_site')}
                        </DropdownMenuItem>
                        {logoutUrl && (
                            <DropdownMenuItem onClick={() => signOut(logoutUrl!)}>
                                <LogOutIcon /> {t('core::admin.shell.sign_out')}
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </header>
    );
}
