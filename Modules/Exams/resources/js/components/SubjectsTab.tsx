import { PlusIcon, XIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { api, ApiError } from '@core/js/api';
import { DashedEmpty, DesignTable, TableCard, Td, Th, toAsciiDigits } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
import { locale, t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Component {
    id?: number;
    name_bn: string;
    name_en?: string | null;
    full_marks: number | string;
    pass_marks: number | string;
}

interface Row {
    subject_id: number;
    name: string;
    full_marks: number | string;
    pass_marks: number | string;
    include_in_gpa: boolean;
    is_optional: boolean;
    components: Component[];
}

interface Setup {
    subjects: Row[];
    class_subjects: { id: number; name: string }[];
    other_exams: { id: number; name: string }[];
}

const k = (key: string) => t(`exams::admin.subjects.${key}`);

/** The usual parts of a paper, in the design's column order. Any other part a class already uses gets a column after them. */
const PARTS = [
    { bn: 'লিখিত', en: 'Written' },
    { bn: 'MCQ', en: 'MCQ' },
    { bn: 'মৌখিক', en: 'Oral' },
    { bn: 'ব্যবহারিক', en: 'Practical' },
];

const cellInput = 'h-10 w-[76px] min-h-0 rounded-[10px] border border-line-strong bg-surface px-2 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:bg-surface-2 disabled:text-faint aria-invalid:border-danger';

/**
 * The "subjects & marks" table of the design: subject, full marks, pass marks, then one pill per part (written, MCQ, oral,
 * practical) showing that part's marks or a dash. The pills are inputs: type a number to switch a part on, clear it to switch it off.
 */
export function SubjectsTab({ examId, classId }: { examId: number; classId: number }) {
    const [setup, setSetup] = useState<Setup | null>(null);
    const [rows, setRows] = useState<Row[]>([]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [source, setSource] = useState('');
    const editable = can('exams.manage');

    const apply = (response: Setup) => {
        setSetup(response);
        setRows(response.subjects);
    };

    const load = useCallback(async () => apply(await api.get<Setup>(`/exams/${examId}/classes/${classId}`)), [examId, classId]);

    useEffect(() => {
        setErrors({});
        setSetup(null);
        void load();
    }, [load]);

    if (!setup) {
        return <LoadingState rows={4} height={48} />;
    }

    const extra = [...new Set(rows.flatMap((row) => row.components.map((component) => component.name_bn)))].filter((name) => !PARTS.some((part) => part.bn === name));
    const columns = [...PARTS.map((part) => ({ key: part.bn, label: locale() === 'en' ? part.en : part.bn, en: part.en })), ...extra.map((name) => ({ key: name, label: name, en: null as string | null }))];
    const unused = setup.class_subjects.filter((subject) => !rows.some((row) => row.subject_id === subject.id));

    const update = (index: number, patch: Partial<Row>) => setRows(rows.map((row, position) => (position === index ? { ...row, ...patch } : row)));

    const setPart = (index: number, column: (typeof columns)[number], value: string) => {
        const row = rows[index];
        const others = row.components.filter((component) => component.name_bn !== column.key);

        if (value === '') {
            update(index, { components: others });

            return;
        }

        const existing = row.components.find((component) => component.name_bn === column.key);
        const next: Component = existing ? { ...existing, full_marks: value } : { name_bn: column.key, name_en: column.en, full_marks: value, pass_marks: 0 };
        // Keep the order of the columns so the saved sort order matches what is on screen.
        const merged = [...others, next].sort((a, b) => columns.findIndex((c) => c.key === a.name_bn) - columns.findIndex((c) => c.key === b.name_bn));
        update(index, { components: merged });
    };

    const save = async () => {
        setErrors({});

        try {
            apply(await api.put<Setup>(`/exams/${examId}/classes/${classId}/subjects`, { subjects: rows }));
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                toast.error(Object.keys(error.errors).length ? t('core::admin.common.error') : error.message);
            }
        }
    };

    const action = async (path: string) => {
        try {
            apply(await api.post<Setup>(`/exams/${examId}/classes/${classId}/subjects/${path}`));
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    const error = (key: string) => errors[key]?.[0];

    return (
        <div className="grid gap-3.5">
            {editable && (
                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="outline" onClick={() => void action('seed')}>
                        {k('seed')}
                    </Button>
                    <NativeSelect className="w-auto" aria-label={k('copy_from')} placeholder={k('copy_from')} value={source} onChange={(event) => setSource(event.target.value)} options={setup.other_exams.map((exam) => ({ value: exam.id, label: exam.name }))} />
                    <Button variant="outline" disabled={!source} onClick={() => void action(`copy/${source}`)}>
                        {k('copy')}
                    </Button>
                </div>
            )}

            {rows.length === 0 ? (
                <DashedEmpty glyph="📚" title={k('empty')} />
            ) : (
                <TableCard>
                    <DesignTable minWidth={760}>
                        <thead>
                            <tr className="bg-surface-2 text-left">
                                <Th className="px-3.5 py-2.5">{k('subject')}</Th>
                                <Th className="px-2 py-2.5">{k('full_marks')}</Th>
                                <Th className="px-2 py-2.5">{k('pass_marks')}</Th>
                                {columns.map((column) => (
                                    <Th key={column.key} align="center" className="px-2 py-2.5">
                                        {column.label}
                                    </Th>
                                ))}
                                <Th align="center" className="px-2 py-2.5">
                                    {k('include_in_gpa')}
                                </Th>
                                <Th align="center" className="px-2 py-2.5">
                                    {k('optional')}
                                </Th>
                                {editable && <Th className="px-2 py-2.5" />}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => {
                                const sum = row.components.reduce((total, component) => total + Number(component.full_marks || 0), 0);
                                const hasParts = row.components.length > 0;

                                return (
                                    <tr key={row.subject_id} className="border-t border-line">
                                        <td className="px-3.5 py-2 font-semibold whitespace-nowrap">{row.name}</td>
                                        <td className="px-2 py-1.5">
                                            <input className={cellInput} inputMode="numeric" disabled={!editable || hasParts} aria-label={`${row.name} ${k('full_marks')}`} value={bnDigits(hasParts ? sum : row.full_marks)} onChange={(event) => update(index, { full_marks: toAsciiDigits(event.target.value) })} />
                                        </td>
                                        <td className="px-2 py-1.5">
                                            <input className={cellInput} inputMode="numeric" disabled={!editable} aria-invalid={Boolean(error(`subjects.${index}.pass_marks`))} aria-label={`${row.name} ${k('pass_marks')}`} value={bnDigits(row.pass_marks)} onChange={(event) => update(index, { pass_marks: toAsciiDigits(event.target.value) })} />
                                            {error(`subjects.${index}.pass_marks`) && <div className="text-xs font-semibold text-danger">{error(`subjects.${index}.pass_marks`)}</div>}
                                        </td>
                                        {columns.map((column) => {
                                            const component = row.components.find((candidate) => candidate.name_bn === column.key);
                                            const on = component !== undefined && component.full_marks !== '';

                                            return (
                                                <td key={column.key} className="px-2 py-1.5 text-center">
                                                    <input
                                                        inputMode="numeric"
                                                        disabled={!editable}
                                                        placeholder="—"
                                                        aria-label={`${row.name} ${column.label}`}
                                                        value={bnDigits(component?.full_marks ?? '')}
                                                        onChange={(event) => setPart(index, column, toAsciiDigits(event.target.value))}
                                                        className="inline-flex h-8 w-[58px] min-w-[52px] rounded-full border-0 text-center text-[13px] font-bold outline-none placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400"
                                                        style={{ background: on ? 'var(--green-50)' : 'var(--surface-2)', color: on ? 'var(--green-700)' : 'var(--faint)' }}
                                                    />
                                                </td>
                                            );
                                        })}
                                        <td className="px-2 py-1.5 text-center">
                                            <Checkbox checked={row.include_in_gpa} disabled={!editable} aria-label={`${row.name} ${k('include_in_gpa')}`} onCheckedChange={(checked) => update(index, { include_in_gpa: Boolean(checked) })} />
                                        </td>
                                        <td className="px-2 py-1.5 text-center">
                                            <Checkbox checked={row.is_optional} disabled={!editable} aria-label={`${row.name} ${k('optional')}`} onCheckedChange={(checked) => update(index, { is_optional: Boolean(checked) })} />
                                        </td>
                                        {editable && (
                                            <Td className="py-1.5">
                                                <Button variant="outline" size="icon" aria-label={t('core::admin.common.delete')} className="text-danger" onClick={() => setRows(rows.filter((_, position) => position !== index))}>
                                                    <XIcon />
                                                </Button>
                                            </Td>
                                        )}
                                    </tr>
                                );
                            })}
                        </tbody>
                    </DesignTable>
                </TableCard>
            )}

            {editable && (
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                        {unused.length > 0 && <span className="text-sm text-muted-foreground">{k('add_subject')}</span>}
                        {unused.map((subject) => (
                            <Button key={subject.id} variant="outline" className="h-10 border-dashed border-green-500 bg-green-50 text-green-700 hover:bg-green-50" onClick={() => setRows([...rows, { subject_id: subject.id, name: subject.name, full_marks: 100, pass_marks: 33, include_in_gpa: true, is_optional: false, components: [] }])}>
                                <PlusIcon /> {subject.name}
                            </Button>
                        ))}
                    </div>
                    <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
                </div>
            )}
        </div>
    );
}
