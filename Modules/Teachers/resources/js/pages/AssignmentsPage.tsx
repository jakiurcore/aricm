import { useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

import { shortName, type Teacher } from '../types';

interface GridData {
    subjects: { id: number; name: string }[];
    rows: { class_id: number; class: string; section_id: number; section: string; subject_ids: number[] }[];
}

interface Assignment {
    id: number;
    teacher_id: number;
    class_id: number;
    section_id: number;
    subject_id: number | null;
    is_class_teacher: boolean;
}

interface Target {
    classId: number;
    sectionId: number;
    subjectId: number | null;
    classTeacher: boolean;
    label: string;
    subject: string;
}

const k = (key: string, replacements?: Record<string, string | number>) => t(`teachers::admin.grid.${key}`, replacements);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
const cellKey = (sectionId: number, subjectId: number | null) => `${sectionId}:${subjectId ?? 'ct'}`;

/** The design's pick-a-teacher dialog: every teacher with their load, the current one ringed, a clear and an assign button. */
function PickDialog({ target, teachers, load, current, onClose, onSave }: { target: Target | null; teachers: Teacher[]; load: Record<number, number>; current: number | null; onClose: () => void; onSave: (teacherId: number | null) => Promise<void> }) {
    const [pick, setPick] = useState<number | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => setPick(current), [target, current]);

    const chosen = teachers.find((teacher) => teacher.id === pick);
    const warn = chosen && chosen.status !== 'active' ? (chosen.status === 'leave' ? k('warn_leave', { teacher: chosen.name_bn }) : k('inactive', { teacher: chosen.name_bn })) : null;

    const save = async (teacherId: number | null) => {
        setBusy(true);

        try {
            await onSave(teacherId);
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={target !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{target?.classTeacher ? k('pick_title_ct', { label: target.label }) : k('pick_title', { label: target?.label ?? '', subject: target?.subject ?? '' })}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="grid max-h-[340px] gap-1.5 overflow-y-auto">
                        {teachers.map((teacher) => {
                            const on = teacher.id === pick;

                            return (
                                <button
                                    key={teacher.id}
                                    type="button"
                                    onClick={() => setPick(teacher.id)}
                                    className={`flex min-h-[52px] cursor-pointer items-center gap-2.5 rounded-[14px] border-2 px-3 py-1.5 text-left text-foreground ${on ? 'border-green-700 bg-green-50' : 'border-line bg-surface'}`}
                                >
                                    <span className="grid flex-1 leading-[1.35]">
                                        <strong className="text-[15px]">{teacher.name_bn}</strong>
                                        <span className="text-[13px] text-muted-foreground">
                                            {[teacher.designation, k('load', { count: bnDigits(load[teacher.id] ?? 0) })].filter(Boolean).join(' · ')}
                                        </span>
                                    </span>
                                    {teacher.status !== 'active' && <span className="rounded-full bg-warn-bg px-2 text-xs font-bold text-warn">{t(`teachers::admin.list.statuses.${teacher.status}`)}</span>}
                                </button>
                            );
                        })}
                    </div>
                    {warn && <div className="rounded-xl bg-warn-bg px-3.5 py-2.5 text-sm font-semibold text-warn">⚠ {warn}</div>}
                    <DialogFooter>
                        {current !== null && (
                            <Button type="button" variant="outline" className="sm:mr-auto" disabled={busy} onClick={() => void save(null)}>
                                {k('clear')}
                            </Button>
                        )}
                        <Button type="button" variant="outline" onClick={onClose}>
                            {k('cancel')}
                        </Button>
                        <Button disabled={busy || pick === null || pick === current} onClick={() => void save(pick)}>
                            {k('assign_btn')}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}

/** The design's "শিক্ষক বণ্টন": a class-section by subject grid, a problem list on top, tap a cell to assign. */
export function AssignmentsPage() {
    const options = useAcademicOptions();
    const manage = can('teacher_assignments.manage');
    const [sessionId, setSessionId] = useState('');
    const [classId, setClassId] = useState('');
    const [grid, setGrid] = useState<GridData | null>(null);
    const [assignments, setAssignments] = useState<Assignment[]>([]);
    const [teachers, setTeachers] = useState<Teacher[]>([]);
    const [failed, setFailed] = useState(false);
    const [target, setTarget] = useState<Target | null>(null);
    const [copyFrom, setCopyFrom] = useState('');

    useEffect(() => {
        if (options.current_session_id && sessionId === '') {
            setSessionId(String(options.current_session_id));
        }
    }, [options.current_session_id, sessionId]);

    const load = useCallback(async () => {
        if (sessionId === '') {
            return;
        }

        setFailed(false);

        try {
            const [gridResponse, assignmentResponse, teacherResponse] = await Promise.all([
                api.get<{ data: GridData }>('/teachers/assignments/grid'),
                api.get<{ data: Assignment[] }>('/teachers/assignments', { session_id: sessionId }),
                api.get<{ data: Teacher[] }>('/teachers', { per_page: 200 }),
            ]);
            setGrid(gridResponse.data);
            setAssignments(assignmentResponse.data);
            setTeachers(teacherResponse.data);
        } catch {
            setFailed(true);
        }
    }, [sessionId]);

    useEffect(() => {
        void load();
    }, [load]);

    const byCell = useMemo(() => new Map(assignments.map((assignment) => [cellKey(assignment.section_id, assignment.is_class_teacher ? null : assignment.subject_id), assignment])), [assignments]);
    const teacherById = useMemo(() => new Map(teachers.map((teacher) => [teacher.id, teacher])), [teachers]);
    const teachLoad = useMemo(() => {
        const counts: Record<number, number> = {};
        assignments.filter((assignment) => !assignment.is_class_teacher).forEach((assignment) => (counts[assignment.teacher_id] = (counts[assignment.teacher_id] ?? 0) + 1));

        return counts;
    }, [assignments]);

    if (failed) {
        return <ErrorState onRetry={() => void load()} />;
    }

    if (!grid) {
        return <LoadingState />;
    }

    const label = (row: GridData['rows'][number]) => k('class_label', { class: row.class, section: row.section });
    const rows = grid.rows.filter((row) => !classId || String(row.class_id) === classId);
    const subjectName = (id: number) => grid.subjects.find((subject) => subject.id === id)?.name ?? '';

    // Everything the school should fix: a subject with nobody, or with a teacher who is away.
    const problems = grid.rows.flatMap((row) =>
        row.subject_ids.flatMap((subjectId) => {
            const assignment = byCell.get(cellKey(row.section_id, subjectId));
            const teacher = assignment ? teacherById.get(assignment.teacher_id) : undefined;
            const target: Target = { classId: row.class_id, sectionId: row.section_id, subjectId, classTeacher: false, label: label(row), subject: subjectName(subjectId) };

            if (!assignment) {
                return [{ tone: 'var(--danger)', text: `${label(row)} – ${subjectName(subjectId)}: ${k('not_assigned')}`, target }];
            }

            if (teacher && teacher.status !== 'active') {
                return [{ tone: 'var(--warn)', text: `${label(row)} – ${subjectName(subjectId)}: ${k(teacher.status === 'leave' ? 'on_leave' : 'inactive', { teacher: teacher.name_bn })}`, target }];
            }

            return [];
        }),
    );

    const currentTeacher = target ? (byCell.get(cellKey(target.sectionId, target.classTeacher ? null : target.subjectId))?.teacher_id ?? null) : null;

    const saveCell = async (teacherId: number | null) => {
        if (!target) {
            return;
        }

        try {
            await api.put('/teachers/assignments/cell', {
                session_id: sessionId, class_id: target.classId, section_id: target.sectionId, subject_id: target.subjectId, is_class_teacher: target.classTeacher, teacher_id: teacherId,
            });
            setTarget(null);
            await load();
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(message(error));
        }
    };

    const copy = async () => {
        try {
            const response = await api.put<{ data: { created: number } }>('/teachers/assignments/copy', { from_session_id: copyFrom, to_session_id: sessionId });
            toast.success(t('teachers::admin.assignments.copied', { count: bnDigits(response.data.created) }));
            await load();
        } catch (error) {
            toast.error(message(error));
        }
    };

    const open = (value: Target) => manage && setTarget(value);

    return (
        <div className="grid gap-3.5">
            <div className="flex flex-wrap items-end gap-2.5">
                <div className="flex-[0_1_180px]">
                    <Field label={k('session')} htmlFor="as-session">
                        <NativeSelect id="as-session" value={sessionId} onChange={(event) => setSessionId(event.target.value)} options={options.sessions.map((session) => ({ value: session.id, label: bnDigits(session.name) }))} />
                    </Field>
                </div>
                <div className="flex-[0_1_220px]">
                    <Field label={k('class')} htmlFor="as-class">
                        <NativeSelect id="as-class" placeholder={k('all_classes')} value={classId} onChange={(event) => setClassId(event.target.value)} options={options.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                    </Field>
                </div>
                <span className="min-w-[200px] flex-1 text-sm text-muted-foreground">{manage ? k('hint') : ''}</span>
            </div>

            {problems.length > 0 && (
                <div className="overflow-hidden rounded-[20px] border border-warn bg-warn-bg">
                    <div className="px-4 py-3 font-bold text-warn">⚠ {k('problems', { count: bnDigits(problems.length) })}</div>
                    {problems.map((problem, index) => (
                        <button
                            key={index}
                            type="button"
                            disabled={!manage}
                            onClick={() => open(problem.target)}
                            className="flex w-full cursor-pointer items-center gap-2.5 border-0 border-t bg-transparent px-4 py-2.5 text-left text-sm text-foreground"
                            style={{ borderTop: '1px solid rgba(138,90,0,.18)' }}
                        >
                            <span className="grid size-[22px] flex-none place-items-center rounded-full text-xs font-bold text-white" style={{ background: problem.tone }}>
                                !
                            </span>
                            <span className="flex-1">{problem.text}</span>
                            {manage && <span className="font-bold text-green-700">{k('fix')}</span>}
                        </button>
                    ))}
                </div>
            )}

            {rows.length === 0 ? (
                <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('empty')}</div>
            ) : (
                <div className="overflow-x-auto rounded-[20px] border border-line bg-surface">
                    <table className="w-full min-w-[1080px] border-collapse text-sm">
                        <thead>
                            <tr className="bg-surface-2 text-left">
                                <th className="sticky left-0 bg-surface-2 px-3 py-2.5">{k('class_section')}</th>
                                {grid.subjects.map((subject) => (
                                    <th key={subject.id} className="px-1.5 py-2.5 whitespace-nowrap">
                                        {subject.name}
                                    </th>
                                ))}
                                <th className="px-3 py-2.5 whitespace-nowrap text-navy-800">{k('class_teacher')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => {
                                const classTeacher = byCell.get(cellKey(row.section_id, null));

                                return (
                                    <tr key={row.section_id} className="border-t border-line">
                                        <td className="sticky left-0 bg-surface px-3 py-1.5 font-bold whitespace-nowrap">{label(row)}</td>
                                        {grid.subjects.map((subject) => {
                                            if (!row.subject_ids.includes(subject.id)) {
                                                return (
                                                    <td key={subject.id} className="p-1">
                                                        <span className="grid min-h-10 place-items-center text-faint">—</span>
                                                    </td>
                                                );
                                            }

                                            const assignment = byCell.get(cellKey(row.section_id, subject.id));
                                            const teacher = assignment ? teacherById.get(assignment.teacher_id) : undefined;
                                            const style = !assignment ? 'border-danger bg-danger-bg text-danger' : teacher && teacher.status !== 'active' ? 'border-warn bg-yellow-50 text-warn' : 'border-line bg-surface text-foreground';

                                            return (
                                                <td key={subject.id} className="p-1">
                                                    <button
                                                        type="button"
                                                        disabled={!manage}
                                                        onClick={() => open({ classId: row.class_id, sectionId: row.section_id, subjectId: subject.id, classTeacher: false, label: label(row), subject: subject.name })}
                                                        className={`min-h-10 w-full cursor-pointer rounded-[10px] border px-2 py-1 text-left text-[13px] leading-[1.3] font-semibold disabled:cursor-default ${style}`}
                                                    >
                                                        {assignment ? shortName(teacher?.name_bn ?? '') : k('assign')}
                                                    </button>
                                                </td>
                                            );
                                        })}
                                        <td className="py-1 pr-3 pl-1">
                                            <button
                                                type="button"
                                                disabled={!manage}
                                                onClick={() => open({ classId: row.class_id, sectionId: row.section_id, subjectId: null, classTeacher: true, label: label(row), subject: '' })}
                                                className="min-h-10 w-full cursor-pointer rounded-[10px] border border-navy-800 bg-navy-50 px-2 py-1 text-left text-[13px] leading-[1.3] font-bold text-navy-800 disabled:cursor-default"
                                            >
                                                {classTeacher ? shortName(teacherById.get(classTeacher.teacher_id)?.name_bn ?? '') : k('assign')}
                                            </button>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {manage && options.sessions.length > 1 && (
                <div className="flex flex-wrap items-end gap-2.5">
                    <div className="flex-[0_1_220px]">
                        <Field label={k('copy_from')} htmlFor="as-copy">
                            <NativeSelect id="as-copy" placeholder="—" value={copyFrom} onChange={(event) => setCopyFrom(event.target.value)} options={options.sessions.filter((session) => String(session.id) !== sessionId).map((session) => ({ value: session.id, label: bnDigits(session.name) }))} />
                        </Field>
                    </div>
                    <Button variant="outline" disabled={!copyFrom || sessionId === ''} onClick={() => void copy()}>
                        {k('copy_action')}
                    </Button>
                </div>
            )}

            <PickDialog target={target} teachers={teachers.filter((teacher) => teacher.is_active || teacher.id === currentTeacher)} load={teachLoad} current={currentTeacher} onClose={() => setTarget(null)} onSave={saveCell} />
        </div>
    );
}
