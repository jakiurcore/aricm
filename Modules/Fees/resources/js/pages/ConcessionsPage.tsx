import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DesignTable, PageTitle, Panel, Pill, Segmented, TableCard, Td, Th, type Tone } from '@core/js/components/design';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { locale, t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Concession {
    id: number;
    student: { id: number; student_id: string; name: string };
    fee_head_id: number | null;
    head: string | null;
    kind: 'percent' | 'fixed';
    value: number;
    reason_type: string;
    note: string | null;
    valid_from_month: string;
    valid_to_month: string | null;
}

const k = (key: string) => t(`fees::admin.concessions.${key}`);
const reasons = ['orphan', 'sibling', 'merit', 'special', 'other'];

export function ConcessionsPage() {
    const [rows, setRows] = useState<Concession[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [heads, setHeads] = useState<{ id: number; name_bn: string }[]>([]);
    const [student, setStudent] = useState<PickedStudent | null>(null);
    const [form, setForm] = useState({ fee_head_id: '', kind: 'percent', value: '', reason_type: 'sibling', note: '', valid_from_month: new Date().toISOString().slice(0, 7), valid_to_month: '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [deleting, setDeleting] = useState<Concession | null>(null);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            setRows((await api.get<{ data: Concession[] }>('/fees/concessions')).data);
        } catch {
            setFailed(true);
        }
    }, []);

    useEffect(() => {
        void load();
        void api.get<{ data: { id: number; name_bn: string }[] }>('/fees/heads').then((response) => setHeads(response.data));
    }, [load]);

    const save = async () => {
        setErrors({});

        try {
            await api.post('/fees/concessions', { ...form, student_id: student?.id, fee_head_id: form.fee_head_id || null, valid_to_month: form.valid_to_month || null });
            toast.success(t('core::admin.common.saved'));
            setStudent(null);
            setForm({ ...form, value: '', note: '' });
            await load();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (deleting) {
            await api.delete(`/fees/concessions/${deleting.id}`);
            setDeleting(null);
            await load();
        }
    };

    const set = (name: string, value: string) => setForm({ ...form, [name]: value });

    const editable = can('fees.concession');
    const month = (value: string) => new Date(`${value}-01T00:00:00`).toLocaleDateString(locale() === 'bn' ? 'bn-BD' : 'en-GB', { month: 'short', year: '2-digit' });
    const reasonTone = (reason: string): Tone => (reason === 'orphan' ? 'info' : reason === 'sibling' ? 'green' : reason === 'merit' ? 'yellow' : 'ink');
    const valueLabel = form.kind === 'percent' ? k('percent_value') : k('fixed_value');

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>

            {editable && (
                <Panel className="gap-3">
                    <strong className="text-base">{k('new')}</strong>
                    <div className="grid items-end gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 190px), 1fr))' }}>
                        <Field label={k('student')} error={errors.student_id?.[0]}>
                            <StudentPicker value={student} onChange={setStudent} />
                        </Field>
                        <Field label={k('head')} htmlFor="c-head">
                            <NativeSelect id="c-head" placeholder={k('all_heads')} value={form.fee_head_id} onChange={(event) => set('fee_head_id', event.target.value)} options={heads.map((head) => ({ value: head.id, label: head.name_bn }))} />
                        </Field>
                        <div className="grid gap-1 text-sm font-semibold">
                            {k('kind')}
                            <Segmented round="lg" filled={false} label={k('kind')} value={form.kind} onChange={(value) => set('kind', value)} options={[{ value: 'percent', label: t('fees::admin.concession_kind.percent') }, { value: 'fixed', label: t('fees::admin.concession_kind.fixed') }]} />
                        </div>
                        <Field label={valueLabel} htmlFor="c-value" error={errors.value?.[0]}>
                            <Input id="c-value" inputMode="decimal" style={{ fontFamily: 'Poppins, sans-serif' }} value={form.value} onChange={(event) => set('value', event.target.value)} />
                        </Field>
                        <Field label={k('reason')} htmlFor="c-reason">
                            <NativeSelect id="c-reason" value={form.reason_type} onChange={(event) => set('reason_type', event.target.value)} options={reasons.map((value) => ({ value, label: t(`fees::admin.concession_reason.${value}`) }))} />
                        </Field>
                        <Field label={k('from')} htmlFor="c-from" error={errors.valid_from_month?.[0]}>
                            <Input id="c-from" type="month" value={form.valid_from_month} onChange={(event) => set('valid_from_month', event.target.value)} />
                        </Field>
                        <Field label={k('to')} htmlFor="c-to" error={errors.valid_to_month?.[0]}>
                            <Input id="c-to" type="month" value={form.valid_to_month} onChange={(event) => set('valid_to_month', event.target.value)} />
                        </Field>
                        <Field label={k('note')} htmlFor="c-note">
                            <Input id="c-note" value={form.note} onChange={(event) => set('note', event.target.value)} />
                        </Field>
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2.5">
                        <span className="min-w-[200px] flex-1 text-sm text-muted-foreground">{k('to_hint')}</span>
                        <Button disabled={!student || !form.value} onClick={() => void save()}>
                            + {k('add')}
                        </Button>
                    </div>
                </Panel>
            )}

            {failed && <ErrorState onRetry={() => void load()} />}
            {rows === null && !failed && <LoadingState rows={3} height={48} />}
            {rows?.length === 0 && <EmptyState glyph="%" title={t('core::admin.common.empty')} />}
            {rows && rows.length > 0 && (
                <TableCard>
                    <DesignTable minWidth={800} className="text-sm">
                        <thead>
                            <tr className="bg-surface-2">
                                <Th>{k('student')}</Th>
                                <Th>{k('head')}</Th>
                                <Th align="right">{k('discount')}</Th>
                                <Th>{k('reason')}</Th>
                                <Th>{k('period')}</Th>
                                <Th />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.id} className="border-t border-line">
                                    <Td>
                                        <strong>{row.student.name}</strong>
                                        <br />
                                        <span className="text-xs text-muted-foreground">{row.student.student_id}</span>
                                    </Td>
                                    <Td>{row.head ?? k('all_heads')}</Td>
                                    <Td align="right" className="font-bold whitespace-nowrap" style={{ color: 'var(--success)' }}>
                                        {row.kind === 'percent' ? `${bnDigits(row.value)}%` : formatMoney(row.value)}
                                    </Td>
                                    <Td>
                                        <Pill tone={reasonTone(row.reason_type)}>{t(`fees::admin.concession_reason.${row.reason_type}`)}</Pill>
                                        {row.note && <div className="mt-0.5 text-xs text-muted-foreground">{row.note}</div>}
                                    </Td>
                                    <Td className="whitespace-nowrap">
                                        {month(row.valid_from_month)} – {row.valid_to_month ? month(row.valid_to_month) : k('open_ended')}
                                    </Td>
                                    <Td align="right">
                                        {editable && (
                                            <button type="button" onClick={() => setDeleting(row)} className="h-[34px] cursor-pointer rounded-full border border-line-strong bg-surface px-3 text-[13px] font-bold whitespace-nowrap">
                                                {k('cancel')}
                                            </button>
                                        )}
                                    </Td>
                                </tr>
                            ))}
                        </tbody>
                    </DesignTable>
                </TableCard>
            )}
            <span className="text-[13px] text-muted-foreground">{k('footnote')}</span>

            <ConfirmDialog open={deleting !== null} title={k('cancel_title')} description={deleting?.student.name} confirmLabel={k('cancel')} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
