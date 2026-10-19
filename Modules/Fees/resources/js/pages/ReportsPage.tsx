import { DownloadIcon, FileTextIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { formatMoney } from '@core/js/money';
import { bnDigits, formatDate } from '@core/js/number';

interface Table {
    title: string;
    columns: { key: string; label: string; type: string }[];
    rows: Record<string, string | number | null>[];
    totals: Record<string, number>;
}

const REPORTS = ['cash-book', 'monthly', 'head-wise', 'dues', 'collectors', 'ledger'];
const k = (key: string) => t(`fees::admin.reports.${key}`);

function cell(type: string, value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return type === 'money' ? formatMoney(Number(value)) : type === 'int' ? bnDigits(value) : type === 'date' ? formatDate(String(value)) : String(value);
}

export function ReportsPage() {
    const academic = useAcademicOptions();
    const [report, setReport] = useState('cash-book');
    const [params, setParams] = useState<Record<string, string>>({ from: new Date().toISOString().slice(0, 8) + '01', to: new Date().toISOString().slice(0, 10), month: new Date().toISOString().slice(0, 7) });
    const [student, setStudent] = useState<PickedStudent | null>(null);
    const [table, setTable] = useState<Table | null>(null);

    const query: Record<string, string> = { ...params, ...(report === 'ledger' && student ? { student_id: String(student.id) } : {}) };
    const ready = report !== 'ledger' || student !== null;
    const queryString = new URLSearchParams(Object.entries(query).filter(([, value]) => value)).toString();

    useEffect(() => {
        if (!ready) {
            setTable(null);

            return;
        }

        void api.get<{ data: Table }>(`/fees/reports/${report}`, query).then((response) => setTable(response.data));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [report, queryString, ready]);

    const set = (name: string, value: string) => setParams({ ...params, [name]: value });
    const range = ['cash-book', 'head-wise', 'collectors'].includes(report);

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            <div className="flex flex-wrap items-center gap-2">
                <NativeSelect className="w-auto" value={report} onChange={(event) => setReport(event.target.value)} options={REPORTS.map((value) => ({ value, label: t(`fees::reports.${value.replace('-', '_')}`) }))} />
                {range && (
                    <>
                        <Input className="w-auto" type="date" aria-label={k('from')} value={params.from} onChange={(event) => set('from', event.target.value)} />
                        <Input className="w-auto" type="date" aria-label={k('to')} value={params.to} onChange={(event) => set('to', event.target.value)} />
                    </>
                )}
                {report === 'monthly' && <Input className="w-auto" type="month" value={params.month} onChange={(event) => set('month', event.target.value)} />}
                {report === 'dues' && <NativeSelect className="w-auto" placeholder={t('students::admin.fields.class_id')} value={params.class_id ?? ''} onChange={(event) => set('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />}
                {report === 'ledger' && <div className="min-w-72"><StudentPicker value={student} onChange={setStudent} /></div>}
                {ready && (
                    <>
                        <Button variant="outline" size="sm" render={<a href={`/api/fees/reports/${report}/export?format=pdf&${queryString}`} target="_blank" rel="noreferrer" />}>
                            <FileTextIcon /> PDF
                        </Button>
                        <Button variant="outline" size="sm" render={<a href={`/api/fees/reports/${report}/export?format=xlsx&${queryString}`} />}>
                            <DownloadIcon /> Excel
                        </Button>
                    </>
                )}
            </div>

            {table && (
                <div className="overflow-x-auto rounded-xl border bg-card">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-muted-foreground">
                                {table.columns.map((column) => (
                                    <th key={column.key} className={`p-3 ${['money', 'int'].includes(column.type) ? 'text-right' : ''}`}>{column.label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {table.rows.length === 0 && (
                                <tr>
                                    <td colSpan={table.columns.length} className="p-6 text-center text-muted-foreground">{t('core::admin.common.empty')}</td>
                                </tr>
                            )}
                            {table.rows.map((row, index) => (
                                <tr key={index} className="border-b last:border-0">
                                    {table.columns.map((column) => (
                                        <td key={column.key} className={`p-3 ${['money', 'int'].includes(column.type) ? 'text-right' : ''}`}>{cell(column.type, row[column.key])}</td>
                                    ))}
                                </tr>
                            ))}
                            {Object.keys(table.totals).length > 0 && (
                                <tr className="bg-muted/50 font-semibold">
                                    {table.columns.map((column, index) => (
                                        <td key={column.key} className={`p-3 ${['money', 'int'].includes(column.type) ? 'text-right' : ''}`}>
                                            {column.key in table.totals ? cell(column.type, table.totals[column.key]) : index === 0 ? t('fees::reports.total') : ''}
                                        </td>
                                    ))}
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
