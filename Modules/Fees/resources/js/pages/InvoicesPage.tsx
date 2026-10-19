import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { DataTable } from '@core/js/components/DataTable';
import { PageTitle, Panel, Notice } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { locale, t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

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
const statusVariant = (status: string) => (status === 'paid' ? 'success' : status === 'unpaid' ? 'destructive' : status === 'waived' ? 'info' : 'warning');
const card = 'items-start content-start gap-3 border-t-4';
const title = 'text-[17px]';
const primary = 'h-11 cursor-pointer justify-self-end rounded-full border-0 px-[18px] font-bold whitespace-nowrap text-white disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint';

/** "মাস yyyy" options for the twelve months around today, newest billing month first-class: this year Jan..Dec. */
function monthOptions(): { value: string; label: string }[] {
    const year = new Date().getFullYear();

    return Array.from({ length: 12 }, (_, index) => {
        const value = `${year}-${String(index + 1).padStart(2, '0')}`;
        const label = new Date(`${value}-01T00:00:00`).toLocaleDateString(locale() === 'bn' ? 'bn-BD' : 'en-GB', { month: 'long', year: 'numeric' });

        return { value, label };
    });
}

function MonthlyCard({ onDone }: { onDone: () => void }) {
    const academic = useAcademicOptions();
    const [month, setMonth] = useState(new Date().toISOString().slice(0, 7));
    const [dueDate, setDueDate] = useState('');
    const [classIds, setClassIds] = useState<number[] | null>(null);
    const [summary, setSummary] = useState<Summary | null>(null);
    const [asking, setAsking] = useState(false);
    const [done, setDone] = useState('');
    const chosen = classIds ?? academic.classes.map((schoolClass) => schoolClass.id);
    const key = JSON.stringify([month, dueDate, chosen]);

    useEffect(() => {
        setAsking(false);
        setDone('');

        if (chosen.length === 0) {
            setSummary(null);

            return;
        }

        const timer = setTimeout(() => void api.post<{ data: Summary }>('/fees/invoices/generate/monthly', { month, class_ids: chosen, due_date: dueDate || null, confirm: false }).then((response) => setSummary(response.data)).catch(() => setSummary(null)), 250);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    const toggle = (id: number) => setClassIds(chosen.includes(id) ? chosen.filter((value) => value !== id) : [...chosen, id]);
    const allOn = chosen.length === academic.classes.length;
    const monthLabel = monthOptions().find((option) => option.value === month)?.label ?? month;

    const run = async () => {
        try {
            const response = await api.post<{ data: Summary }>('/fees/invoices/generate/monthly', { month, class_ids: chosen, due_date: dueDate || null, confirm: true });
            setAsking(false);
            setDone(k('done_line', { month: monthLabel, count: bnDigits(response.data.created) }));
            onDone();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    return (
        <Panel className={card} style={{ borderTopColor: 'var(--green-700)' }}>
            <strong className={title}>🗓 {k('monthly_title')}</strong>
            <div className="grid grid-cols-2 gap-2.5">
                <Field label={k('month')} htmlFor="g-month">
                    <NativeSelect id="g-month" value={month} onChange={(event) => setMonth(event.target.value)} options={monthOptions()} />
                </Field>
                <Field label={k('due_date')} htmlFor="g-due">
                    <Input id="g-due" type="date" value={dueDate} onChange={(event) => setDueDate(event.target.value)} />
                </Field>
            </div>
            <div className="grid gap-1.5">
                <span className="flex justify-between text-sm font-semibold">
                    {k('classes')}
                    <button type="button" onClick={() => setClassIds(allOn ? [] : academic.classes.map((schoolClass) => schoolClass.id))} className="min-h-0 cursor-pointer border-0 bg-transparent p-0 font-bold text-green-700">
                        {allOn ? k('clear_all') : k('select_all')}
                    </button>
                </span>
                <div className="flex flex-wrap gap-1.5">
                    {academic.classes.map((schoolClass) => {
                        const on = chosen.includes(schoolClass.id);

                        return (
                            <button key={schoolClass.id} type="button" aria-pressed={on} onClick={() => toggle(schoolClass.id)} className="min-h-10 cursor-pointer rounded-full border px-3 text-sm font-bold" style={{ borderColor: on ? 'var(--green-700)' : 'var(--line-strong)', background: on ? 'var(--green-50)' : 'var(--surface)', color: on ? 'var(--green-700)' : 'var(--ink)' }}>
                                {on ? '✓ ' : ''}
                                {schoolClass.name}
                            </button>
                        );
                    })}
                </div>
            </div>
            <div className="grid grid-cols-3 gap-2 rounded-[14px] bg-surface-2 px-3.5 py-3 text-center">
                {[
                    [k('classes'), bnDigits(chosen.length)],
                    [k('invoices'), summary ? bnDigits(summary.created) : '—'],
                    [k('total'), summary ? formatMoney(summary.total_amount) : '—'],
                ].map(([label, value]) => (
                    <span key={label} className="grid leading-[1.4]">
                        <span className="text-xs font-semibold text-muted-foreground">{label}</span>
                        <strong className="font-head text-lg">{value}</strong>
                    </span>
                ))}
            </div>
            {summary && summary.skipped_existing > 0 && <Notice tone="warn">⚠ {k('skipped', { existing: bnDigits(summary.skipped_existing), pre: bnDigits(summary.skipped_pre_admission), nofee: bnDigits(summary.skipped_no_fee) })}</Notice>}
            {asking && summary && (
                <div className="grid gap-2 rounded-[14px] border-2 border-green-700 bg-green-50 px-3.5 py-3">
                    <strong>
                        {k('confirm_label')}: {monthLabel} — {k('to_create', { count: bnDigits(summary.created), amount: formatMoney(summary.total_amount) })}
                    </strong>
                    <span className="flex justify-end gap-2">
                        <Button variant="outline" onClick={() => setAsking(false)}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button onClick={() => void run()}>{k('yes_create')}</Button>
                    </span>
                </div>
            )}
            {done && <Notice tone="success" className="font-bold">✓ {done}</Notice>}
            {!asking && (
                <button type="button" className={`${primary} bg-green-700`} disabled={!summary || summary.created === 0} onClick={() => setAsking(true)}>
                    {k('generate_button')} →
                </button>
            )}
        </Panel>
    );
}

function ExamCard({ onDone }: { onDone: () => void }) {
    const [exams, setExams] = useState<{ id: number; name: string }[]>([]);
    const [examId, setExamId] = useState('');
    const [summary, setSummary] = useState<Summary | null>(null);
    const [done, setDone] = useState(false);

    useEffect(() => {
        void api.get<{ data: { id: number; name: string }[] }>('/exams', { per_page: '60' }).then((response) => {
            setExams(response.data);
            setExamId((current) => current || String(response.data[0]?.id ?? ''));
        }).catch(() => undefined);
    }, []);

    const generate = async (confirm: boolean) => {
        try {
            const response = await api.post<{ data: Summary }>('/fees/invoices/generate/exam', { exam_id: Number(examId), confirm });
            setSummary(confirm ? null : response.data);

            if (confirm) {
                setDone(true);
                toast.success(t('fees::admin.exam_fees.generated', { count: response.data.created }));
                onDone();
            }
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <Panel className={card} style={{ borderTopColor: 'var(--navy-800)' }}>
            <strong className={title}>📝 {k('exam_title')}</strong>
            <Field label={k('exam')} htmlFor="e-exam">
                <NativeSelect id="e-exam" value={examId} onChange={(event) => { setExamId(event.target.value); setSummary(null); setDone(false); }} options={exams.map((exam) => ({ value: exam.id, label: exam.name }))} />
            </Field>
            <span className="text-sm leading-[1.55] text-muted-foreground">{k('exam_help')}</span>
            {summary && <Notice tone="info">{t('fees::admin.exam_fees.summary', { created: summary.created, existing: summary.skipped_existing })}</Notice>}
            {done && <Notice tone="success" className="font-bold">✓ {k('exam_done')}</Notice>}
            <span className="flex flex-wrap justify-end gap-2">
                <Button variant="outline" disabled={!examId} render={<Link to={`/exams/${examId}?tab=fees`} />}>
                    {k('view_exam_fees')}
                </Button>
                {summary ? (
                    <button type="button" className={`${primary} bg-navy-800`} disabled={summary.created === 0} onClick={() => void generate(true)}>
                        {k('yes_create')}
                    </button>
                ) : (
                    <button type="button" className={`${primary} bg-navy-800`} disabled={!examId} onClick={() => void generate(false)}>
                        {k('exam_button')}
                    </button>
                )}
            </span>
        </Panel>
    );
}

function OneOffCard({ onDone }: { onDone: () => void }) {
    const [student, setStudent] = useState<PickedStudent | null>(null);
    const [heads, setHeads] = useState<{ id: number; name_bn: string }[]>([]);
    const [head, setHead] = useState('');
    const [amount, setAmount] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    useEffect(() => {
        void api.get<{ data: { id: number; name_bn: string; is_active: boolean }[] }>('/fees/heads').then((response) => {
            const active = response.data.filter((candidate) => candidate.is_active);
            setHeads(active);
            setHead((current) => current || String(active[0]?.id ?? ''));
        });
    }, []);

    const save = async () => {
        setErrors({});

        try {
            await api.post('/fees/invoices', { student_id: student?.id, due_date: dueDate || null, items: [{ fee_head_id: Number(head), amount: amount || null }] });
            toast.success(t('core::admin.common.saved'));
            setAmount('');
            onDone();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(error instanceof ApiError && Object.keys(error.errors).length === 0 ? error.message : t('core::admin.common.error'));
        }
    };

    return (
        <Panel className={card} style={{ borderTopColor: 'var(--yellow-400)' }}>
            <strong className={title}>👤 {k('one_title')}</strong>
            <div className="grid gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))' }}>
                <Field label={k('student')} error={errors.student_id?.[0]}>
                    <StudentPicker value={student} onChange={setStudent} />
                </Field>
                <Field label={k('head')} htmlFor="o-head">
                    <NativeSelect id="o-head" value={head} onChange={(event) => setHead(event.target.value)} options={heads.map((candidate) => ({ value: candidate.id, label: candidate.name_bn }))} />
                </Field>
                <Field label={k('amount')} htmlFor="o-amount" error={errors['items.0.amount']?.[0] ?? errors.items?.[0]} hint={k('amount_hint')}>
                    <Input id="o-amount" inputMode="decimal" style={{ fontFamily: 'Poppins, sans-serif' }} value={amount} onChange={(event) => setAmount(event.target.value)} />
                </Field>
                <Field label={k('due_date')} htmlFor="o-due">
                    <Input id="o-due" type="date" value={dueDate} onChange={(event) => setDueDate(event.target.value)} />
                </Field>
            </div>
            <button type="button" className={`${primary} bg-green-700`} disabled={!student || !head} onClick={() => void save()}>
                {k('generate_one')}
            </button>
        </Panel>
    );
}

function WaiveCard({ invoiceId, onInvoice, onDone }: { invoiceId: string; onInvoice: (id: string) => void; onDone: () => void }) {
    const [options, setOptions] = useState<Invoice[]>([]);
    const [reason, setReason] = useState('');
    const [error, setError] = useState(false);

    const load = () => void api.get<{ data: Invoice[] }>('/fees/invoices', { status: 'unpaid', per_page: '100' }).then((response) => setOptions(response.data));

    useEffect(load, []);

    useEffect(() => {
        if (!invoiceId && options[0]) {
            onInvoice(String(options[0].id));
        }
    }, [options, invoiceId, onInvoice]);

    const save = async () => {
        if (!reason.trim()) {
            setError(true);

            return;
        }

        try {
            await api.post(`/fees/invoices/${invoiceId}/waive`, { reason });
            toast.success(k('waived'));
            setReason('');
            onInvoice('');
            load();
            onDone();
        } catch (failure) {
            toast.error(failure instanceof ApiError ? (failure.errors.reason?.[0] ?? failure.message) : t('core::admin.common.error'));
        }
    };

    return (
        <Panel className={card} style={{ borderTopColor: 'var(--info)' }}>
            <strong className={title}>✦ {k('waive_card')}</strong>
            <Field label={k('invoice')} htmlFor="w-invoice">
                <NativeSelect id="w-invoice" value={invoiceId} onChange={(event) => onInvoice(event.target.value)} options={options.map((invoice) => ({ value: invoice.id, label: `${invoice.student.name} — ${invoice.invoice_no} (${formatMoney(invoice.due)})` }))} />
            </Field>
            <Field label={`${k('waive_reason')} *`} htmlFor="w-reason">
                <Textarea id="w-reason" rows={2} placeholder={k('waive_placeholder')} value={reason} onChange={(event) => { setReason(event.target.value); setError(false); }} />
            </Field>
            {error && <Notice tone="danger">{k('reason_required')}</Notice>}
            <button type="button" className={`${primary} bg-info`} disabled={!invoiceId} onClick={() => void save()}>
                {k('waive_button')}
            </button>
        </Panel>
    );
}

/** The invoice screen of the design (the four generation cards), with the full invoice list underneath. */
export function InvoicesPage() {
    const academic = useAcademicOptions();
    const [filters, setFilters] = useState<Record<string, string>>({});
    const [search, setSearch] = useState('');
    const { rows, meta, loading, setPage, reload } = usePaginated<Invoice>('/fees/invoices', { ...filters, search });
    const [waiveId, setWaiveId] = useState('');
    const waiveRef = useRef<HTMLDivElement>(null);
    const generate = can('fees.generate');
    const waive = can('fees.waive');
    const filter = (name: string, value: string) => setFilters({ ...filters, [name]: value });

    return (
        <div className="grid gap-4">
            <PageTitle>{k('title')}</PageTitle>

            {(generate || waive) && (
                <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 420px), 1fr))' }}>
                    {generate && <MonthlyCard onDone={reload} />}
                    {generate && <ExamCard onDone={reload} />}
                    {generate && <OneOffCard onDone={reload} />}
                    {waive && (
                        <div ref={waiveRef} className="contents">
                            <WaiveCard invoiceId={waiveId} onInvoice={setWaiveId} onDone={reload} />
                        </div>
                    )}
                </div>
            )}

            <h2 className="font-head text-[17px] font-bold text-heading">{k('list_title')}</h2>
            <div className="grid gap-2.5 rounded-2xl border border-line bg-surface p-3.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 160px), 1fr))' }}>
                <Input className="sm:col-span-2" placeholder={`🔍 ${t('core::admin.common.search')}`} aria-label={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                <NativeSelect aria-label={t('students::admin.fields.class_id')} placeholder={t('students::admin.fields.class_id')} value={filters.class_id ?? ''} onChange={(event) => filter('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                <NativeSelect aria-label={k('status')} placeholder={k('status')} value={filters.status ?? ''} onChange={(event) => filter('status', event.target.value)} options={['unpaid', 'partial', 'paid', 'waived'].map((value) => ({ value, label: t(`fees::admin.invoice_status.${value}`) }))} />
                <NativeSelect aria-label={k('type')} placeholder={k('type')} value={filters.type ?? ''} onChange={(event) => filter('type', event.target.value)} options={['monthly', 'one_time', 'exam'].map((value) => ({ value, label: t(`fees::admin.types.${value}`) }))} />
                <Input type="month" aria-label={k('month')} value={filters.month ?? ''} onChange={(event) => filter('month', event.target.value)} />
            </div>

            <DataTable
                data={rows}
                loading={loading}
                meta={meta}
                onPageChange={setPage}
                columns={[
                    { header: k('invoice'), cell: ({ row }) => <span className="font-bold">{row.original.invoice_no}</span> },
                    { header: k('student'), cell: ({ row }) => (<div><div>{row.original.student.name}</div><div className="text-xs text-muted-foreground">{row.original.student.student_id} · {row.original.class_name}</div></div>) },
                    { header: k('for'), cell: ({ row }) => (row.original.billing_month ? bnDigits(row.original.billing_month) : t(`fees::admin.types.${row.original.type}`)) },
                    { header: k('due_date'), cell: ({ row }) => formatDate(row.original.due_date) },
                    { header: k('total'), cell: ({ row }) => formatMoney(row.original.total) },
                    { header: k('due'), cell: ({ row }) => <span className={row.original.due > 0 ? 'font-bold text-danger' : ''}>{formatMoney(row.original.due)}</span> },
                    { header: k('status'), cell: ({ row }) => <Badge variant={statusVariant(row.original.status)}>{t(`fees::admin.invoice_status.${row.original.status}`)}</Badge> },
                    {
                        id: 'actions',
                        header: '',
                        cell: ({ row }) =>
                            waive && ['unpaid', 'partial'].includes(row.original.status) ? (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setWaiveId(String(row.original.id));
                                        waiveRef.current?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    }}
                                >
                                    {k('waive')}
                                </Button>
                            ) : null,
                    },
                ]}
            />
        </div>
    );
}
