import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { t } from '@core/js/i18n';
import { poishaToTaka } from '@core/js/money';
import { can } from '@core/js/permissions';

interface Row {
    class_id: number;
    class_name: string;
    amount: number | string;
    due_date: string | null;
}

interface Summary {
    created: number;
    skipped_existing: number;
    total_amount: number;
}

const field = 'h-11 min-w-0 rounded-[10px] border border-line-strong bg-surface px-2.5 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400';
const k = (key: string, replacements: Record<string, string | number> = {}) => t(`fees::admin.exam_fees.${key}`, replacements);

/** The "ফি" tab on an exam: amount per class, then bill the students. Added to the exam page through Exams' tab registry. */
export function ExamFeesTab({ examId }: { examId: number }) {
    const [rows, setRows] = useState<Row[]>([]);
    const [summary, setSummary] = useState<Summary | null>(null);

    const load = useCallback(async () => {
        const response = await api.get<{ data: Row[] }>(`/fees/exams/${examId}/fees`);
        setRows(response.data.map((row) => ({ ...row, amount: poishaToTaka(Number(row.amount)) })));
    }, [examId]);

    useEffect(() => {
        void load();
    }, [load]);

    const update = (index: number, patch: Partial<Row>) => setRows(rows.map((row, at) => (at === index ? { ...row, ...patch } : row)));

    const save = async () => {
        try {
            await api.put(`/fees/exams/${examId}/fees`, { rows: rows.map((row) => ({ class_id: row.class_id, amount: row.amount, due_date: row.due_date || null })) });
            toast.success(t('core::admin.common.saved'));
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    const generate = async (confirm: boolean) => {
        try {
            const response = await api.post<{ data: Summary }>('/fees/invoices/generate/exam', { exam_id: examId, confirm });
            setSummary(confirm ? null : response.data);

            if (confirm) {
                toast.success(k('generated', { count: response.data.created }));
            }
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <div className="grid gap-3.5">
            <div className="max-w-[640px] overflow-hidden rounded-2xl border border-line bg-surface">
                {rows.map((row, index) => (
                    <div key={row.class_id} className="flex flex-wrap items-center gap-x-3 gap-y-1.5 border-b border-line px-4 py-2">
                        <span className="min-w-24 flex-1 font-semibold">{row.class_name}</span>
                        <span className="flex items-center gap-3">
                            <span className="text-muted-foreground">৳</span>
                            <input className={field} style={{ width: 110 }} inputMode="decimal" aria-label={`${row.class_name} ${k('amount')}`} value={row.amount} onChange={(event) => update(index, { amount: event.target.value })} />
                        </span>
                        <input className={field} style={{ width: 150 }} type="date" aria-label={`${row.class_name} ${k('due_date')}`} value={row.due_date ?? ''} onChange={(event) => update(index, { due_date: event.target.value })} />
                    </div>
                ))}
                {can('fees.manage_structure') && (
                    <div className="flex justify-end px-4 py-3">
                        <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
                    </div>
                )}
            </div>
            {can('fees.generate') && (
                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="outline" onClick={() => void generate(false)}>
                        {k('preview')}
                    </Button>
                    <Button className="bg-navy-800 text-white hover:bg-navy-800" disabled={!summary || summary.created === 0} onClick={() => void generate(true)}>
                        {k('generate')}
                    </Button>
                    {summary && <span className="text-sm text-muted-foreground">{k('summary', { created: summary.created, existing: summary.skipped_existing })}</span>}
                </div>
            )}
        </div>
    );
}
