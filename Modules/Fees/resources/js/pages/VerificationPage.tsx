import { CheckIcon, XIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';

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
    verified_by: string | null;
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.verification.${key}`, replacements);

export function VerificationPage() {
    const [status, setStatus] = useState('pending');
    const { rows, meta, loading, setPage, reload } = usePaginated<Payment>('/fees/payments', { status });
    const [rejecting, setRejecting] = useState<Payment | null>(null);
    const [reason, setReason] = useState('');
    const [zoom, setZoom] = useState<string | null>(null);

    const approve = async (payment: Payment) => {
        try {
            await api.post(`/fees/payments/${payment.id}/approve`);
            toast.success(k('approved'));
            reload();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
            reload();
        }
    };

    const reject = async () => {
        if (!rejecting) {
            return;
        }

        try {
            await api.post(`/fees/payments/${rejecting.id}/reject`, { reason });
            toast.success(k('rejected'));
            setRejecting(null);
            setReason('');
            reload();
        } catch (error) {
            toast.error(error instanceof ApiError ? (error.errors.reason?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <div className="max-w-5xl space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
                <NativeSelect className="w-auto" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={['pending', 'approved', 'rejected', 'failed'].map((value) => ({ value, label: t(`fees::messages.payment_status.${value}`) }))} />
            </div>

            {loading && rows.length === 0 && <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>}
            {!loading && rows.length === 0 && <p className="rounded-xl border bg-card p-6 text-center text-muted-foreground">{k('empty')}</p>}

            <div className="space-y-3">
                {rows.map((payment) => (
                    <div key={payment.id} className="flex flex-wrap items-start justify-between gap-4 rounded-xl border bg-card p-4">
                        <div className="flex gap-4">
                            {payment.screenshot_url ? (
                                <button type="button" onClick={() => setZoom(payment.screenshot_url)} className="size-20 shrink-0 overflow-hidden rounded-lg border">
                                    <img src={payment.screenshot_url} alt={k('screenshot')} className="size-full object-cover" />
                                </button>
                            ) : (
                                <div className="flex size-20 shrink-0 items-center justify-center rounded-lg border bg-muted text-xs text-muted-foreground">{k('no_screenshot')}</div>
                            )}
                            <div className="space-y-0.5 text-sm">
                                <div className="font-semibold">{payment.student.name} <span className="font-normal text-muted-foreground">· {payment.student.student_id} · {payment.student.class}</span></div>
                                <div>
                                    <Badge variant="secondary">{t(`fees::messages.payment_method.${payment.method}`)}</Badge> <code className="text-xs">{payment.transaction_id}</code>
                                </div>
                                {payment.sender_number && <div className="text-muted-foreground">{k('sender')}: {bnDigits(payment.sender_number)}</div>}
                                <div className="text-muted-foreground">{formatDate(payment.created_at)}</div>
                                {payment.invoices?.map((invoice) => (
                                    <div key={invoice.id} className="text-xs text-muted-foreground">{invoice.invoice_no} {invoice.billing_month ? `(${bnDigits(invoice.billing_month)})` : ''} — {formatMoney(invoice.due)}</div>
                                ))}
                                {payment.rejection_reason && <div className="text-destructive">{payment.rejection_reason}</div>}
                                {payment.receipt_no && <div className="text-xs">{t('fees::pdf.receipt_no')}: {payment.receipt_no}</div>}
                            </div>
                        </div>
                        <div className="flex flex-col items-end gap-2">
                            <div className="text-xl font-semibold">{formatMoney(payment.amount)}</div>
                            {payment.status === 'pending' && can('fees.verify') ? (
                                <div className="flex gap-2">
                                    <Button size="sm" onClick={() => void approve(payment)}>
                                        <CheckIcon /> {k('approve')}
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={() => setRejecting(payment)}>
                                        <XIcon /> {k('reject')}
                                    </Button>
                                </div>
                            ) : (
                                <Badge variant={payment.status === 'approved' ? 'default' : 'outline'}>{t(`fees::messages.payment_status.${payment.status}`)}</Badge>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {meta && meta.last_page > 1 && (
                <div className="flex justify-center gap-2">
                    <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage(meta.current_page - 1)}>{t('core::admin.common.previous')}</Button>
                    <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage(meta.current_page + 1)}>{t('core::admin.common.next')}</Button>
                </div>
            )}

            <Dialog open={rejecting !== null} onOpenChange={(open) => !open && setRejecting(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{k('reject_title')}</DialogTitle>
                    </DialogHeader>
                    <Field label={k('reason')} htmlFor="reject-reason" hint={k('reason_hint')}>
                        <Input id="reject-reason" value={reason} onChange={(event) => setReason(event.target.value)} />
                    </Field>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRejecting(null)}>{t('core::admin.common.cancel')}</Button>
                        <Button disabled={!reason.trim()} onClick={() => void reject()}>{k('reject')}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={zoom !== null} onOpenChange={(open) => !open && setZoom(null)}>
                <DialogContent className="max-w-3xl">
                    {zoom && <img src={zoom} alt={k('screenshot')} className="max-h-[80vh] w-full object-contain" />}
                </DialogContent>
            </Dialog>
        </div>
    );
}
