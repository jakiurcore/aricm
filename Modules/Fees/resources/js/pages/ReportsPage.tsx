import { useEffect, useState } from 'react';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { StudentPicker, type PickedStudent } from '@students/js/components/StudentPicker';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { api } from '@core/js/api';
import { Chip, DashedEmpty, DesignTable, PageTitle, TableCard, Td, Th } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
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

    const date = 'h-11 w-full min-w-0 rounded-xl border border-line-strong bg-surface px-3 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400';
    const align = (type: string) => (['money', 'int'].includes(type) ? 'right' : 'left');
    const summaryTone = (key: string) => (['outstanding', 'due'].includes(key) ? { bg: 'var(--danger-bg)', fg: 'var(--danger)' } : ['collected', 'amount', 'total'].includes(key) ? { bg: 'var(--success-bg)', fg: 'var(--success)' } : { bg: 'var(--surface)', fg: 'var(--ink)' });
    const cards = table ? table.columns.filter((column) => column.type === 'money' && column.key in table.totals) : [];

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>

            <div role="tablist" className="flex gap-1.5 overflow-x-auto pb-0.5">
                {REPORTS.map((value) => (
                    <Chip key={value} role="tab" aria-selected={report === value} size={42} active={report === value} onClick={() => setReport(value)}>
                        {k(`names.${value.replace('-', '_')}`)}
                    </Chip>
                ))}
            </div>

            <div className="flex flex-wrap items-end gap-2.5">
                {range && (
                    <>
                        <Field label={k('from')} htmlFor="r-from"><input id="r-from" className={`${date} flex-[0_1_170px]`} type="date" value={params.from} onChange={(event) => set('from', event.target.value)} /></Field>
                        <Field label={k('to')} htmlFor="r-to"><input id="r-to" className={`${date} flex-[0_1_170px]`} type="date" value={params.to} onChange={(event) => set('to', event.target.value)} /></Field>
                    </>
                )}
                {report === 'monthly' && <Field label={k('month')} htmlFor="r-month"><input id="r-month" className={date} type="month" value={params.month} onChange={(event) => set('month', event.target.value)} /></Field>}
                {report === 'dues' && (
                    <div className="grid flex-[0_1_180px] gap-1">
                        <Label htmlFor="r-class">{t('students::admin.fields.class_id')}</Label>
                        <NativeSelect id="r-class" placeholder={k('all_classes')} value={params.class_id ?? ''} onChange={(event) => set('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                    </div>
                )}
                {report === 'ledger' && (
                    <div className="grid min-w-[220px] flex-[1_1_220px] gap-1">
                        <Label>{k('student')}</Label>
                        <StudentPicker value={student} onChange={setStudent} />
                    </div>
                )}
                <span className="flex-1" />
                {ready && (
                    <>
                        <Button variant="outline" render={<a href={`/api/fees/reports/${report}/export?format=pdf&${queryString}`} target="_blank" rel="noreferrer" />}>
                            ⬇ PDF
                        </Button>
                        <Button variant="outline" render={<a href={`/api/fees/reports/${report}/export?format=xlsx&${queryString}`} />}>
                            ⬇ Excel
                        </Button>
                    </>
                )}
            </div>

            {!ready && <DashedEmpty glyph="🔍" title={k('pick_student')} />}
            {ready && !table && <LoadingState rows={2} height={120} />}

            {table && cards.length > 0 && (
                <div className="grid gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))' }}>
                    {cards.slice(0, 3).map((column) => {
                        const tone = summaryTone(column.key);

                        return (
                            <div key={column.key} className="grid rounded-2xl px-3.5 py-3" style={{ background: tone.bg }}>
                                <span className="text-[13px] font-semibold text-muted-foreground">{column.label}</span>
                                <strong className="font-head text-[22px] leading-[1.35]" style={{ color: tone.fg }}>
                                    {cell(column.type, table.totals[column.key])}
                                </strong>
                            </div>
                        );
                    })}
                </div>
            )}

            {table && (
                <TableCard>
                    <DesignTable minWidth={720} className="text-sm">
                        <thead>
                            <tr className="bg-surface-2">
                                {table.columns.map((column) => (
                                    <Th key={column.key} align={align(column.type)}>
                                        {column.label}
                                    </Th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {table.rows.length === 0 && (
                                <tr>
                                    <td colSpan={table.columns.length} className="p-6 text-center text-muted-foreground">
                                        {t('core::admin.common.empty')}
                                    </td>
                                </tr>
                            )}
                            {table.rows.map((row, index) => (
                                <tr key={index} className="border-t border-line">
                                    {table.columns.map((column) => (
                                        <Td key={column.key} align={align(column.type)} className={['money', 'int'].includes(column.type) ? 'whitespace-nowrap' : undefined}>
                                            {cell(column.type, row[column.key])}
                                        </Td>
                                    ))}
                                </tr>
                            ))}
                            {Object.keys(table.totals).length > 0 && (
                                <tr className="border-t-2 border-line-strong bg-surface-2">
                                    {table.columns.map((column, index) => (
                                        <Td key={column.key} align={align(column.type)} className="font-bold whitespace-nowrap">
                                            {column.key in table.totals ? cell(column.type, table.totals[column.key]) : index === 0 ? k('total') : ''}
                                        </Td>
                                    ))}
                                </tr>
                            )}
                        </tbody>
                    </DesignTable>
                </TableCard>
            )}
        </div>
    );
}
