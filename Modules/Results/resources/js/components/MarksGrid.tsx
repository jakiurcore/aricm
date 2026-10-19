import { useCallback, useEffect, useMemo, useRef, useState, type KeyboardEvent } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { api, ApiError } from '@core/js/api';
import { DashedEmpty, Notice, TableCard } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
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
const k = (key: string) => t(`results::admin.marks.${key}`);

/** Cell text from a stored mark: '' empty, 'A' absent, otherwise the number. */
const toText = (mark?: { obtained: number | null; absent: boolean }) => (mark ? (mark.absent ? ABSENT : mark.obtained === null ? '' : String(mark.obtained)) : '');

/**
 * The marks grid of the design: pick a subject, then one row per student with an input for each part of the paper, an
 * "absent" tick and the running total. Sticky student column, green header, Enter / arrows move down the column, and every
 * change is saved on its own after a short pause.
 */
export function MarksGrid({ examId, classId }: { examId: number; classId: number }) {
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [subjectId, setSubjectId] = useState<number | null>(null);
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
        setSubjectId((current) => (response.data.columns.some((column) => column.exam_subject_id === current) ? current : (response.data.columns[0]?.exam_subject_id ?? null)));
    }, [examId, classId, sectionId]);

    useEffect(() => {
        setGrid(null);
        void load();
    }, [load]);

    useEffect(() => setSectionId(''), [classId]);

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
        return <LoadingState rows={5} height={44} />;
    }

    if (grid.columns.length === 0) {
        return <DashedEmpty glyph="📝" title={k('no_subjects')} />;
    }

    const subjects = grid.columns.filter((column, index, all) => all.findIndex((other) => other.exam_subject_id === column.exam_subject_id) === index);
    const visible = grid.columns.filter((column) => column.exam_subject_id === subjectId);

    const change = (cell: string, raw: string) => {
        const text = raw.trim().toUpperCase();
        const normalized = text === 'A' || text === 'এ' || text === 'AB' ? ABSENT : raw.replace(/[০-৯]/g, (digit) => String('০১২৩৪৫৬৭৮৯'.indexOf(digit)));
        dirty.current.add(cell);
        setValues((current) => ({ ...current, [cell]: normalized }));
        setStatus('dirty');
    };

    const setAbsent = (studentId: number, absent: boolean) => {
        dirty.current = new Set([...dirty.current, ...visible.map((column) => `${studentId}|${column.key}`)]);
        setValues((current) => ({ ...current, ...Object.fromEntries(visible.map((column) => [`${studentId}|${column.key}`, absent ? ABSENT : ''])) }));
        setStatus('dirty');
    };

    const move = (event: KeyboardEvent<HTMLInputElement>, row: number, col: number) => {
        const delta: Record<string, number> = { ArrowDown: 1, Enter: 1, ArrowUp: -1 };
        const step = delta[event.key];
        if (step === undefined) {
            return;
        }
        event.preventDefault();
        document.querySelector<HTMLInputElement>(`[data-cell="${row + step},${col}"]`)?.focus();
    };

    const saving = status === 'saving' || status === 'dirty';
    const failed = status === 'error';

    return (
        <div className="grid gap-2.5">
            <div className="flex flex-wrap items-center justify-between gap-2.5">
                <div className="flex flex-wrap items-center gap-2.5">
                    <NativeSelect className="w-auto font-semibold" aria-label={k('subject')} value={subjectId ?? ''} onChange={(event) => setSubjectId(Number(event.target.value))} options={subjects.map((column) => ({ value: column.exam_subject_id, label: column.subject }))} />
                    {sections.length > 1 && <NativeSelect className="w-auto" aria-label={t('students::admin.fields.section_id')} placeholder={t('exams::admin.admit.all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />}
                </div>
                <span aria-live="polite" className="flex items-center gap-2 rounded-full px-3 py-1 text-sm font-bold" style={failed ? { background: 'var(--danger-bg)', color: 'var(--danger)' } : saving ? { background: 'var(--warn-bg)', color: 'var(--warn)' } : { background: 'var(--success-bg)', color: 'var(--success)' }}>
                    {failed ? `⚠ ${k('status.error')}` : saving ? `● ${k('status.saving')}` : `✓ ${k('status.saved_all')}`}
                </span>
            </div>

            {grid.published && <Notice tone="warn">{k('locked')}</Notice>}
            <span className="text-[13px] text-muted-foreground">{k('hint')}</span>

            <TableCard>
                <table className="w-full border-collapse text-[15px]" style={{ minWidth: 640 }}>
                    <thead>
                        <tr className="bg-green-700 text-left text-white">
                            <th className="sticky left-0 bg-green-700 px-3 py-2.5 font-bold whitespace-nowrap">
                                {t('students::admin.fields.roll')} · {k('student')}
                            </th>
                            {visible.map((column) => (
                                <th key={column.key} className="px-2 py-2.5 font-bold whitespace-nowrap">
                                    {column.label !== column.subject ? column.label : k('marks')} ({bnDigits(column.full)})
                                </th>
                            ))}
                            <th className="px-2 py-2.5 font-bold whitespace-nowrap">{k('absent')}</th>
                            <th className="px-3 py-2.5 font-bold whitespace-nowrap">{k('total')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {grid.students.map((student, row) => {
                            const texts = visible.map((column) => values[`${student.id}|${column.key}`] ?? '');
                            const absent = texts.length > 0 && texts.every((text) => text === ABSENT);
                            const total = texts.reduce((sum, text) => sum + (text === ABSENT || text === '' || Number.isNaN(Number(text)) ? 0 : Number(text)), 0);
                            const background = absent ? 'var(--red-50)' : row % 2 ? 'var(--surface-2)' : 'var(--surface)';

                            return (
                                <tr key={student.id} className="border-t border-line" style={{ background }}>
                                    <td className="sticky left-0 px-3 py-1.5" style={{ background }}>
                                        <strong className="text-sm">
                                            {student.roll ? bnDigits(student.roll) : '—'} · {student.name}
                                        </strong>
                                    </td>
                                    {visible.map((column, col) => {
                                        const cell = `${student.id}|${column.key}`;
                                        const text = values[cell] ?? '';

                                        return (
                                            <td key={column.key} className="px-2 py-1">
                                                <input
                                                    data-cell={`${row},${col}`}
                                                    inputMode="decimal"
                                                    disabled={grid.published || (absent && text === ABSENT)}
                                                    value={text}
                                                    aria-label={`${student.name} ${column.subject} ${column.label}`}
                                                    aria-invalid={invalid(cell, text)}
                                                    onChange={(event) => change(cell, event.target.value)}
                                                    onKeyDown={(event) => move(event, row, col)}
                                                    onFocus={(event) => event.target.select()}
                                                    onBlur={() => void flush()}
                                                    className="h-10 min-h-0 w-[76px] rounded-[10px] border bg-surface px-2 text-center text-base font-semibold text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:bg-surface-2 disabled:text-faint"
                                                    style={{ borderColor: invalid(cell, text) ? 'var(--danger)' : 'var(--line-strong)' }}
                                                />
                                            </td>
                                        );
                                    })}
                                    <td className="px-2 py-1">
                                        <input type="checkbox" checked={absent} disabled={grid.published} aria-label={`${student.name} ${k('absent')}`} onChange={(event) => setAbsent(student.id, event.target.checked)} className="size-[22px] cursor-pointer accent-red-600" />
                                    </td>
                                    <td className="px-3 py-1 font-bold">{absent ? k('absent_short') : bnDigits(total)}</td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </TableCard>
        </div>
    );
}
