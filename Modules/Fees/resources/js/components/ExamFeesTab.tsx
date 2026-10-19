import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
        <div className="space-y-4">
            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="p-3">{t('students::admin.fields.class_id')}</th>
                            <th className="p-3">{k('amount')}</th>
                            <th className="p-3">{k('due_date')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row, index) => (
                            <tr key={row.class_id} className="border-b last:border-0">
                                <td className="p-3 font-medium">{row.class_name}</td>
                                <td className="p-3"><Input className="w-32" inputMode="decimal" value={row.amount} onChange={(event) => update(index, { amount: event.target.value })} /></td>
                                <td className="p-3"><Input className="w-44" type="date" value={row.due_date ?? ''} onChange={(event) => update(index, { due_date: event.target.value })} /></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            <div className="flex flex-wrap items-center gap-2">
                {can('fees.manage_structure') && <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>}
                {can('fees.generate') && (
                    <>
                        <Button variant="outline" onClick={() => void generate(false)}>{k('preview')}</Button>
                        <Button variant="outline" disabled={!summary || summary.created === 0} onClick={() => void generate(true)}>{k('generate')}</Button>
                    </>
                )}
                {summary && <span className="text-sm text-muted-foreground">{k('summary', { created: summary.created, existing: summary.skipped_existing })}</span>}
            </div>
        </div>
    );
}
