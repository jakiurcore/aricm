import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { t } from '@core/js/i18n';

interface Routine {
    subject_id: number;
    subject: string;
    exam_date: string;
    start_time: string;
    end_time: string;
    room: string | null;
}

interface Setup {
    subjects: { subject_id: number; name: string }[];
    routine: Routine[];
}

const k = (key: string) => t(`exams::admin.routine.${key}`);

export function RoutineTab({ examId, classId, onSaved }: { examId: number; classId: number; onSaved?: () => void }) {
    const [subjects, setSubjects] = useState<Setup['subjects']>([]);
    const [rows, setRows] = useState<Record<number, Routine>>({});
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    const load = useCallback(async () => {
        const response = await api.get<Setup>(`/exams/${examId}/classes/${classId}`);
        setSubjects(response.subjects);
        setRows(Object.fromEntries(response.routine.map((row) => [row.subject_id, row])));
    }, [examId, classId]);

    useEffect(() => {
        setErrors({});
        void load();
    }, [load]);

    const set = (subjectId: number, name: string, patch: Partial<Routine>) => {
        const current: Routine = rows[subjectId] ?? { subject_id: subjectId, subject: name, exam_date: '', start_time: '10:00', end_time: '12:00', room: '' };
        setRows({ ...rows, [subjectId]: { ...current, ...patch } });
    };

    const save = async () => {
        setErrors({});
        const routine = subjects.filter((subject) => rows[subject.subject_id]?.exam_date).map((subject) => rows[subject.subject_id]);

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

    if (subjects.length === 0) {
        return <p className="text-sm text-muted-foreground">{k('need_subjects')}</p>;
    }

    const position = (subjectId: number) => Object.keys(rows).map(Number).filter((id) => rows[id]?.exam_date).indexOf(subjectId);

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="p-3">{k('subject')}</th>
                            <th className="p-3">{k('date')}</th>
                            <th className="p-3">{k('start')}</th>
                            <th className="p-3">{k('end')}</th>
                            <th className="p-3">{k('room')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {subjects.map((subject) => {
                            const row = rows[subject.subject_id];
                            const index = position(subject.subject_id);
                            const message = (field: string) => errors[`routine.${index}.${field}`]?.[0];

                            return (
                                <tr key={subject.subject_id} className="border-b last:border-0">
                                    <td className="p-3 font-medium">{subject.name}</td>
                                    <td className="p-3">
                                        <Input type="date" value={row?.exam_date ?? ''} onChange={(event) => set(subject.subject_id, subject.name, { exam_date: event.target.value })} />
                                        {message('exam_date') && <span className="text-xs text-destructive">{message('exam_date')}</span>}
                                    </td>
                                    <td className="p-3">
                                        <Input type="time" value={row?.start_time ?? '10:00'} onChange={(event) => set(subject.subject_id, subject.name, { start_time: event.target.value })} />
                                    </td>
                                    <td className="p-3">
                                        <Input type="time" value={row?.end_time ?? '12:00'} onChange={(event) => set(subject.subject_id, subject.name, { end_time: event.target.value })} />
                                        {message('end_time') && <span className="text-xs text-destructive">{message('end_time')}</span>}
                                    </td>
                                    <td className="p-3">
                                        <Input className="w-24" value={row?.room ?? ''} onChange={(event) => set(subject.subject_id, subject.name, { room: event.target.value })} />
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
            <p className="text-xs text-muted-foreground">{k('hint')}</p>
            <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>
        </div>
    );
}
