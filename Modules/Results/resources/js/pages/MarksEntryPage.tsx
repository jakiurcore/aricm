import { CheckIcon, Loader2Icon } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { Link, useParams } from 'react-router';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { api, ApiError } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface Column {
    key: string;
    exam_subject_id: number;
    component_id: number | null;
    subject: string;
    label: string;
    full: number;
    pass: number;
    optional: boolean;
}

interface Grid {
    students: { id: number; roll: number | null; name: string; code: string }[];
    columns: Column[];
    marks: Record<string, Record<string, { obtained: number | null; absent: boolean }>>;
    published: boolean;
}

type Status = 'idle' | 'dirty' | 'saving' | 'saved' | 'error';

const ABSENT = 'A';
const k = (key: string, replacements: Record<string, string | number> = {}) => t(`results::admin.marks.${key}`, replacements);

/** Cell text → numeric state: '' empty, 'A' absent, otherwise a number string. */
const toText = (mark?: { obtained: number | null; absent: boolean }) => (mark ? (mark.absent ? ABSENT : mark.obtained === null ? '' : String(mark.obtained)) : '');

export function MarksEntryPage({ examId: propExamId, classId: propClassId, backTo }: { examId?: number; classId?: number; backTo?: string } = {}) {
    const params = useParams();
    const examId = propExamId ?? Number(params.examId);
    const classId = propClassId ?? Number(params.classId);
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [grid, setGrid] = useState<Grid | null>(null);
    const [values, setValues] = useState<Record<string, string>>({});
    const [status, setStatus] = useState<Status>('idle');
    const dirty = useRef(new Set<string>());
    const timer = useRef<ReturnType<typeof setTimeout>>(undefined);
    const sections = academic.classes.find((schoolClass) => schoolClass.id === classId)?.sections ?? [];

    const load = useCallback(async () => {
        const response = await api.get<{ data: Grid }>(`/results/exams/${examId}/classes/${classId}/marks`, { section_id: sectionId });
        setGrid(response.data);
        const next: Record<string, string> = {};
        for (const student of response.data.students) {
            for (const column of response.data.columns) {
                next[`${student.id}|${column.key}`] = toText(response.data.marks[student.id]?.[column.key]);
            }
        }
        setValues(next);
        dirty.current.clear();
        setStatus('idle');
    }, [examId, classId, sectionId]);

    useEffect(() => {
        void load();
    }, [load]);

    const columnByKey = useMemo(() => Object.fromEntries((grid?.columns ?? []).map((column) => [column.key, column])), [grid]);

    const invalid = (cell: string, text: string) => {
        const column = columnByKey[cell.split('|')[1]];

        return text !== '' && text !== ABSENT && (Number.isNaN(Number(text)) || Number(text) < 0 || Number(text) > column.full);
    };

    const flush = useCallback(async () => {
        const cells = [...dirty.current].filter((cell) => !invalid(cell, values[cell] ?? ''));
        if (cells.length === 0) {
            return;
        }

        setStatus('saving');
        const entries = cells.map((cell) => {
            const [studentId, key] = cell.split('|');
            const column = columnByKey[key];
            const text = values[cell] ?? '';

            return { student_id: Number(studentId), exam_subject_id: column.exam_subject_id, component_id: column.component_id, obtained: text === ABSENT || text === '' ? null : Number(text), is_absent: text === ABSENT };
        });

        try {
            await api.put(`/results/exams/${examId}/classes/${classId}/marks`, { entries });
            cells.forEach((cell) => dirty.current.delete(cell));
            setStatus(dirty.current.size > 0 ? 'dirty' : 'saved');
        } catch (error) {
            setStatus('error');
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [values, columnByKey, examId, classId]);

    useEffect(() => {
        if (dirty.current.size === 0) {
            return;
        }

        clearTimeout(timer.current);
        timer.current = setTimeout(() => void flush(), 800);

        return () => clearTimeout(timer.current);
    }, [values, flush]);

    if (!grid) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const change = (cell: string, raw: string) => {
        const text = raw.trim().toUpperCase();
        const normalized = text === 'A' || text === 'এ' || text === 'AB' ? ABSENT : raw.replace(/[০-৯]/g, (digit) => String('০১২৩৪৫৬৭৮৯'.indexOf(digit)));
        dirty.current.add(cell);
        setValues((current) => ({ ...current, [cell]: normalized }));
        setStatus('dirty');
    };

    const move = (event: KeyboardEvent<HTMLInputElement>, row: number, col: number) => {
        const delta: Record<string, [number, number]> = { ArrowDown: [1, 0], Enter: [1, 0], ArrowUp: [-1, 0] };
        const step = delta[event.key];
        if (!step) {
            return;
        }
        event.preventDefault();
        document.querySelector<HTMLInputElement>(`[data-cell="${row + step[0]},${col + step[1]}"]`)?.focus();
    };

    const subjectGroups = grid.columns.reduce<{ subject: string; span: number }[]>((groups, column) => {
        const last = groups[groups.length - 1];
        last && last.subject === column.subject ? last.span++ : groups.push({ subject: column.subject, span: 1 });

        return groups;
    }, []);

    return (
        <div className="space-y-4">
            <Link to={backTo ?? '/results'} className="text-sm text-muted-foreground underline">
                ← {t('results::admin.overview.title')}
            </Link>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
                <div className="flex items-center gap-3 text-sm">
                    <NativeSelect className="w-auto" placeholder={t('exams::admin.admit.all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                    <span className="flex items-center gap-1 text-muted-foreground" aria-live="polite">
                        {status === 'saving' && <Loader2Icon className="size-4 animate-spin" />}
                        {status === 'saved' && <CheckIcon className="size-4 text-green-700" />}
                        {k(`status.${status}`)}
                    </span>
                </div>
            </div>

            {grid.published && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">{k('locked')}</p>}
            <p className="text-xs text-muted-foreground">{k('hint')}</p>

            <div className="overflow-auto rounded-xl border bg-card">
                <table className="min-w-full border-collapse text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th rowSpan={2} className="sticky left-0 z-10 w-14 bg-muted p-2 text-left">
                                {t('students::admin.fields.roll')}
                            </th>
                            <th rowSpan={2} className="sticky left-14 z-10 min-w-44 bg-muted p-2 text-left">
                                {t('students::admin.fields.name_bn')}
                            </th>
                            {subjectGroups.map((group, index) => (
                                <th key={index} colSpan={group.span} className="border-l p-2 text-center">
                                    {group.subject}
                                </th>
                            ))}
                        </tr>
                        <tr className="border-b bg-muted/50 text-xs text-muted-foreground">
                            {grid.columns.map((column) => (
                                <th key={column.key} className="border-l p-1 text-center font-normal">
                                    {column.label !== column.subject ? column.label : ''} ({bnDigits(column.full)})
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {grid.students.map((student, row) => (
                            <tr key={student.id} className="border-b last:border-0">
                                <td className="sticky left-0 bg-card p-2">{student.roll ? bnDigits(student.roll) : '—'}</td>
                                <td className="sticky left-14 bg-card p-2">{student.name}</td>
                                {grid.columns.map((column, col) => {
                                    const cell = `${student.id}|${column.key}`;
                                    const text = values[cell] ?? '';

                                    return (
                                        <td key={column.key} className="border-l p-0">
                                            <input
                                                data-cell={`${row},${col}`}
                                                inputMode="decimal"
                                                disabled={grid.published}
                                                value={text}
                                                aria-label={`${student.name} ${column.subject} ${column.label}`}
                                                onChange={(event) => change(cell, event.target.value)}
                                                onKeyDown={(event) => move(event, row, col)}
                                                onFocus={(event) => event.target.select()}
                                                onBlur={() => void flush()}
                                                className={`h-10 w-20 bg-transparent px-2 text-center outline-none focus:bg-primary/10 ${invalid(cell, text) ? 'bg-red-100 text-red-700' : ''} ${text === ABSENT ? 'font-semibold text-amber-700' : ''}`}
                                            />
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
