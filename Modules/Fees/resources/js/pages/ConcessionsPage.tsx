import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits } from '@core/js/number';

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
    const [rows, setRows] = useState<Concession[]>([]);
    const [heads, setHeads] = useState<{ id: number; name_bn: string }[]>([]);
    const [open, setOpen] = useState(false);
    const [student, setStudent] = useState<PickedStudent | null>(null);
    const [form, setForm] = useState({ fee_head_id: '', kind: 'percent', value: '', reason_type: 'sibling', note: '', valid_from_month: new Date().toISOString().slice(0, 7), valid_to_month: '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [deleting, setDeleting] = useState<Concession | null>(null);

    const load = useCallback(async () => setRows((await api.get<{ data: Concession[] }>('/fees/concessions')).data), []);

    useEffect(() => {
        void load();
        void api.get<{ data: { id: number; name_bn: string }[] }>('/fees/heads').then((response) => setHeads(response.data));
    }, [load]);

    const save = async () => {
        setErrors({});

        try {
            await api.post('/fees/concessions', { ...form, student_id: student?.id, fee_head_id: form.fee_head_id || null, valid_to_month: form.valid_to_month || null });
            toast.success(t('core::admin.common.saved'));
            setOpen(false);
            setStudent(null);
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

    return (
        <div className="space-y-4">
            <HeaderActions.Fill>
                <Button onClick={() => setOpen(true)}>
                    <PlusIcon /> {k('new')}
                </Button>
            </HeaderActions.Fill>
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="p-3">{k('student')}</th>
                            <th className="p-3">{k('head')}</th>
                            <th className="p-3">{k('discount')}</th>
                            <th className="p-3">{k('reason')}</th>
                            <th className="p-3">{k('period')}</th>
                            <th className="p-3" />
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-muted-foreground">
                                    {t('core::admin.common.empty')}
                                </td>
                            </tr>
                        )}
                        {rows.map((row) => (
                            <tr key={row.id} className="border-b last:border-0">
                                <td className="p-3">
                                    <div className="font-medium">{row.student.name}</div>
                                    <div className="text-xs text-muted-foreground">{row.student.student_id}</div>
                                </td>
                                <td className="p-3">{row.head ?? k('all_heads')}</td>
                                <td className="p-3 font-semibold">{row.kind === 'percent' ? `${bnDigits(row.value)}%` : formatMoney(row.value)}</td>
                                <td className="p-3">
                                    <Badge variant="secondary">{t(`fees::messages.concession_reason.${row.reason_type}`)}</Badge>
                                    {row.note && <div className="text-xs text-muted-foreground">{row.note}</div>}
                                </td>
                                <td className="p-3">
                                    {bnDigits(row.valid_from_month)} – {row.valid_to_month ? bnDigits(row.valid_to_month) : k('open_ended')}
                                </td>
                                <td className="p-3 text-right">
                                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(row)}>
                                        <Trash2Icon />
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{k('new')}</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <Field label={k('student')} error={errors.student_id?.[0]}>
                                <StudentPicker value={student} onChange={setStudent} />
                            </Field>
                        </div>
                        <Field label={k('head')} htmlFor="c-head">
                            <NativeSelect id="c-head" placeholder={k('all_heads')} value={form.fee_head_id} onChange={(event) => set('fee_head_id', event.target.value)} options={heads.map((head) => ({ value: head.id, label: head.name_bn }))} />
                        </Field>
                        <Field label={k('kind')} htmlFor="c-kind">
                            <NativeSelect id="c-kind" value={form.kind} onChange={(event) => set('kind', event.target.value)} options={['percent', 'fixed'].map((value) => ({ value, label: t(`fees::messages.concession_kind.${value}`) }))} />
                        </Field>
                        <Field label={form.kind === 'percent' ? k('percent_value') : k('fixed_value')} htmlFor="c-value" error={errors.value?.[0]}>
                            <Input id="c-value" inputMode="decimal" value={form.value} onChange={(event) => set('value', event.target.value)} />
                        </Field>
                        <Field label={k('reason')} htmlFor="c-reason">
                            <NativeSelect id="c-reason" value={form.reason_type} onChange={(event) => set('reason_type', event.target.value)} options={reasons.map((value) => ({ value, label: t(`fees::messages.concession_reason.${value}`) }))} />
                        </Field>
                        <Field label={k('from')} htmlFor="c-from" error={errors.valid_from_month?.[0]}>
                            <Input id="c-from" type="month" value={form.valid_from_month} onChange={(event) => set('valid_from_month', event.target.value)} />
                        </Field>
                        <Field label={k('to')} htmlFor="c-to" error={errors.valid_to_month?.[0]} hint={k('to_hint')}>
                            <Input id="c-to" type="month" value={form.valid_to_month} onChange={(event) => set('valid_to_month', event.target.value)} />
                        </Field>
                        <div className="sm:col-span-2">
                            <Field label={k('note')} htmlFor="c-note">
                                <Textarea id="c-note" rows={2} value={form.note} onChange={(event) => set('note', event.target.value)} />
                            </Field>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button disabled={!student} onClick={() => void save()}>
                            {t('core::admin.common.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog open={deleting !== null} description={deleting?.student.name} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
