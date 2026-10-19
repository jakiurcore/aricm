import { FilePlusIcon, SparklesIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { HeaderActions } from '@admin/js';
import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { DataTable } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';

interface Invoice {
    id: number;
    invoice_no: string;
    type: string;
    status: string;
    billing_month: string | null;
    due_date: string | null;
    total: number;
    paid: number;
    due: number;
    late_fine: number;
    student: { id: number; student_id: string; name: string; roll: number | null };
    class_name: string;
}

interface Summary {
    dry_run: boolean;
    created: number;
    skipped_existing: number;
    skipped_pre_admission: number;
    skipped_no_fee: number;
    total_amount: number;
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.invoices.${key}`, replacements);

const statusVariant = (status: string) => (status === 'paid' ? 'default' : status === 'unpaid' ? 'destructive' : 'outline');

function SummaryBox({ summary }: { summary: Summary }) {
    return (
        <div className="space-y-1 rounded-lg border bg-muted/40 p-3 text-sm">
            <p className="font-medium">{summary.dry_run ? k('preview') : k('done')}</p>
            <p>{k('to_create', { count: bnDigits(summary.created), amount: formatMoney(summary.total_amount) })}</p>
            <p className="text-muted-foreground">{k('skipped', { existing: bnDigits(summary.skipped_existing), pre: bnDigits(summary.skipped_pre_admission), nofee: bnDigits(summary.skipped_no_fee) })}</p>
        </div>
    );
}

function GenerateDialog({ open, onClose, onDone }: { open: boolean; onClose: () => void; onDone: () => void }) {
    const academic = useAcademicOptions();
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));
    const [classIds, setClassIds] = useState<number[]>([]);
    const [summary, setSummary] = useState<Summary | null>(null);

    const run = async (confirm: boolean) => {
        try {
            const response = await api.post<{ data: Summary }>('/fees/invoices/generate/monthly', { month, class_ids: classIds, confirm });
            setSummary(response.data);

            if (confirm) {
                toast.success(k('done'));
                onDone();
            }
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k('generate_title')}</DialogTitle>
                </DialogHeader>
                <Field label={k('month')} htmlFor="g-month">
                    <Input id="g-month" type="month" value={month} onChange={(event) => { setMonth(event.target.value); setSummary(null); }} />
                </Field>
                <Field label={k('classes')} hint={k('classes_hint')}>
                    <div className="grid gap-2 sm:grid-cols-3">
                        {academic.classes.map((schoolClass) => (
                            <label key={schoolClass.id} className="flex items-center gap-2 text-sm">
                                <Checkbox checked={classIds.includes(schoolClass.id)} onCheckedChange={(checked) => { setClassIds(checked ? [...classIds, schoolClass.id] : classIds.filter((id) => id !== schoolClass.id)); setSummary(null); }} />
                                {schoolClass.name}
                            </label>
                        ))}
                    </div>
                </Field>
                {summary && <SummaryBox summary={summary} />}
                <DialogFooter>
                    <Button variant="outline" onClick={() => void run(false)}>
                        {k('preview_button')}
                    </Button>
                    <Button disabled={!summary?.dry_run || summary.created === 0} onClick={() => void run(true)}>
                        {k('confirm_button')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function OneOffDialog({ open, onClose, onDone }: { open: boolean; onClose: () => void; onDone: () => void }) {
    const [student, setStudent] = useState<PickedStudent | null>(null);
    const [heads, setHeads] = useState<{ id: number; name_bn: string }[]>([]);
    const [items, setItems] = useState<{ fee_head_id: string; amount: string }[]>([{ fee_head_id: '', amount: '' }]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const ensureHeads = async () => heads.length === 0 && setHeads((await api.get<{ data: { id: number; name_bn: string }[] }>('/fees/heads')).data);

    if (open && heads.length === 0) {
        void ensureHeads();
    }

    const save = async () => {
        setErrors({});

        try {
            await api.post('/fees/invoices', { student_id: student?.id, items: items.filter((item) => item.fee_head_id).map((item) => ({ fee_head_id: Number(item.fee_head_id), amount: item.amount || null })) });
            toast.success(t('core::admin.common.saved'));
            setStudent(null);
            setItems([{ fee_head_id: '', amount: '' }]);
            onDone();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k('one_off_title')}</DialogTitle>
                </DialogHeader>
                <Field label={t('fees::admin.concessions.student')}>
                    <StudentPicker value={student} onChange={setStudent} />
                </Field>
                {items.map((item, index) => (
                    <div key={index} className="grid gap-2 sm:grid-cols-[1fr_9rem]">
                        <NativeSelect placeholder={k('head')} value={item.fee_head_id} onChange={(event) => setItems(items.map((row, at) => (at === index ? { ...row, fee_head_id: event.target.value } : row)))} options={heads.map((head) => ({ value: head.id, label: head.name_bn }))} />
                        <Input inputMode="decimal" placeholder={k('amount_hint')} value={item.amount} onChange={(event) => setItems(items.map((row, at) => (at === index ? { ...row, amount: event.target.value } : row)))} />
                    </div>
                ))}
                {(errors.items || errors['items.0.amount']) && <p className="text-sm text-destructive">{(errors.items ?? errors['items.0.amount'])[0]}</p>}
                <Button variant="ghost" size="sm" onClick={() => setItems([...items, { fee_head_id: '', amount: '' }])}>
                    + {k('add_item')}
                </Button>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        {t('core::admin.common.cancel')}
                    </Button>
                    <Button disabled={!student} onClick={() => void save()}>
                        {k('create')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function WaiveDialog({ invoice, onClose, onDone }: { invoice: Invoice | null; onClose: () => void; onDone: () => void }) {
    const [reason, setReason] = useState('');

    const save = async () => {
        try {
            await api.post(`/fees/invoices/${invoice?.id}/waive`, { reason });
            toast.success(k('waived'));
            setReason('');
            onDone();
        } catch (error) {
            toast.error(error instanceof ApiError ? (error.errors.reason?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <Dialog open={invoice !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k('waive_title', { invoice: invoice?.invoice_no ?? '' })}</DialogTitle>
                </DialogHeader>
                <Field label={k('waive_reason')} htmlFor="waive-reason" hint={k('waive_hint')}>
                    <Input id="waive-reason" value={reason} onChange={(event) => setReason(event.target.value)} />
                </Field>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        {t('core::admin.common.cancel')}
                    </Button>
                    <Button disabled={!reason.trim()} onClick={() => void save()}>
                        {k('waive')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function InvoicesPage() {
    const academic = useAcademicOptions();
    const [filters, setFilters] = useState<Record<string, string>>({});
    const [search, setSearch] = useState('');
    const { rows, meta, loading, setPage, reload } = usePaginated<Invoice>('/fees/invoices', { ...filters, search });
    const [generate, setGenerate] = useState(false);
    const [oneOff, setOneOff] = useState(false);
    const [waiving, setWaiving] = useState<Invoice | null>(null);

    const filter = (name: string, value: string) => setFilters({ ...filters, [name]: value });

    return (
        <div className="space-y-4">
            <HeaderActions.Fill>
                <div className="flex gap-2">
                    {can('fees.generate') && (
                        <>
                            <Button variant="outline" onClick={() => setOneOff(true)}>
                                <FilePlusIcon /> {k('one_off')}
                            </Button>
                            <Button onClick={() => setGenerate(true)}>
                                <SparklesIcon /> {k('generate')}
                            </Button>
                        </>
                    )}
                </div>
            </HeaderActions.Fill>
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            <div className="flex flex-wrap gap-2">
                <Input className="max-w-xs" placeholder={t('students::admin.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                <NativeSelect className="w-auto" placeholder={t('students::admin.fields.class_id')} value={filters.class_id ?? ''} onChange={(event) => filter('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                <NativeSelect className="w-auto" placeholder={k('status')} value={filters.status ?? ''} onChange={(event) => filter('status', event.target.value)} options={['unpaid', 'partial', 'paid', 'waived'].map((value) => ({ value, label: t(`fees::messages.invoice_status.${value}`) }))} />
                <NativeSelect className="w-auto" placeholder={k('type')} value={filters.type ?? ''} onChange={(event) => filter('type', event.target.value)} options={['monthly', 'one_time', 'exam'].map((value) => ({ value, label: t(`fees::admin.types.${value}`) }))} />
                <Input className="w-auto" type="month" aria-label={k('month')} value={filters.month ?? ''} onChange={(event) => filter('month', event.target.value)} />
            </div>

            <DataTable
                data={rows}
                loading={loading}
                meta={meta}
                onPageChange={setPage}
                columns={[
                    { header: k('invoice'), cell: ({ row }) => <span className="font-medium">{row.original.invoice_no}</span> },
                    { header: k('student'), cell: ({ row }) => (<div><div>{row.original.student.name}</div><div className="text-xs text-muted-foreground">{row.original.student.student_id} · {row.original.class_name}</div></div>) },
                    { header: k('for'), cell: ({ row }) => (row.original.billing_month ? bnDigits(row.original.billing_month) : t(`fees::admin.types.${row.original.type}`)) },
                    { header: k('due_date'), cell: ({ row }) => formatDate(row.original.due_date) },
                    { header: k('total'), cell: ({ row }) => formatMoney(row.original.total) },
                    { header: k('due'), cell: ({ row }) => <span className={row.original.due > 0 ? 'font-semibold text-destructive' : ''}>{formatMoney(row.original.due)}</span> },
                    { header: k('status'), cell: ({ row }) => <Badge variant={statusVariant(row.original.status)}>{t(`fees::messages.invoice_status.${row.original.status}`)}</Badge> },
                    { id: 'actions', header: '', cell: ({ row }) => (can('fees.waive') && ['unpaid', 'partial'].includes(row.original.status) ? <Button variant="ghost" size="sm" onClick={() => setWaiving(row.original)}>{k('waive')}</Button> : null) },
                ]}
            />

            <GenerateDialog open={generate} onClose={() => setGenerate(false)} onDone={() => { setGenerate(false); reload(); }} />
            <OneOffDialog open={oneOff} onClose={() => setOneOff(false)} onDone={() => { setOneOff(false); reload(); }} />
            <WaiveDialog invoice={waiving} onClose={() => setWaiving(null)} onDone={() => { setWaiving(null); reload(); }} />
        </div>
    );
}
