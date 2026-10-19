import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { Dialog, DialogContent } from '@/components/ui/dialog';
import { api, ApiError } from '@core/js/api';
import { Chip, DashedEmpty, PageTitle, Pill } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Payment {
    id: number;
    receipt_no: string | null;
    amount: number;
    method: string;
    channel: string;
    transaction_id: string | null;
    sender_number: string | null;
    status: string;
    rejection_reason: string | null;
    screenshot_url: string | null;
    created_at: string;
    student: { id: number; student_id: string; name: string; class: string | null };
    invoices?: { id: number; invoice_no: string; billing_month: string | null; due: number }[];
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.verification.${key}`, replacements);
const BRANDS: Record<string, { icon: string; color: string }> = {
    bkash: { icon: 'b', color: '#E2136E' },
    nagad: { icon: 'N', color: '#EC1C24' },
    rocket: { icon: 'R', color: '#8C3494' },
    bank: { icon: '▦', color: '#16307A' },
    gateway: { icon: '⌁', color: '#16307A' },
    cash: { icon: '৳', color: '#0B6B3A' },
};
const FILTERS = ['all', 'bkash', 'nagad', 'rocket', 'bank', 'gateway'];
const REASONS = ['txn_mismatch', 'amount_mismatch', 'duplicate', 'unclear'];
const field = 'h-11 min-w-0 rounded-xl border border-line-strong bg-surface px-3 text-base text-foreground outline-none placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400';

/** The verification queue of the design: method chips with counts, a search box, and one card per pending payment. */
export function VerificationPage() {
    const [status, setStatus] = useState('pending');
    const [rows, setRows] = useState<Payment[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [filter, setFilter] = useState('all');
    const [query, setQuery] = useState('');
    const [rejecting, setRejecting] = useState<number | null>(null);
    const [reason, setReason] = useState('');
    const [reasonError, setReasonError] = useState(false);
    const [zoom, setZoom] = useState<string | null>(null);
    const canVerify = can('fees.verify');

    const load = useCallback(async () => {
        setFailed(false);

        try {
            const response = await api.get<{ data: Payment[] }>('/fees/payments', { status, per_page: '100' });
            setRows(response.data);
        } catch {
            setFailed(true);
        }
    }, [status]);

    useEffect(() => {
        setRows(null);
        void load();
    }, [load]);

    const counts = useMemo(() => Object.fromEntries(FILTERS.map((name) => [name, (rows ?? []).filter((row) => name === 'all' || row.method === name).length])), [rows]);
    const shown = (rows ?? []).filter((row) => (filter === 'all' || row.method === filter) && (!query || `${row.student.name} ${row.transaction_id ?? ''} ${row.student.student_id}`.toLowerCase().includes(query.toLowerCase())));

    const approve = async (payment: Payment) => {
        try {
            await api.post(`/fees/payments/${payment.id}/approve`);
            toast.success(k('approved_toast', { name: payment.student.name, amount: formatMoney(payment.amount) }));
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }

        void load();
    };

    const reject = async (payment: Payment) => {
        if (rejecting !== payment.id) {
            setRejecting(payment.id);
            setReason('');
            setReasonError(false);

            return;
        }

        if (!reason.trim()) {
            setReasonError(true);

            return;
        }

        try {
            await api.post(`/fees/payments/${payment.id}/reject`, { reason });
            toast.success(k('rejected_toast'));
            setRejecting(null);
        } catch (error) {
            toast.error(error instanceof ApiError ? (error.errors.reason?.[0] ?? error.message) : t('core::admin.common.error'));
        }

        void load();
    };

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>

            <div className="flex flex-wrap items-center gap-2">
                {FILTERS.filter((name) => name === 'all' || name === filter || counts[name] > 0 || status !== 'pending').map((name) => (
                    <Chip key={name} active={filter === name} onClick={() => setFilter(name)}>
                        {name === 'all' ? k('all') : t(`fees::admin.payment_method.${name}`)} ({bnDigits(counts[name] ?? 0)})
                    </Chip>
                ))}
                <input className={`${field} w-auto flex-[1_1_200px]`} placeholder={k('search')} aria-label={k('search')} value={query} onChange={(event) => setQuery(event.target.value)} />
                <NativeSelect className="w-auto" aria-label={k('status')} value={status} onChange={(event) => { setStatus(event.target.value); setFilter('all'); }} options={['pending', 'approved', 'rejected', 'failed'].map((value) => ({ value, label: t(`fees::admin.payment_status.${value}`) }))} />
            </div>

            {failed && <ErrorState onRetry={() => void load()} />}
            {rows === null && !failed && <LoadingState rows={2} height={180} />}
            {rows !== null && shown.length === 0 && (status === 'pending' && !query && filter === 'all' ? <DashedEmpty glyph="✓" title={k('empty_title')} description={k('empty_body')} /> : <EmptyState glyph="🔍" title={t('core::admin.common.empty')} />)}

            <div className="grid gap-3" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 460px), 1fr))' }}>
                {shown.map((payment) => {
                    const brand = BRANDS[payment.method] ?? BRANDS.cash;
                    const rejectingThis = rejecting === payment.id;
                    const pending = payment.status === 'pending';
                    const details: [string, string | null, boolean][] = [
                        [k('method'), t(`fees::admin.payment_method.${payment.method}`), false],
                        [k('transaction_id'), payment.transaction_id, true],
                        [k('sender'), payment.sender_number ? bnDigits(payment.sender_number) : null, true],
                        [k('for'), (payment.invoices ?? []).map((invoice) => (invoice.billing_month ? bnDigits(invoice.billing_month) : invoice.invoice_no)).join(', ') || null, false],
                        [k('paid_at'), formatDate(payment.created_at), true],
                    ];

                    return (
                        <div key={payment.id} className="grid gap-3.5 rounded-[20px] border border-line bg-surface p-[18px] shadow-sm" style={{ gridTemplateColumns: payment.screenshot_url ? 'minmax(0, 1fr) 120px' : 'minmax(0, 1fr)' }}>
                            <div className="grid min-w-0 gap-2">
                                <span className="flex flex-wrap items-center gap-2">
                                    <span className="grid size-9 place-items-center rounded-[10px] font-bold text-white" style={{ background: brand.color, fontFamily: 'Poppins, sans-serif' }}>
                                        {brand.icon}
                                    </span>
                                    <strong className="font-head text-[22px]">{formatMoney(payment.amount)}</strong>
                                    <Pill tone={pending ? 'warn' : payment.status === 'approved' ? 'success' : 'danger'}>{pending ? `⏳ ${k('verifying')}` : t(`fees::admin.payment_status.${payment.status}`)}</Pill>
                                </span>
                                <span className="leading-[1.45]">
                                    <strong>{payment.student.name}</strong> · <span className="text-sm text-muted-foreground">{payment.student.class} · {payment.student.student_id}</span>
                                </span>
                                {details.filter(([, value]) => value).map(([label, value, latin]) => (
                                    <span key={label} className="flex justify-between gap-2.5 border-b border-dashed border-line pb-1 text-sm">
                                        <span className="text-muted-foreground">{label}</span>
                                        <strong className="text-right break-all" style={latin ? { fontFamily: 'Poppins, sans-serif' } : undefined}>
                                            {value}
                                        </strong>
                                    </span>
                                ))}
                                {payment.rejection_reason && <span className="text-sm font-semibold text-danger">{payment.rejection_reason}</span>}
                            </div>
                            {payment.screenshot_url && (
                                <button type="button" onClick={() => setZoom(payment.screenshot_url)} aria-label={k('screenshot')} className="aspect-[9/16] max-h-[260px] w-full cursor-zoom-in overflow-hidden rounded-[14px] border border-line bg-surface-2 p-0">
                                    <img src={payment.screenshot_url} alt={k('screenshot')} className="size-full object-cover" />
                                </button>
                            )}
                            {!payment.screenshot_url && pending && <div className="col-span-full grid place-items-center rounded-[14px] border-2 border-dashed border-line p-3 text-center text-[13px] text-muted-foreground">{k('no_screenshot')}</div>}
                            {pending && canVerify && (
                                <div className="col-span-full grid gap-2">
                                    {rejectingThis && (
                                        <div className="grid gap-2 rounded-[14px] p-3" style={{ background: 'var(--danger-bg)' }}>
                                            <span className="text-sm font-bold text-danger">{k('reject_reason')}</span>
                                            <div className="flex flex-wrap gap-1.5">
                                                {REASONS.map((name) => (
                                                    <Chip key={name} size={34} active={reason === k(`reasons.${name}`)} tone="green" className="text-[13px]" onClick={() => { setReason(k(`reasons.${name}`)); setReasonError(false); }} style={reason === k(`reasons.${name}`) ? { background: 'var(--red-50)', borderColor: 'var(--red-600)', color: 'var(--ink)' } : undefined}>
                                                        {k(`reasons.${name}`)}
                                                    </Chip>
                                                ))}
                                            </div>
                                            <input className={`${field} w-full`} placeholder={k('reason_own')} aria-label={k('reject_reason')} value={reason} onChange={(event) => { setReason(event.target.value); setReasonError(false); }} />
                                            {reasonError && <span className="text-[13px] font-bold text-danger">{k('reason_required')}</span>}
                                        </div>
                                    )}
                                    <span className="flex flex-wrap justify-end gap-2">
                                        <button type="button" onClick={() => void reject(payment)} className="inline-flex h-11 cursor-pointer items-center gap-1.5 rounded-full border border-red-600 bg-surface px-4 font-semibold whitespace-nowrap text-danger">
                                            {rejectingThis ? k('reject_confirm') : `✕ ${k('reject')}`}
                                        </button>
                                        {!rejectingThis && (
                                            <button type="button" onClick={() => void approve(payment)} className="h-11 cursor-pointer rounded-full border-0 bg-green-700 px-[18px] font-bold whitespace-nowrap text-white">
                                                ✓ {k('approve')}
                                            </button>
                                        )}
                                    </span>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            <Dialog open={zoom !== null} onOpenChange={(open) => !open && setZoom(null)}>
                <DialogContent className="max-w-3xl">{zoom && <img src={zoom} alt={k('screenshot')} className="max-h-[80vh] w-full object-contain" />}</DialogContent>
            </Dialog>
        </div>
    );
}
