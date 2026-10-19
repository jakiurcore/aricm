import { Link } from 'react-router';

import { menuBadge } from '@core/js/menu';
import { t } from '@core/js/i18n';

import type { ShellGroup } from './menu';

interface SidebarProps {
    groups: ShellGroup[];
    active: string | null;
    /** Labels are shown: the wide sidebar, and the drawer of a phone. False is the icon-only 72px rail. */
    labels: boolean;
    phone: boolean;
    onNavigate: () => void;
}

const BRAND_LOGO = '/images/logo.jpeg';

/** The green sidebar of the design: logo and name, then every group heading and entry, 44px tall each. */
export function Sidebar({ groups, active, labels, phone, onNavigate }: SidebarProps) {
    return (
        <aside
            id="admin-sidebar"
            className="adm-sidebar flex flex-col overflow-y-auto"
            style={{
                position: phone ? 'fixed' : 'sticky',
                inset: '0 auto 0 0',
                top: 0,
                zIndex: 50,
                width: phone ? 280 : 'auto',
                height: '100vh',
                background: 'var(--green-900)',
                color: '#E6F2EA',
                boxShadow: 'var(--shadow-lg)',
            }}
        >
            <div className="flex items-center" style={{ gap: 10, padding: '14px 14px 10px' }}>
                <img src={BRAND_LOGO} alt="" className="shrink-0 rounded-full bg-white" style={{ width: 40, height: 40 }} />
                {labels && (
                    <span className="grid flex-1" style={{ lineHeight: 1.3 }}>
                        <strong className="font-head text-white" style={{ fontSize: 14 }}>
                            {t('core::admin.brand')}
                        </strong>
                        <span style={{ fontSize: 12, color: 'var(--yellow-400)' }}>{t('core::admin.shell.admin_panel')}</span>
                    </span>
                )}
            </div>

            <nav className="grid" style={{ gap: 2, padding: '6px 8px 16px' }} aria-label={t('core::admin.shell.menu')}>
                {groups.map((group) => (
                    <div key={group.key} className="grid" style={{ gap: 2 }}>
                        {labels && (
                            <span style={{ padding: '12px 10px 4px', fontSize: 12, fontWeight: 700, color: 'rgba(230,242,234,.6)', letterSpacing: '.02em' }}>{group.label}</span>
                        )}
                        {group.items.map((item) => {
                            const Badge = menuBadge(item.path);
                            const Icon = item.icon;

                            return (
                                <Link
                                    key={item.path}
                                    to={item.path}
                                    title={item.label}
                                    aria-current={item.path === active ? 'page' : undefined}
                                    onClick={onNavigate}
                                    className="adm-nav-item flex items-center"
                                    style={{ gap: 12, minHeight: 44, padding: '0 12px', borderRadius: 12, fontWeight: 600, fontSize: 15, color: item.path === active ? '#FFD21F' : '#E6F2EA', textDecoration: 'none' }}
                                >
                                    <span className="flex shrink-0 items-center justify-center" style={{ width: 22 }} aria-hidden="true">
                                        {item.glyph ?? <Icon className="size-[18px]" />}
                                    </span>
                                    {labels && <span className="flex-1">{item.label}</span>}
                                    {Badge && <Badge />}
                                </Link>
                            );
                        })}
                    </div>
                ))}
            </nav>
        </aside>
    );
}
