import { PrinterIcon } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import { toast } from 'sonner';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
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
    student: Found & { photo_url: string | null };
    invoices: Invoice[];
    credit: number;
    total_due: number;
    recent_payments: { id: number; receipt_no: string | null; amount: number; method: string; status: string; paid_at: string | null }[];
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.collection.${key}`, replacements);
const methods = ['cash', 'bkash', 'nagad', 'rocket', 'bank'];

/** Counter desk: find a student, tick what they are paying for, take the money, print the receipt. Keyboard first. */
export function CollectionPage() {
    const [term, setTerm] = useState('');
    const [found, setFound] = useState<Found[]>([]);
    const [desk, setDesk] = useState<Desk | null>(null);
    const [selected, setSelected] = useState<number[]>([]);
    const [amount, setAmount] = useState('');
    const [method, setMethod] = useState('cash');
    const [transactionId, setTransactionId] = useState('');
    const [note, setNote] = useState('');
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const searchRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        searchRef.current?.focus();
    }, []);

    useEffect(() => {
        if (term.trim().length < 2) {
            setFound([]);

            return;
        }

        const timer = setTimeout(() => void api.get<{ data: Found[] }>('/fees/collection/search', { q: term }).then((response) => setFound(response.data)).catch(() => setFound([])), 250);

        return () => clearTimeout(timer);
    }, [term]);

    const open = async (student: Found) => {
        const response = await api.get<{ data: Desk }>(`/fees/collection/students/${student.id}`);
        setDesk(response.data);
        setSelected(response.data.invoices.map((invoice) => invoice.id));
        setFound([]);
        setTerm('');
        setErrors({});
    };

    const chosenTotal = desk ? desk.invoices.filter((invoice) => selected.includes(invoice.id)).reduce((sum, invoice) => sum + invoice.due, 0) : 0;

    // The amount follows the ticked invoices until the cashier types their own.
    const [manual, setManual] = useState(false);
    useEffect(() => {
        if (!manual) {
            setAmount(chosenTotal ? String(chosenTotal / 100) : '');
        }
    }, [chosenTotal, manual]);

    const reset = () => {
        setDesk(null);
        setSelected([]);
        setAmount('');
        setManual(false);
        setTransactionId('');
        setNote('');
        setMethod('cash');
        setTimeout(() => searchRef.current?.focus(), 50);
    };

    const pay = async () => {
        if (!desk) {
            return;
        }

        setBusy(true);
        setErrors({});

        try {
            const response = await api.post<{ data: { receipt_url: string; receipt_no: string } }>('/fees/collection/pay', { student_id: desk.student.id, invoice_ids: selected, amount, method, transaction_id: transactionId, note });
            toast.success(k('collected', { receipt: response.data.receipt_no }));
            window.open(response.data.receipt_url, '_blank', 'noopener');
            reset();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
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

    return (
        <div className="max-w-5xl space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            {!desk && (
                <div className="space-y-2">
                    <Input ref={searchRef} className="h-12 max-w-xl text-base" placeholder={k('search')} value={term} onChange={(event) => setTerm(event.target.value)} onKeyDown={searchKey} />
                    <ul className="max-w-xl divide-y rounded-xl border bg-card">
                        {found.map((student, index) => (
                            <li key={student.id}>
                                <button type="button" onClick={() => void open(student)} className={`flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-muted ${index === 0 ? 'bg-muted/40' : ''}`}>
                                    <span className="flex items-center gap-3">
                                        <Avatar>
                                            {student.photo_url && <AvatarImage src={student.photo_url} alt="" />}
                                            <AvatarFallback>{student.name.slice(0, 1)}</AvatarFallback>
                                        </Avatar>
                                        <span>
                                            <span className="block font-medium">{student.name}</span>
                                            <span className="text-xs text-muted-foreground">{student.student_id} · {student.class} · {t('students::admin.fields.roll')} {student.roll ? bnDigits(student.roll) : '—'}</span>
                                        </span>
                                    </span>
                                    <span className={student.due > 0 ? 'font-semibold text-destructive' : 'text-muted-foreground'}>{student.due > 0 ? formatMoney(student.due) : k('no_due')}</span>
                                </button>
                            </li>
                        ))}
                        {term.trim().length >= 2 && found.length === 0 && <li className="px-4 py-3 text-sm text-muted-foreground">{t('core::admin.common.empty')}</li>}
                    </ul>
                </div>
            )}

            {desk && (
                <div className="grid gap-4 lg:grid-cols-[1fr_22rem]">
                    <div className="space-y-3">
                        <div className="flex items-center justify-between rounded-xl border bg-card p-4">
                            <div className="flex items-center gap-3">
                                <Avatar className="size-12">
                                    {desk.student.photo_url && <AvatarImage src={desk.student.photo_url} alt="" />}
                                    <AvatarFallback>{desk.student.name.slice(0, 1)}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <div className="font-semibold">{desk.student.name}</div>
                                    <div className="text-xs text-muted-foreground">{desk.student.student_id} · {desk.student.class} · {desk.student.guardian_phone ? bnDigits(desk.student.guardian_phone) : ''}</div>
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" onClick={reset}>
                                {k('change_student')}
                            </Button>
                        </div>

                        <div className="overflow-hidden rounded-xl border bg-card">
                            {desk.invoices.length === 0 && <p className="p-4 text-sm text-muted-foreground">{k('no_open_invoices')}</p>}
                            {desk.invoices.map((invoice) => (
                                <label key={invoice.id} className="flex cursor-pointer items-center justify-between gap-3 border-b px-4 py-3 last:border-0 hover:bg-muted/40">
                                    <span className="flex items-center gap-3">
                                        <Checkbox checked={selected.includes(invoice.id)} onCheckedChange={(checked) => { setManual(false); setSelected(checked ? [...selected, invoice.id] : selected.filter((id) => id !== invoice.id)); }} />
                                        <span>
                                            <span className="block font-medium">{invoice.billing_month ? bnDigits(invoice.billing_month) : t(`fees::admin.types.${invoice.type}`)} <span className="text-xs text-muted-foreground">{invoice.invoice_no}</span></span>
                                            <span className="text-xs text-muted-foreground">{invoice.items.map((item) => item.head).join(', ')} · {t('fees::admin.invoices.due_date')}: {formatDate(invoice.due_date)}</span>
                                        </span>
                                    </span>
                                    <span className="text-right">
                                        <span className="block font-semibold">{formatMoney(invoice.due)}</span>
                                        {invoice.late_fine > 0 && <Badge variant="destructive">{k('late_fine', { amount: formatMoney(invoice.late_fine) })}</Badge>}
                                    </span>
                                </label>
                            ))}
                        </div>
                        {desk.credit > 0 && <p className="text-sm text-green-700">{k('credit', { amount: formatMoney(desk.credit) })}</p>}
                    </div>

                    <div className="space-y-3 rounded-xl border bg-card p-4">
                        <div className="flex items-baseline justify-between">
                            <span className="text-sm text-muted-foreground">{k('selected')}</span>
                            <span className="text-xl font-semibold">{formatMoney(chosenTotal)}</span>
                        </div>
                        <Field label={k('amount')} htmlFor="pay-amount" error={errors.amount?.[0]}>
                            <Input id="pay-amount" className="h-11 text-lg" inputMode="decimal" value={amount} onChange={(event) => { setManual(true); setAmount(event.target.value); }} onKeyDown={submitKey} />
                        </Field>
                        <Field label={k('method')} htmlFor="pay-method" error={errors.method?.[0]}>
                            <NativeSelect id="pay-method" value={method} onChange={(event) => setMethod(event.target.value)} options={methods.map((value) => ({ value, label: t(`fees::messages.payment_method.${value}`) }))} />
                        </Field>
                        {method !== 'cash' && (
                            <Field label={k('transaction_id')} htmlFor="pay-txn" error={errors.transaction_id?.[0]}>
                                <Input id="pay-txn" value={transactionId} onChange={(event) => setTransactionId(event.target.value)} onKeyDown={submitKey} />
                            </Field>
                        )}
                        <Field label={k('note')} htmlFor="pay-note">
                            <Input id="pay-note" value={note} onChange={(event) => setNote(event.target.value)} onKeyDown={submitKey} />
                        </Field>
                        <Button className="h-11 w-full" disabled={busy || !amount} onClick={() => void pay()}>
                            <PrinterIcon /> {k('collect_print')}
                        </Button>
                        <p className="text-center text-xs text-muted-foreground">{k('enter_hint')}</p>
                    </div>
                </div>
            )}
        </div>
    );
}
