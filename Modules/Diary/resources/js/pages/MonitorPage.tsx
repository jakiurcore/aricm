import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Panel } from '@core/js/components/Surface';
import { locale, t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Row {
    teacher_id: number;
    teacher: string;
    class_id: number;
    class: string;
    section_id: number;
    section: string;
    subject_id: number;
    subject: string;
    status: 'published' | 'draft' | 'missing';
    entry_id: number | null;
}

interface Compliance {
    date: string;
    is_school_day: boolean;
    rows: Row[];
    summary: { expected: number; published: number; draft: number; missing: number; percent: number };
}

interface Entry {
    id: number;
    date: string;
    status: string;
    teacher: string;
    class: string;
    section: string;
    subject: string;
    lesson: string | null;
    next_lesson: string | null;
    homework: string | null;
    note: string | null;
    published_at: string | null;
    seen: number;
    total: number;
}

const k = (key: string, replacements?: Record<string, string | number>) => t(`diary::admin.mon.${key}`, replacements);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
const today = () => new Date().toISOString().slice(0, 10);
const intl = () => (locale() === 'bn' ? 'bn-BD' : 'en-GB');

const CELL = {
    published: { icon: '✓', bg: 'var(--success-bg)', fg: 'var(--success)', title: 'done_title' },
    draft: { icon: '◐', bg: 'var(--warn-bg)', fg: 'var(--warn)', title: 'draft_title' },
    missing: { icon: '✕', bg: 'var(--danger-bg)', fg: 'var(--danger)', title: 'missing_title' },
} as const;

/** The entry dialog of the design: status chip and meta, the four fields, delete on the left and edit on the right. */
function EntryDialog({ cell, onClose, onChanged }: { cell: { row: Row; subject: string } | null; onClose: () => void; onChanged: () => void }) {
    const manage = can('diary.manage');
    const [entry, setEntry] = useState<Entry | null>(null);
    const [editing, setEditing] = useState(false);
    const [draft, setDraft] = useState({ lesson: '', next_lesson: '', homework: '', note: '' });
    const [confirming, setConfirming] = useState(false);
    const [busy, setBusy] = useState(false);

    const entryId = cell?.row.entry_id ?? null;

    useEffect(() => {
        setEntry(null);
        setEditing(false);
        setConfirming(false);

        if (entryId) {
            void api.get<{ data: Entry }>(`/diary/entries/${entryId}`).then((response) => setEntry(response.data));
        }
    }, [entryId, cell]);

    const startEdit = () => {
        setDraft({ lesson: entry?.lesson ?? '', next_lesson: entry?.next_lesson ?? '', homework: entry?.homework ?? '', note: entry?.note ?? '' });
        setEditing(true);
    };

    const save = async () => {
        if (!entry) {
            return;
        }

        setBusy(true);

        try {
            const response = await api.put<{ data: Entry }>(`/diary/entries/${entry.id}`, draft);
            setEntry(response.data);
            setEditing(false);
            toast.success(k('saved'));
            onChanged();
        } catch (error) {
            toast.error(message(error));
        } finally {
            setBusy(false);
        }
    };

    const remove = async () => {
        if (!confirming) {
            setConfirming(true);

            return;
        }

        if (!entry) {
            return;
        }

        try {
            await api.delete(`/diary/entries/${entry.id}`);
            toast.success(k('deleted'));
            onChanged();
            onClose();
        } catch (error) {
            toast.error(message(error));
        }
    };

    const fields: [keyof typeof draft, string][] = [['lesson', 'lesson'], ['next_lesson', 'next_lesson'], ['homework', 'homework'], ['note', 'note']];
    const time = entry?.published_at ? new Date(entry.published_at).toLocaleTimeString(intl(), { hour: '2-digit', minute: '2-digit' }) : null;

    return (
        <Dialog open={cell !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{cell ? `${cell.row.class} – ${cell.row.section} – ${cell.subject}` : ''}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    {!entryId && <div className="rounded-[14px] border-2 border-dashed border-input p-4 text-center text-muted-foreground">{k('entry_missing')}</div>}
                    {entryId && !entry && <LoadingState rows={2} height={48} />}
                    {entry && (
                        <>
                            <span className="flex flex-wrap items-center gap-2">
                                <span className={`rounded-full px-2.5 py-0.5 text-[13px] font-bold ${entry.status === 'published' ? 'bg-success-bg text-success' : 'bg-warn-bg text-warn'}`}>{entry.status === 'published' ? k('published') : k('draft_chip')}</span>
                                <span className="text-sm text-muted-foreground">{[entry.teacher, time, entry.status === 'published' ? k('seen', { seen: bnDigits(entry.seen), total: bnDigits(entry.total) }) : null].filter(Boolean).join(' · ')}</span>
                            </span>
                            {fields.map(([key, label]) => (
                                <div key={key} className="grid gap-1">
                                    <span className="text-[13px] font-bold text-muted-foreground">{k(label)}</span>
                                    {editing ? <Textarea rows={2} className="min-h-0 py-2" value={draft[key]} onChange={(event) => setDraft({ ...draft, [key]: event.target.value })} aria-label={k(label)} /> : <span className="leading-[1.6]">{entry[key] || '—'}</span>}
                                </div>
                            ))}
                            {confirming && <div className="rounded-xl bg-danger-bg px-3.5 py-2.5 text-sm font-semibold text-danger">{k('confirm_delete')}</div>}
                            {manage && (
                                <DialogFooter>
                                    <Button type="button" variant="outline" className="border-red-600 bg-red-50 font-bold text-danger sm:mr-auto" onClick={() => void remove()}>
                                        {confirming ? k('delete_yes') : k('delete')}
                                    </Button>
                                    {editing ? (
                                        <>
                                            <Button type="button" variant="outline" onClick={() => setEditing(false)}>
                                                {k('cancel')}
                                            </Button>
                                            <Button disabled={busy} onClick={() => void save()}>
                                                {k('save')}
                                            </Button>
                                        </>
                                    ) : (
                                        <Button onClick={startEdit}>{k('edit')}</Button>
                                    )}
                                </DialogFooter>
                            )}
                        </>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

/** The design's "ডায়েরি মনিটর": date and class filters, the compliance card and the class-section by subject grid. */
export function MonitorPage() {
    const options = useAcademicOptions();
    const manage = can('diary.manage');
    const [date, setDate] = useState(today());
    const [classId, setClassId] = useState('');
    const [data, setData] = useState<Compliance | null>(null);
    const [failed, setFailed] = useState(false);
    const [open, setOpen] = useState<{ row: Row; subject: string } | null>(null);
    const [reminding, setReminding] = useState(false);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            setData((await api.get<{ data: Compliance }>('/diary/compliance', { date })).data);
        } catch {
            setFailed(true);
        }
    }, [date]);

    useEffect(() => {
        void load();
    }, [load]);

    const view = useMemo(() => {
        const rows = (data?.rows ?? []).filter((row) => !classId || String(row.class_id) === classId);
        const subjects = new Map<number, string>();
        const sections = new Map<number, { label: string; cells: Map<number, Row> }>();

        for (const row of data?.rows ?? []) {
            subjects.set(row.subject_id, row.subject);
        }

        for (const row of rows) {
            const section = sections.get(row.section_id) ?? { label: `${row.class} – ${row.section}`, cells: new Map<number, Row>() };
            section.cells.set(row.subject_id, row);
            sections.set(row.section_id, section);
        }

        const counts = { published: 0, draft: 0, missing: 0 };
        const missingBy = new Map<string, number>();

        for (const row of rows) {
            counts[row.status]++;

            if (row.status === 'missing') {
                missingBy.set(row.teacher, (missingBy.get(row.teacher) ?? 0) + 1);
            }
        }

        const total = rows.length;

        return {
            subjects: [...subjects.entries()], sections: [...sections.entries()], counts, total,
            pct: total > 0 ? Math.round((counts.published * 100) / total) : 100,
            top: [...missingBy.entries()].sort((a, b) => b[1] - a[1]).slice(0, 3),
        };
    }, [data, classId]);

    const remind = async () => {
        setReminding(true);

        try {
            const response = await api.post<{ data: { sent: number } }>('/diary/remind', { date });
            toast.success(k('reminded', { count: bnDigits(response.data.sent) }));
        } catch (error) {
            toast.error(message(error));
        } finally {
            setReminding(false);
        }
    };

    const dateLabel = new Date(`${date}T00:00:00`).toLocaleDateString(intl(), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const nothingYet = data?.is_school_day && data.rows.length > 0 && view.counts.published === 0 && view.counts.draft === 0 && date === today();

    return (
        <div className="grid gap-3.5">
            <div className="flex flex-wrap items-end gap-2.5">
                <div className="flex-[0_1_200px]">
                    <Field label={k('date')} htmlFor="mon-date">
                        <Input id="mon-date" type="date" value={date} onChange={(event) => setDate(event.target.value || today())} />
                    </Field>
                </div>
                <div className="flex-[0_1_220px]">
                    <Field label={k('class')} htmlFor="mon-class">
                        <NativeSelect id="mon-class" placeholder={k('all_classes')} value={classId} onChange={(event) => setClassId(event.target.value)} options={options.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                    </Field>
                </div>
                <span className="flex flex-[1_1_0] flex-wrap justify-end gap-2.5 text-[13px] font-bold">
                    <span className="whitespace-nowrap text-success">{k('done')}</span>
                    <span className="whitespace-nowrap text-warn">{k('draft')}</span>
                    <span className="whitespace-nowrap text-danger">{k('missing')}</span>
                    <span className="whitespace-nowrap text-faint">{k('na')}</span>
                </span>
            </div>

            {failed && <ErrorState onRetry={() => void load()} />}
            {!failed && !data && <LoadingState />}

            {data && !data.is_school_day && (
                <div className="grid gap-1.5 rounded-[20px] border-2 border-dashed border-input px-5 py-7 text-center">
                    <span className="text-3xl" aria-hidden="true">
                        🌙
                    </span>
                    <strong className="text-lg">{k('off')}</strong>
                </div>
            )}

            {data?.is_school_day && (
                <div className="grid gap-3.5">
                    {nothingYet && (
                        <div className="grid gap-1 rounded-[20px] border-2 border-dashed border-input p-5 text-center">
                            <strong className="text-lg">{k('empty_title')}</strong>
                            <span className="text-muted-foreground">{k('empty_body')}</span>
                        </div>
                    )}

                    <Panel className="items-center gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 260px), 1fr))' }}>
                        <div className="grid gap-2">
                            <span className="text-sm font-bold text-muted-foreground">{k('compliance', { date: dateLabel })}</span>
                            <strong className="font-head text-[34px] leading-[1.2] text-green-700">{bnDigits(view.pct)}%</strong>
                            <span className="flex h-2.5 overflow-hidden rounded-full bg-surface-2">
                                <span style={{ width: `${view.total ? (view.counts.published * 100) / view.total : 0}%`, background: 'var(--green-500)' }} />
                                <span style={{ width: `${view.total ? (view.counts.draft * 100) / view.total : 0}%`, background: 'var(--yellow-400)' }} />
                            </span>
                            <span className="text-sm">{k('counts', { done: bnDigits(view.counts.published), draft: bnDigits(view.counts.draft), missing: bnDigits(view.counts.missing), total: bnDigits(view.total) })}</span>
                        </div>
                        <div className="grid gap-1.5">
                            <span className="text-sm font-bold text-muted-foreground">{k('top_missing')}</span>
                            {view.top.map(([name, count]) => (
                                <span key={name} className="flex justify-between gap-2 text-sm">
                                    <span>{name}</span>
                                    <strong className="text-danger">{k('n_items', { count: bnDigits(count) })}</strong>
                                </span>
                            ))}
                            {manage && view.counts.missing + view.counts.draft > 0 && (
                                <button type="button" disabled={reminding} onClick={() => void remind()} className="mt-1 h-10 cursor-pointer justify-self-start rounded-full border-0 bg-navy-800 px-3.5 text-sm font-bold text-white disabled:opacity-60">
                                    {k('remind')}
                                </button>
                            )}
                        </div>
                    </Panel>

                    {view.sections.length === 0 ? (
                        <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('no_subjects')}</div>
                    ) : (
                        <div className="overflow-x-auto rounded-[20px] border border-line bg-surface">
                            <table className="w-full min-w-[900px] border-collapse text-sm">
                                <thead>
                                    <tr className="bg-surface-2 text-left">
                                        <th className="sticky left-0 bg-surface-2 px-3 py-2.5">{k('class_section')}</th>
                                        {view.subjects.map(([id, name]) => (
                                            <th key={id} className="px-1 py-2.5 text-center whitespace-nowrap">
                                                {name}
                                            </th>
                                        ))}
                                        <th className="px-3 py-2.5 text-right">{k('total')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {view.sections.map(([sectionId, section]) => {
                                        const cells = [...section.cells.values()];

                                        return (
                                            <tr key={sectionId} className="border-t border-line">
                                                <td className="sticky left-0 bg-surface px-3 py-1.5 font-bold whitespace-nowrap">{section.label}</td>
                                                {view.subjects.map(([subjectId, subjectName]) => {
                                                    const row = section.cells.get(subjectId);

                                                    if (!row) {
                                                        return (
                                                            <td key={subjectId} className="p-1 text-center">
                                                                <span title={k('na_title')} className="inline-grid h-10 w-11 place-items-center font-bold text-faint">
                                                                    —
                                                                </span>
                                                            </td>
                                                        );
                                                    }

                                                    const style = CELL[row.status];

                                                    return (
                                                        <td key={subjectId} className="p-1 text-center">
                                                            <button type="button" title={`${subjectName} · ${k(style.title)}`} onClick={() => setOpen({ row, subject: subjectName })} className="h-10 w-11 min-h-0 cursor-pointer rounded-[10px] border-0 text-base font-bold" style={{ background: style.bg, color: style.fg }}>
                                                                {style.icon}
                                                            </button>
                                                        </td>
                                                    );
                                                })}
                                                <td className="px-3 py-1.5 text-right font-bold whitespace-nowrap">
                                                    {bnDigits(cells.filter((row) => row.status === 'published').length)}/{bnDigits(cells.length)}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            <EntryDialog cell={open} onClose={() => setOpen(null)} onChanged={() => void load()} />
        </div>
    );
}
