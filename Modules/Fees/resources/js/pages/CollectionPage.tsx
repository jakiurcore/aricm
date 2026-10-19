import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import { toast } from 'sonner';

import { api, ApiError } from '@core/js/api';
import { DashedEmpty, Notice, PageTitle, Panel } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';

interface Found {
    id: number;
    student_id: string;
    name: string;
    class: string | null;
    roll: number | null;
    guardian_phone: string | null;
    photo_url: string | null;
    due: number;
}

interface Invoice {
    id: number;
    invoice_no: string;
    billing_month: string | null;
    type: string;
    due_date: string | null;
    total: number;
    due: number;
    late_fine: number;
    status: string;
    items: { head: string }[];
}

interface Desk {
    student: Found;
    invoices: Invoice[];
    credit: number;
    total_due: number;
}

interface Done {
    amount: number;
    receipt_no: string;
    receipt_url: string;
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.collection.${key}`, replacements);
const methods = ['cash', 'bkash', 'nagad', 'rocket', 'bank'];
const field = 'h-11 w-full min-w-0 rounded-xl border border-line-strong bg-surface px-3 text-base text-foreground outline-none placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 aria-invalid:border-danger';
const linkButton = 'inline-flex h-11 items-center gap-1.5 rounded-full px-4 font-semibold whitespace-nowrap no-underline';

/**
 * The collection desk of the design. Left: a big search box over a list of 60px student cards. Right: the picked student,
 * their open invoices (tick what is being paid), the amount / method form and the red collect button; afterwards a green
 * "collected" card with the receipt links. Keyboard first: Enter picks the first match and submits the form.
 */
export function CollectionPage() {
    const [term, setTerm] = useState('');
    const [found, setFound] = useState<Found[]>([]);
    const [searched, setSearched] = useState(false);
    const [desk, setDesk] = useState<Desk | null>(null);
    const [selected, setSelected] = useState<number[]>([]);
    const [amount, setAmount] = useState('');
    const [method, setMethod] = useState('cash');
    const [transactionId, setTransactionId] = useState('');
    const [note, setNote] = useState('');
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [done, setDone] = useState<Done | null>(null);
    const [manual, setManual] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        searchRef.current?.focus();
    }, []);

    useEffect(() => {
        if (term.trim().length < 2) {
            setFound([]);
            setSearched(false);

            return;
        }

        const timer = setTimeout(
            () =>
                void api
                    .get<{ data: Found[] }>('/fees/collection/search', { q: term })
                    .then((response) => setFound(response.data))
                    .catch(() => setFound([]))
                    .finally(() => setSearched(true)),
            250,
        );

        return () => clearTimeout(timer);
    }, [term]);

    const open = async (student: Found) => {
        const response = await api.get<{ data: Desk }>(`/fees/collection/students/${student.id}`);
        setDesk(response.data);
        setSelected(response.data.invoices.map((invoice) => invoice.id));
        setDone(null);
        setManual(false);
        setErrors({});
        setMethod('cash');
        setTransactionId('');
        setNote('');
    };

    const chosenTotal = desk ? desk.invoices.filter((invoice) => selected.includes(invoice.id)).reduce((sum, invoice) => sum + invoice.due, 0) : 0;

    // The amount follows the ticked invoices until the cashier types their own.
    useEffect(() => {
        if (!manual) {
            setAmount(chosenTotal ? String(chosenTotal / 100) : '');
        }
    }, [chosenTotal, manual]);

    const next = () => {
        setDesk(null);
        setDone(null);
        setSelected([]);
        setAmount('');
        setManual(false);
        setTerm('');
        setFound([]);
        setTimeout(() => searchRef.current?.focus(), 50);
    };

    const pay = async () => {
        if (!desk || busy || !amount) {
            return;
        }

        setBusy(true);
        setErrors({});

        try {
            const response = await api.post<{ data: { receipt_url: string; receipt_no: string; amount: number } }>('/fees/collection/pay', { student_id: desk.student.id, invoice_ids: selected, amount, method, transaction_id: transactionId, note });
            toast.success(k('collected', { receipt: response.data.receipt_no }));
            setDone({ amount: response.data.amount, receipt_no: response.data.receipt_no, receipt_url: response.data.receipt_url });
            // Refresh what the student still owes, in case another receipt follows.
            const refreshed = await api.get<{ data: Desk }>(`/fees/collection/students/${desk.student.id}`);
            setDesk(refreshed.data);
            setSelected(refreshed.data.invoices.map((invoice) => invoice.id));
            setManual(false);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(error instanceof ApiError && Object.keys(error.errors).length === 0 ? error.message : t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    const searchKey = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter' && found[0]) {
            void open(found[0]);
        }
    };

    const submitKey = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            void pay();
        }
    };

    const amountPoisha = Math.round((Number(amount) || 0) * 100);
    const needTransaction = method !== 'cash';
    const amountNote = !desk || !amount ? null : amountPoisha > chosenTotal ? { tone: 'info' as const, message: k('advance_note', { amount: formatMoney(amountPoisha - chosenTotal) }) } : amountPoisha < chosenTotal ? { tone: 'warn' as const, message: k('partial_note', { amount: formatMoney(chosenTotal - amountPoisha) }) } : null;

    return (
        <div className="grid items-start gap-4 lg:grid-cols-[minmax(280px,360px)_minmax(0,1fr)]">
            <PageTitle>{k('title')}</PageTitle>

            <div className="grid min-w-0 gap-2.5">
                <input ref={searchRef} className={`${field} h-[52px] text-[17px]`} placeholder={k('search')} aria-label={k('search_label')} value={term} onChange={(event) => setTerm(event.target.value)} onKeyDown={searchKey} />
                <span className="text-[13px] text-muted-foreground">{term.trim().length < 2 ? k('search_hint') : k('count', { count: bnDigits(found.length) })}</span>
                <div className="grid max-h-[70vh] gap-1.5 overflow-y-auto">
                    {found.map((student) => {
                        const on = desk?.student.id === student.id;

                        return (
                            <button key={student.id} type="button" onClick={() => void open(student)} className="flex min-h-[60px] cursor-pointer items-center gap-2.5 rounded-[14px] border-2 px-3 py-2 text-left text-ink" style={{ borderColor: on ? 'var(--green-700)' : 'var(--line)', background: on ? 'var(--green-50)' : 'var(--surface)' }}>
                                {student.photo_url ? (
                                    <img src={student.photo_url} alt="" className="size-10 flex-none rounded-full object-cover" />
                                ) : (
                                    <span className="grid size-10 flex-none place-items-center rounded-full bg-green-100 font-bold text-green-700">{student.name.replace(/^(মোহাম্মদ|মুহাম্মদ|মোঃ) /, '').slice(0, 1)}</span>
                                )}
                                <span className="grid min-w-0 flex-1 leading-[1.35]">
                                    <strong className="text-[15px]">{student.name}</strong>
                                    <span className="text-xs text-muted-foreground">
                                        {student.student_id} · {student.class} · {t('students::admin.fields.roll')} {student.roll ? bnDigits(student.roll) : '—'}
                                    </span>
                                </span>
                                <span className="text-sm font-bold whitespace-nowrap" style={{ color: student.due > 0 ? 'var(--danger)' : 'var(--success)' }}>
                                    {student.due > 0 ? formatMoney(student.due) : '✓'}
                                </span>
                            </button>
                        );
                    })}
                    {searched && found.length === 0 && <div className="rounded-[14px] border-2 border-dashed border-input p-5 text-center text-muted-foreground">{k('none_found')}</div>}
                </div>
            </div>

            <div className="grid min-w-0 gap-3">
                {!desk && <DashedEmpty className="px-5 py-10" glyph="🔍" title={k('pick_title')} description={k('pick_body')} />}
                {desk && (
                    <div className="grid gap-3">
                        <Panel className="flex flex-wrap items-center gap-3.5">
                            {desk.student.photo_url ? <img src={desk.student.photo_url} alt="" className="size-[72px] flex-none rounded-[18px] object-cover" /> : <span className="grid size-[72px] flex-none place-items-center rounded-[18px] bg-surface-2 text-2xl font-bold text-muted-foreground">{desk.student.name.slice(0, 1)}</span>}
                            <span className="grid min-w-[180px] flex-1 leading-[1.45]">
                                <strong className="text-[19px]">{desk.student.name}</strong>
                                <span className="text-sm text-muted-foreground">
                                    {desk.student.student_id} · {desk.student.class} · {t('students::admin.fields.roll')} {desk.student.roll ? bnDigits(desk.student.roll) : '—'}
                                </span>
                                {desk.student.guardian_phone && <span className="text-sm">{bnDigits(desk.student.guardian_phone)}</span>}
                            </span>
                            <span className="grid text-right leading-[1.3]">
                                <span className="text-[13px] font-bold text-muted-foreground">{k('total_due')}</span>
                                <strong className="font-head text-[30px]" style={{ color: desk.total_due > 0 ? 'var(--danger)' : 'var(--success)' }}>
                                    {formatMoney(desk.total_due)}
                                </strong>
                            </span>
                        </Panel>

                        {desk.credit > 0 && <Notice tone="info">{k('credit', { amount: formatMoney(desk.credit) })}</Notice>}

                        {done && (
                            <div className="grid justify-items-center gap-2.5 rounded-[20px] border-2 p-5 text-center" style={{ background: 'var(--success-bg)', borderColor: 'var(--success)' }}>
                                <span className="grid size-14 place-items-center rounded-full text-[28px] text-white" style={{ background: 'var(--success)' }}>
                                    ✓
                                </span>
                                <strong className="text-[19px]" style={{ color: 'var(--success)' }}>
                                    {k('done_title', { amount: formatMoney(done.amount) })}
                                </strong>
                                <span>
                                    {k('receipt_no')} <strong style={{ fontFamily: 'Poppins, sans-serif' }}>{done.receipt_no}</strong>
                                </span>
                                <span className="flex flex-wrap justify-center gap-2">
                                    <a href={`${done.receipt_url}?format=thermal`} target="_blank" rel="noreferrer" className={`${linkButton} bg-green-700 font-bold text-white`}>
                                        🖨 {k('receipt_thermal')}
                                    </a>
                                    <a href={`${done.receipt_url}?format=a5`} target="_blank" rel="noreferrer" className={`${linkButton} border border-line-strong bg-surface text-ink`}>
                                        🖨 {k('receipt_a5')}
                                    </a>
                                    <button type="button" onClick={next} className={`${linkButton} cursor-pointer border border-line-strong bg-surface text-ink`}>
                                        {k('next_student')}
                                    </button>
                                </span>
                            </div>
                        )}

                        {desk.invoices.length > 0 || !done ? (
                            <div className="grid gap-3">
                                <div className="overflow-hidden rounded-[20px] border border-line bg-surface">
                                    <div className="bg-surface-2 px-4 py-3 font-bold">{k('open_invoices')}</div>
                                    {desk.invoices.map((invoice) => {
                                        const on = selected.includes(invoice.id);

                                        return (
                                            <button
                                                key={invoice.id}
                                                type="button"
                                                role="checkbox"
                                                aria-checked={on}
                                                onClick={() => {
                                                    setManual(false);
                                                    setSelected(on ? selected.filter((id) => id !== invoice.id) : [...selected, invoice.id]);
                                                }}
                                                className="flex min-h-[54px] w-full cursor-pointer items-center gap-3 border-0 border-t border-line px-4 py-2 text-left text-ink"
                                                style={{ background: on ? 'var(--green-50)' : 'var(--surface)' }}
                                            >
                                                <span className="grid size-6 flex-none place-items-center rounded-[7px] border-2 text-[13px] font-bold text-white" style={{ borderColor: on ? 'var(--green-700)' : 'var(--line-strong)', background: on ? 'var(--green-700)' : 'transparent' }}>
                                                    {on ? '✓' : ''}
                                                </span>
                                                <span className="grid flex-1 leading-[1.35]">
                                                    <strong>{invoice.billing_month ? bnDigits(invoice.billing_month) : t(`fees::admin.types.${invoice.type}`)}</strong>
                                                    <span className="text-xs text-muted-foreground">
                                                        {invoice.invoice_no} · {invoice.items.map((item) => item.head).join(', ')} · {t('fees::admin.invoices.due_date')}: {formatDate(invoice.due_date)}
                                                        {invoice.late_fine > 0 && <span className="font-bold text-danger"> · {k('late_fine', { amount: formatMoney(invoice.late_fine) })}</span>}
                                                    </span>
                                                </span>
                                                <strong className="whitespace-nowrap">{formatMoney(invoice.due)}</strong>
                                            </button>
                                        );
                                    })}
                                    {desk.invoices.length === 0 && <div className="border-t border-line px-4 py-3.5 font-bold" style={{ color: 'var(--success)' }}>{k('no_open_invoices')} 🎉</div>}
                                </div>

                                <Panel>
                                    <div className="grid items-end gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 200px), 1fr))' }}>
                                        <Field label={`${k('amount')} (${k('payable')} ${formatMoney(chosenTotal)})`} htmlFor="pay-amount" error={errors.amount?.[0]}>
                                            <input id="pay-amount" inputMode="decimal" className={`${field} h-[52px] text-[22px] font-bold`} style={{ fontFamily: 'Poppins, sans-serif' }} value={amount} onChange={(event) => { setManual(true); setAmount(event.target.value.replace(/[০-৯]/g, (digit) => String('০১২৩৪৫৬৭৮৯'.indexOf(digit)))); }} onKeyDown={submitKey} />
                                        </Field>
                                        <div className="col-span-full grid gap-1 text-sm font-semibold">
                                            {k('method')}
                                            <div role="group" aria-label={k('method')} className="grid gap-1" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(72px, 1fr))' }}>
                                                {methods.map((value) => {
                                                    const on = method === value;

                                                    return (
                                                        <button key={value} type="button" aria-pressed={on} onClick={() => setMethod(value)} className="h-11 cursor-pointer rounded-[10px] border-2 text-[13px] font-bold" style={{ borderColor: on ? 'var(--green-700)' : 'var(--line-strong)', background: on ? 'var(--green-700)' : 'var(--surface)', color: on ? '#fff' : 'var(--ink)' }}>
                                                            {t(`fees::admin.payment_method.${value}`)}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                        {needTransaction && (
                                            <Field label={`${k('transaction_id')} *`} htmlFor="pay-txn" error={errors.transaction_id?.[0]}>
                                                <input id="pay-txn" className={field} style={{ fontFamily: 'Poppins, sans-serif' }} value={transactionId} onChange={(event) => setTransactionId(event.target.value)} onKeyDown={submitKey} />
                                            </Field>
                                        )}
                                        <Field label={k('note')} htmlFor="pay-note">
                                            <input id="pay-note" className={field} placeholder={k('optional')} value={note} onChange={(event) => setNote(event.target.value)} onKeyDown={submitKey} />
                                        </Field>
                                    </div>
                                    {amountNote && <Notice tone={amountNote.tone}>{amountNote.message}</Notice>}
                                    <button type="button" disabled={busy || !amount || selected.length === 0} onClick={() => void pay()} className="h-14 cursor-pointer rounded-full border-0 bg-red-600 text-lg font-bold whitespace-nowrap text-white disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint">
                                        {k('collect')} {amount ? formatMoney(amountPoisha) : ''}
                                    </button>
                                    <p className="text-center text-xs text-muted-foreground">{k('enter_hint')}</p>
                                </Panel>
                            </div>
                        ) : null}
                    </div>
                )}
            </div>
        </div>
    );
}
