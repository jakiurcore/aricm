import { XIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { DashedEmpty } from '@core/js/components/design';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

interface Routine {
    subject_id: number;
    exam_date: string;
    start_time: string;
    end_time: string;
    room: string | null;
}

interface Setup {
    subjects: { subject_id: number; name: string }[];
    routine: (Routine & { subject: string })[];
}

const k = (key: string) => t(`exams::admin.routine.${key}`);
const field = 'h-11 min-w-0 w-full rounded-[10px] border border-line-strong bg-surface px-2 text-base text-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:bg-surface-2 aria-invalid:border-danger';

/** The routine of the design: one rounded row per paper (date, subject, start, end, room), "+ add row" and save underneath. */
export function RoutineTab({ examId, classId, onSaved }: { examId: number; classId: number; onSaved?: () => void }) {
    const [subjects, setSubjects] = useState<Setup['subjects'] | null>(null);
    const [rows, setRows] = useState<Routine[]>([]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const editable = can('exams.manage');

    const load = useCallback(async () => {
        const response = await api.get<Setup>(`/exams/${examId}/classes/${classId}`);
        setSubjects(response.subjects);
        const planned = response.routine.map((row) => ({ subject_id: row.subject_id, exam_date: row.exam_date, start_time: row.start_time, end_time: row.end_time, room: row.room }));
        const open = response.subjects.filter((subject) => !planned.some((row) => row.subject_id === subject.subject_id)).map((subject) => ({ subject_id: subject.subject_id, exam_date: '', start_time: '10:00', end_time: '12:00', room: '' }));
        setRows([...planned, ...open]);
    }, [examId, classId]);

    useEffect(() => {
        setErrors({});
        setSubjects(null);
        void load();
    }, [load]);

    if (subjects === null) {
        return <LoadingState rows={4} height={64} />;
    }

    if (subjects.length === 0) {
        return <DashedEmpty glyph="🗓" title={k('need_subjects')} />;
    }

    const set = (index: number, patch: Partial<Routine>) => setRows(rows.map((row, at) => (at === index ? { ...row, ...patch } : row)));
    const free = subjects.filter((subject) => !rows.some((row) => row.subject_id === subject.subject_id));

    const save = async () => {
        setErrors({});
        const routine = rows.filter((row) => row.exam_date);

        try {
            await api.put(`/exams/${examId}/classes/${classId}/routine`, { routine });
            toast.success(t('core::admin.common.saved'));
            await load();
            onSaved?.();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const position = (row: Routine) => rows.filter((candidate) => candidate.exam_date).indexOf(row);
    const message = (row: Routine, name: string) => errors[`routine.${position(row)}.${name}`]?.[0];

    return (
        <div className="grid gap-2.5">
            {rows.map((row, index) => (
                <div key={row.subject_id} className="grid items-center gap-2 rounded-[14px] border border-line bg-surface p-2.5" style={{ gridTemplateColumns: `repeat(auto-fit, minmax(130px, 1fr))` }}>
                    <div className="grid gap-0.5">
                        <input type="date" className={field} disabled={!editable} aria-label={k('date')} aria-invalid={Boolean(message(row, 'exam_date'))} value={row.exam_date} onChange={(event) => set(index, { exam_date: event.target.value })} />
                        {message(row, 'exam_date') && <span className="text-xs font-semibold text-danger">{message(row, 'exam_date')}</span>}
                    </div>
                    <NativeSelect
                        className="h-11 rounded-[10px] px-2"
                        aria-label={k('subject')}
                        disabled={!editable}
                        value={row.subject_id}
                        onChange={(event) => set(index, { subject_id: Number(event.target.value) })}
                        options={subjects.filter((subject) => subject.subject_id === row.subject_id || free.some((candidate) => candidate.subject_id === subject.subject_id)).map((subject) => ({ value: subject.subject_id, label: subject.name }))}
                    />
                    <input type="time" className={field} disabled={!editable} aria-label={k('start')} value={row.start_time} onChange={(event) => set(index, { start_time: event.target.value })} />
                    <div className="grid gap-0.5">
                        <input type="time" className={field} disabled={!editable} aria-label={k('end')} aria-invalid={Boolean(message(row, 'end_time'))} value={row.end_time} onChange={(event) => set(index, { end_time: event.target.value })} />
                        {message(row, 'end_time') && <span className="text-xs font-semibold text-danger">{message(row, 'end_time')}</span>}
                    </div>
                    <div className="flex gap-2">
                        <input className={field} disabled={!editable} placeholder={k('room')} aria-label={k('room')} value={row.room ?? ''} onChange={(event) => set(index, { room: event.target.value })} />
                        {editable && rows.length > 1 && (
                            <Button variant="outline" size="icon" className="size-11 shrink-0 text-danger" aria-label={t('core::admin.common.delete')} onClick={() => setRows(rows.filter((_, at) => at !== index))}>
                                <XIcon />
                            </Button>
                        )}
                    </div>
                </div>
            ))}

            {editable && (
                <div className="flex flex-wrap justify-between gap-2">
                    <Button variant="outline" disabled={free.length === 0} className="border-dashed border-green-500 bg-green-50 font-bold text-green-700 hover:bg-green-50" onClick={() => setRows([...rows, { subject_id: free[0].subject_id, exam_date: '', start_time: '10:00', end_time: '12:00', room: '' }])}>
                        + {k('add_row')}
                    </Button>
                    <Button onClick={() => void save()}>{k('save')}</Button>
                </div>
            )}
            <p className="text-[13px] text-muted-foreground">{k('hint')}</p>
        </div>
    );
}
