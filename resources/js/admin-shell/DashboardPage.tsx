import { useCallback, useEffect, useState, type CSSProperties, type ReactNode } from 'react';
import { Link } from 'react-router';

import { DashboardWidgets } from '@admin/js';
import { viewer } from '@admin/js/lib/boot';
import { getMenuGroups } from '@admin/js/lib/modular';
import { api } from '@core/js/api';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { locale, t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Overview {
    students: { total: number; session: string | null; by_class: Array<{ id: number; name: string; count: number }> } | null;
    fees: {
        month: string;
        expected_this_month: number;
        collected_this_month: number;
        collected_session: number;
        collected_today: number;
        receipts_today: number;
        pending_amount: number;
        outstanding: number;
        students_with_dues: number;
        top_dues: Array<{ class: string; due: number }>;
    } | null;
    exam: { count: number; next: { id: number; name: string; start_date: string | null } | null } | null;
    pending_verifications: number | null;
    activity: Array<{ text: string; at: string; tone: 'green' | 'yellow' | 'navy' | 'red' }>;
}

const surface: CSSProperties = { borderRadius: 20, background: 'var(--surface)', border: '1px solid var(--line)', boxShadow: 'var(--shadow-sm)' };
const TONES = { green: 'var(--green-500)', yellow: 'var(--yellow-400)', navy: 'var(--navy-800)', red: 'var(--red-600)' };

/** "৳১,৮৪,৫০০" with the space the design puts after the sign on the KPI tiles. */
function spaced(poisha: number): string {
    return formatMoney(poisha).replace('৳', '৳ ');
}

function monthLabel(month: string): string {
    const [year, number] = month.split('-').map(Number);

    return new Date(year, number - 1, 1).toLocaleDateString(locale() === 'bn' ? 'bn-BD' : 'en-GB', { month: 'long', year: 'numeric' });
}

function ago(iso: string): string {
    const minutes = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000));
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return t('core::admin.dashboard.time.now');
    if (minutes < 60) return t('core::admin.dashboard.time.minutes', { count: bnDigits(minutes) });
    if (hours < 24) return t('core::admin.dashboard.time.hours', { count: bnDigits(hours) });
    if (days === 1) return t('core::admin.dashboard.time.yesterday');

    return t('core::admin.dashboard.time.days', { count: bnDigits(days) });
}

function Tile({ to, children, style }: { to?: string; children: ReactNode; style?: CSSProperties }) {
    const base: CSSProperties = { ...surface, padding: 18, display: 'grid', gap: 8, color: 'var(--ink)', minWidth: 0, textAlign: 'left', textDecoration: 'none', ...style };

    return to ? (
        <Link to={to} className="adm-card-link" style={base}>
            {children}
        </Link>
    ) : (
        <div style={base}>{children}</div>
    );
}

function Panel({ title, children, action, titleSize = 17, gap = 10 }: { title: string; children: ReactNode; action?: ReactNode; titleSize?: number; gap?: number }) {
    return (
        <section style={{ ...surface, boxShadow: 'none', padding: 18, display: 'grid', gap, alignContent: 'start' }}>
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 style={{ margin: 0, fontSize: titleSize, fontWeight: 700 }}>{title}</h3>
                {action}
            </div>
            {children}
        </section>
    );
}

function Bar({ value, color, height }: { value: number; color: string; height: number }) {
    return (
        <span style={{ height, borderRadius: 999, background: 'var(--surface-2)', overflow: 'hidden', display: 'block' }}>
            <span style={{ display: 'block', height: '100%', width: `${Math.max(0, Math.min(100, value))}%`, background: color, borderRadius: 999 }} />
        </span>
    );
}

function FeeRows({ data, pending }: { data: NonNullable<Overview['fees']>; pending: number }) {
    const percent = data.expected_this_month > 0 ? Math.min(100, Math.round((data.collected_this_month / data.expected_this_month) * 100)) : 0;
    const maxDue = Math.max(1, ...data.top_dues.map((row) => row.due));
    const reportsPath = can('fees.reports') ? '/fees/reports' : '/fees/invoices';

    return (
        <>
            <div className="grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 240px), 1fr))', gap: 12 }}>
                <Tile to={reportsPath} style={{ gridColumn: 'span 2' }}>
                    <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--ds-muted)' }}>{t('core::admin.dashboard.collected_vs_expected', { month: monthLabel(data.month) })}</span>
                    <span className="flex flex-wrap items-baseline" style={{ gap: 10 }}>
                        <strong className="font-head" style={{ fontSize: 28, color: 'var(--green-700)' }}>
                            {formatMoney(data.collected_this_month)}
                        </strong>
                        <span style={{ color: 'var(--ds-muted)' }}>/ {formatMoney(data.expected_this_month)}</span>
                        <span style={{ marginLeft: 'auto', fontWeight: 700 }}>{bnDigits(percent)}%</span>
                    </span>
                    <Bar value={percent} color="linear-gradient(90deg, var(--green-500), var(--green-700))" height={12} />
                </Tile>

                <Tile to={can('fees.reports') ? '/fees/reports' : '/fees/invoices'} style={{ gap: 4 }}>
                    <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--ds-muted)' }}>{t('core::admin.dashboard.today')}</span>
                    <strong className="font-head" style={{ fontSize: 28 }}>
                        {formatMoney(data.collected_today)}
                    </strong>
                    <span style={{ fontSize: 13, color: 'var(--ds-muted)' }}>{t('core::admin.dashboard.receipts', { count: bnDigits(data.receipts_today) })}</span>
                </Tile>

                {can('fees.verify') && (
                    <Tile to="/fees/verification" style={{ gap: 4, borderColor: 'var(--warn)' }}>
                        <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--warn)' }}>⏳ {t('core::admin.dashboard.pending')}</span>
                        <strong className="font-head" style={{ fontSize: 28 }}>
                            {bnDigits(pending)}
                        </strong>
                        <span style={{ fontSize: 13, color: 'var(--ds-muted)' }}>{t('core::admin.dashboard.pending_amount', { amount: formatMoney(data.pending_amount) })}</span>
                    </Tile>
                )}
            </div>

            {data.top_dues.length > 0 && (
                <Panel
                    title={t('core::admin.dashboard.top_dues')}
                    titleSize={16}
                    gap={8}
                    action={
                        <Link to="/fees/invoices" style={{ color: 'var(--green-700)', fontWeight: 700, textDecoration: 'none' }}>
                            {t('core::admin.dashboard.due_list')} →
                        </Link>
                    }
                >
                    {data.top_dues.map((row) => (
                        <div key={row.class} className="grid items-center" style={{ gridTemplateColumns: 'minmax(90px, 140px) 1fr auto', gap: 10, fontSize: 14 }}>
                            <span style={{ fontWeight: 600 }}>{row.class}</span>
                            <Bar value={(row.due / maxDue) * 100} color="var(--red-600)" height={8} />
                            <strong style={{ color: 'var(--danger)', whiteSpace: 'nowrap' }}>{formatMoney(row.due)}</strong>
                        </div>
                    ))}
                </Panel>
            )}
        </>
    );
}

function Kpi({ label, value, sub, glyph, bg, color }: { label: string; value: string; sub: string; glyph: string; bg: string; color: string }) {
    return (
        <div style={{ ...surface, padding: 18, display: 'grid', gap: 4 }}>
            <span className="flex items-center justify-between" style={{ fontSize: 14, fontWeight: 600, color: 'var(--ds-muted)' }}>
                {label}
                <span className="grid place-items-center" style={{ width: 36, height: 36, borderRadius: 12, background: bg }}>
                    {glyph}
                </span>
            </span>
            <strong className="font-head" style={{ fontSize: 32, lineHeight: 1.25, color }}>
                {value}
            </strong>
            <span style={{ fontSize: 13, color: 'var(--ds-muted)' }}>{sub}</span>
        </div>
    );
}

const QUICK: Array<{ key: string; glyph: string; path: string }> = [
    { key: 'new_student', glyph: '＋', path: '/students' },
    { key: 'exams', glyph: '📝', path: '/exams' },
    { key: 'fees', glyph: '৳', path: '/fees/collection' },
    { key: 'notices', glyph: '📢', path: '/notices' },
    { key: 'admit_cards', glyph: '🎫', path: '/exams' },
    { key: 'id_cards', glyph: '🪪', path: '/students' },
];

export function DashboardPage() {
    const [data, setData] = useState<Overview | null>(null);
    const [failed, setFailed] = useState(false);

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: Overview }>('/fees/overview')
            .then((response) => setData(response.data))
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!data) {
        return <LoadingState rows={4} />;
    }

    const offered = new Set(getMenuGroups(viewer).flatMap((group) => group.items.map((item) => item.path)));
    const quick = QUICK.filter((action) => offered.has(action.path));
    const students = data.students;
    const fees = data.fees;
    const classMax = Math.max(40, ...(students?.by_class.map((row) => row.count) ?? [0]));

    return (
        <div className="grid" style={{ gap: 16 }}>
            {fees && <FeeRows data={fees} pending={data.pending_verifications ?? 0} />}

            <div className="grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 220px), 1fr))', gap: 12 }}>
                {students && (
                    <Kpi
                        label={t('core::admin.dashboard.total_students')}
                        value={bnDigits(students.total)}
                        sub={t('core::admin.dashboard.total_students_sub', { session: bnDigits(students.session ?? '—'), count: bnDigits(students.total) })}
                        glyph="👥"
                        bg="var(--green-50)"
                        color="var(--heading)"
                    />
                )}
                {fees && <Kpi label={t('core::admin.dashboard.collected')} value={spaced(fees.collected_session)} sub={t('core::admin.dashboard.collected_sub')} glyph="৳" bg="var(--success-bg)" color="var(--success)" />}
                {fees && (
                    <Kpi label={t('core::admin.dashboard.due')} value={spaced(fees.outstanding)} sub={t('core::admin.dashboard.due_sub', { count: bnDigits(fees.students_with_dues) })} glyph="!" bg="var(--danger-bg)" color="var(--danger)" />
                )}
                {data.exam && (
                    <Kpi
                        label={t('core::admin.dashboard.exams')}
                        value={bnDigits(data.exam.count)}
                        sub={data.exam.next ? `${data.exam.next.name}${data.exam.next.start_date ? ` · ${formatDate(data.exam.next.start_date)}` : ''}` : t('core::admin.dashboard.exams_none')}
                        glyph="📝"
                        bg="var(--navy-50)"
                        color="var(--heading)"
                    />
                )}
            </div>

            <div className="grid items-start" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))', gap: 16 }}>
                {students && (
                    <Panel title={t('core::admin.dashboard.by_class')}>
                        {students.by_class.map((row) => (
                            <div key={row.id} className="grid items-center" style={{ gridTemplateColumns: '96px 1fr 36px', gap: 10, fontSize: 14 }}>
                                <span>{row.name}</span>
                                <Bar value={(row.count / classMax) * 100} color="var(--green-500)" height={10} />
                                <strong style={{ textAlign: 'right' }}>{bnDigits(row.count)}</strong>
                            </div>
                        ))}
                    </Panel>
                )}

                <div className="grid" style={{ gap: 16 }}>
                    {quick.length > 0 && (
                        <Panel title={t('core::admin.dashboard.quick_actions')}>
                            <div className="grid" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: 8 }}>
                                {quick.map((action) => (
                                    <Link
                                        key={action.key}
                                        to={action.path}
                                        className="grid place-items-center text-center"
                                        style={{ minHeight: 64, borderRadius: 14, border: '1px solid var(--line)', background: 'var(--surface-2)', fontWeight: 600, fontSize: 14, color: 'var(--ink)', padding: 8, textDecoration: 'none' }}
                                    >
                                        <span style={{ fontSize: 20 }}>{action.glyph}</span>
                                        {t(`core::admin.dashboard.quick.${action.key}`)}
                                    </Link>
                                ))}
                            </div>
                        </Panel>
                    )}

                    <section style={{ ...surface, boxShadow: 'none', padding: 18, display: 'grid', gap: 4 }}>
                        <h3 style={{ margin: '0 0 6px', fontSize: 17, fontWeight: 700 }}>{t('core::admin.dashboard.activity')}</h3>
                        {data.activity.length === 0 && <p style={{ color: 'var(--ds-muted)', fontSize: 14 }}>{t('core::admin.dashboard.activity_empty')}</p>}
                        {data.activity.map((entry, index) => (
                            <div key={index} className="grid items-baseline" style={{ gridTemplateColumns: '10px 1fr auto', gap: 10, padding: '6px 0', borderTop: '1px solid var(--line)', fontSize: 14 }}>
                                <span style={{ width: 8, height: 8, borderRadius: '50%', background: TONES[entry.tone] }} />
                                <span>{entry.text}</span>
                                <span style={{ color: 'var(--faint)', fontSize: 13 }}>{ago(entry.at)}</span>
                            </div>
                        ))}
                    </section>
                </div>
            </div>

            {/* Whatever a module adds to the dashboard through <DashboardWidgets.Fill> follows the design's blocks. */}
            <DashboardWidgets.Outlet className="grid gap-3 empty:hidden sm:grid-cols-2 lg:grid-cols-3" />
        </div>
    );
}
