import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ListCard, Panel } from '@core/js/components/Surface';
import { LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits, formatNumber } from '@core/js/number';

import type { Student } from '../types';

const k = (key: string, replacements?: Record<string, string | number>) => t(`students::admin.promote_page.${key}`, replacements);

/** The design's "শ্রেণি উন্নীতকরণ": from / to pickers, the class list with checkboxes and GPA, one button. */
export function PromotePage() {
    const academic = useAcademicOptions();
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    const [sectionId, setSectionId] = useState('');
    const [rollMode, setRollMode] = useState('sequential');
    const [students, setStudents] = useState<Student[] | null>(null);
    const [off, setOff] = useState<Set<number>>(new Set());
    const [busy, setBusy] = useState(false);

    // "class_id:session_id", class by class in each session: the design's "৫ম শ্রেণি · ২০২৬".
    const combos = useMemo(
        () => academic.sessions.flatMap((session) => academic.classes.map((schoolClass) => ({ value: `${schoolClass.id}:${session.id}`, label: `${schoolClass.name} · ${bnDigits(session.name)}`, classId: schoolClass.id }))),
        [academic.sessions, academic.classes],
    );

    useEffect(() => {
        if (!from && academic.current_session_id && academic.classes.length > 0) {
            setFrom(`${academic.classes[0].id}:${academic.current_session_id}`);
        }
    }, [academic.current_session_id, academic.classes, from]);

    // The next class in the next session is what a promotion nearly always is, so it is the starting choice.
    useEffect(() => {
        if (!from || to || academic.classes.length === 0) {
            return;
        }

        const [classId, sessionId] = from.split(':');
        const index = academic.classes.findIndex((schoolClass) => String(schoolClass.id) === classId);
        const current = academic.sessions.find((session) => String(session.id) === sessionId);
        const next = academic.sessions.filter((session) => current && session.name > current.name).sort((a, b) => a.name.localeCompare(b.name))[0] ?? current;

        if (next && academic.classes[index + 1]) {
            setTo(`${academic.classes[index + 1].id}:${next.id}`);
        }
    }, [from, to, academic.classes, academic.sessions]);

    useEffect(() => {
        if (!from) {
            return;
        }

        const [classId, sessionId] = from.split(':');
        setStudents(null);
        setOff(new Set());
        api.get<{ data: Student[] }>('/students', { class_id: classId, session_id: sessionId, status: 'active', per_page: '100', with_gpa: '1' }).then((response) => setStudents(response.data));
    }, [from]);

    const targetClassId = to ? to.split(':')[0] : '';
    const targetSections = academic.classes.find((schoolClass) => String(schoolClass.id) === targetClassId)?.sections ?? [];
    const chosen = (students ?? []).filter((student) => !off.has(student.id));

    const submit = async () => {
        const [classId, sessionId] = to.split(':');
        setBusy(true);

        try {
            const response = await api.post<{ data: { promoted: number } }>('/students/promote', {
                student_ids: chosen.map((student) => student.id),
                session_id: sessionId,
                class_id: classId,
                section_id: sectionId || null,
                roll_mode: rollMode,
            });
            toast.success(t('students::admin.promote.done', { count: bnDigits(response.data.promoted) }));
            const [fromClass, fromSession] = from.split(':');
            const refreshed = await api.get<{ data: Student[] }>('/students', { class_id: fromClass, session_id: fromSession, status: 'active', per_page: '100', with_gpa: '1' });
            setStudents(refreshed.data);
            setOff(new Set());
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="grid max-w-[900px] gap-4">
            <Panel className="items-end gap-2.5 p-4" style={{ gridTemplateColumns: '1fr auto 1fr' }}>
                <Field label={k('from')} htmlFor="p-from">
                    <NativeSelect id="p-from" value={from} onChange={(event) => setFrom(event.target.value)} options={combos} />
                </Field>
                <span className="grid h-11 place-items-center text-xl text-green-700" aria-hidden="true">
                    →
                </span>
                <Field label={k('to')} htmlFor="p-to">
                    <NativeSelect id="p-to" placeholder={k('pick_class')} value={to} onChange={(event) => { setTo(event.target.value); setSectionId(''); }} options={combos} />
                </Field>
                <div className="col-span-3 grid gap-2.5 sm:grid-cols-2">
                    <Field label={t('students::admin.fields.section_id')} htmlFor="p-section">
                        <NativeSelect id="p-section" placeholder="—" value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={targetSections.map((section) => ({ value: section.id, label: section.name }))} />
                    </Field>
                    <Field label={t('students::admin.promote.roll_mode')} htmlFor="p-roll">
                        <NativeSelect id="p-roll" value={rollMode} onChange={(event) => setRollMode(event.target.value)} options={['sequential', 'keep', 'clear'].map((value) => ({ value, label: t(`students::admin.promote.roll.${value}`) }))} />
                    </Field>
                </div>
            </Panel>

            {students === null && <LoadingState rows={3} />}
            {students !== null && students.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('empty')}</div>}
            {students !== null && students.length > 0 && (
                <ListCard>
                    {students.map((student) => (
                        <label key={student.id} className="flex cursor-pointer items-center gap-3 border-b border-line px-4 py-3">
                            <input
                                type="checkbox"
                                className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700"
                                checked={!off.has(student.id)}
                                onChange={() => setOff((current) => { const next = new Set(current); if (!next.delete(student.id)) next.add(student.id); return next; })}
                            />
                            <span className="flex-1">
                                {student.roll ? bnDigits(student.roll) : '—'} · {student.name_bn}
                            </span>
                            {student.latest_gpa != null && <span className="text-sm text-muted-foreground">GPA {bnDigits(formatNumber(student.latest_gpa, 2))}</span>}
                        </label>
                    ))}
                    <div className="flex flex-wrap items-center justify-between gap-2.5 px-4 py-3">
                        <span className="text-sm text-muted-foreground">{k('selected', { count: bnDigits(chosen.length) })}</span>
                        <Button disabled={busy || !to || chosen.length === 0} onClick={() => void submit()}>
                            ⇪ {k('button')}
                        </Button>
                    </div>
                </ListCard>
            )}
        </div>
    );
}
