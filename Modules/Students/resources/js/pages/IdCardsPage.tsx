import { useEffect, useState } from 'react';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Badge } from '@/components/ui/badge';
import { api } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { ListCard, Panel } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

import type { Student } from '../types';

const k = (key: string) => t(`students::admin.cards.${key}`);

/** The design's ID card generator: pick a class (and section), tick the students, open the print preview. */
export function IdCardsPage() {
    const academic = useAcademicOptions();
    const [classId, setClassId] = useState('');
    const [sectionId, setSectionId] = useState('');
    const [students, setStudents] = useState<Student[] | null>(null);
    const [off, setOff] = useState<Set<number>>(new Set());
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        if (!classId && academic.classes.length > 0) {
            setClassId(String(academic.classes[0].id));
        }
    }, [academic.classes, classId]);

    useEffect(() => {
        if (!classId) {
            return;
        }

        setStudents(null);
        setFailed(false);
        setOff(new Set());
        api.get<{ data: Student[] }>('/students', { class_id: classId, section_id: sectionId, session_id: academic.current_session_id ? String(academic.current_session_id) : '', status: 'active', per_page: '100' })
            .then((response) => setStudents(response.data))
            .catch(() => setFailed(true));
    }, [classId, sectionId, academic.current_session_id]);

    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === classId)?.sections ?? [];
    const chosen = (students ?? []).filter((student) => !off.has(student.id));
    const query = new URLSearchParams(chosen.length === (students ?? []).length ? [['class_id', classId], ['section_id', sectionId], ['session_id', academic.current_session_id ? String(academic.current_session_id) : '']] : chosen.map((student) => ['ids[]', String(student.id)]));
    [...query.keys()].forEach((key) => !query.get(key) && query.delete(key));
    const printable = chosen.length > 0;

    return (
        <div className="grid gap-4">
            <Panel className="items-end gap-2.5 p-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 200px), 1fr))' }}>
                <Field label={k('class')} htmlFor="c-class">
                    <NativeSelect id="c-class" value={classId} onChange={(event) => { setClassId(event.target.value); setSectionId(''); }} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                </Field>
                <Field label={k('section')} htmlFor="c-section">
                    <NativeSelect id="c-section" placeholder={k('all')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                </Field>
                <a
                    href={printable ? `/api/students/id-cards?${query.toString()}` : undefined}
                    target="_blank"
                    rel="noreferrer"
                    aria-disabled={!printable}
                    className={`grid h-11 place-items-center rounded-full bg-navy-800 font-bold text-white no-underline ${printable ? '' : 'pointer-events-none opacity-50'}`}
                >
                    🖨 {k('print')}
                </a>
            </Panel>

            {failed && <ErrorState onRetry={() => setClassId((current) => current)} />}
            {!failed && students === null && <LoadingState rows={3} />}
            {students !== null && students.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{t('students::admin.list.no_match')}</div>}
            {students !== null && students.length > 0 && (
                <ListCard>
                    {students.map((student) => (
                        <label key={student.id} className="flex cursor-pointer items-center gap-3 border-b border-line px-4 py-2.5 last:border-b-0">
                            <input
                                type="checkbox"
                                className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700"
                                checked={!off.has(student.id)}
                                onChange={() => setOff((current) => { const next = new Set(current); if (!next.delete(student.id)) next.add(student.id); return next; })}
                            />
                            <span className="flex-1">
                                {student.name_bn} <span className="text-[13px] text-muted-foreground">· {bnDigits(student.student_id)}</span>
                            </span>
                            <Badge variant="success" className="text-xs">
                                {k('ready')}
                            </Badge>
                        </label>
                    ))}
                </ListCard>
            )}
        </div>
    );
}
