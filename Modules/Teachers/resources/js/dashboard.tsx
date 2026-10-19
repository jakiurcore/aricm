import { KeyRoundIcon, UserRoundCheckIcon, UsersRoundIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { registerModule } from '@admin/js';
import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Teacher {
    id: number;
    name_bn: string;
    name_en: string | null;
    designation: string | null;
    phone: string | null;
    is_active: boolean;
    login_email: string | null;
    user_id: number | null;
    assignments_count: number;
}

interface Assignment {
    id: number;
    teacher_id: number;
    teacher: string;
    class: string;
    section: string;
    subject: string | null;
    is_class_teacher: boolean;
}

const k = (key: string) => t(`teachers::admin.${key}`);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

function TeachersPage() {
    const manage = can('teachers.manage');

    return (
        <ResourcePage<Teacher>
            title={k('teachers.title')}
            newLabel={k('teachers.new')}
            editLabel={k('teachers.edit')}
            endpoint="/teachers"
            hideCreate={!manage}
            canEdit={() => manage}
            canDelete={() => manage}
            description={k('teachers.login_hint')}
            defaults={{ name_bn: '', name_en: '', designation: '', phone: '', is_active: true, email: '', password: '' }}
            fields={[
                { name: 'name_bn', label: k('teachers.name_bn'), type: 'text' },
                { name: 'name_en', label: k('teachers.name_en'), type: 'text' },
                { name: 'designation', label: k('teachers.designation'), type: 'text' },
                { name: 'phone', label: k('teachers.phone'), type: 'text' },
                { name: 'email', label: k('teachers.email'), type: 'email' },
                { name: 'password', label: k('teachers.password'), type: 'password' },
                { name: 'is_active', label: k('teachers.is_active'), type: 'switch' },
            ]}
            toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', designation: row.designation ?? '', phone: row.phone ?? '', is_active: row.is_active, email: row.login_email ?? '', password: '' })}
            columns={[
                { header: k('teachers.name_bn'), cell: ({ row }) => <span className="font-medium">{row.original.name_bn}</span> },
                { header: k('teachers.designation'), cell: ({ row }) => row.original.designation ?? '—' },
                { header: k('teachers.phone'), cell: ({ row }) => row.original.phone ?? '—' },
                { header: k('teachers.email'), cell: ({ row }) => row.original.login_email ?? <span className="text-muted-foreground">{k('teachers.no_login')}</span> },
                { header: k('teachers.assignments'), cell: ({ row }) => bnDigits(row.original.assignments_count) },
                { header: '', id: 'state', cell: ({ row }) => <Badge variant={row.original.is_active ? 'default' : 'outline'}>{row.original.is_active ? k('teachers.is_active') : '—'}</Badge> },
            ]}
            rowActions={(row, reload) =>
                manage && row.user_id ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-label={k('teachers.reset_password')}
                        title={k('teachers.reset_password')}
                        onClick={() => {
                            const password = window.prompt(k('teachers.new_password'));
                            if (password) {
                                void api.put(`/teachers/${row.id}/password`, { password }).then(() => { toast.success(t('core::admin.common.saved')); reload(); }).catch((error) => toast.error(message(error)));
                            }
                        }}
                    >
                        <KeyRoundIcon />
                    </Button>
                ) : null
            }
        />
    );
}

function AssignmentsPage() {
    const options = useAcademicOptions();
    const manage = can('teacher_assignments.manage');
    const [sessionId, setSessionId] = useState<number | ''>('');
    const [rows, setRows] = useState<Assignment[]>([]);
    const [teachers, setTeachers] = useState<Teacher[]>([]);
    const [subjects, setSubjects] = useState<{ id: number; name: string }[]>([]);
    const [form, setForm] = useState({ teacher_id: '', class_id: '', section_id: '', subject_id: '' });
    const [copyFrom, setCopyFrom] = useState('');

    useEffect(() => {
        if (options.current_session_id && sessionId === '') {
            setSessionId(options.current_session_id);
        }
    }, [options.current_session_id, sessionId]);

    useEffect(() => {
        void api.get<{ data: Teacher[] }>('/teachers', { active: 1, per_page: 200 }).then((response) => setTeachers(response.data));
    }, []);

    const load = useCallback(async () => {
        if (sessionId !== '') {
            setRows((await api.get<{ data: Assignment[] }>('/teachers/assignments', { session_id: sessionId })).data);
        }
    }, [sessionId]);

    useEffect(() => {
        void load();
    }, [load]);

    useEffect(() => {
        setSubjects([]);
        if (form.class_id) {
            void api.get<{ data: { id: number; name: string }[] }>('/teachers/assignments/subjects', { class_id: form.class_id }).then((response) => setSubjects(response.data)).catch(() => setSubjects([]));
        }
    }, [form.class_id]);

    const sections = options.classes.find((item) => String(item.id) === form.class_id)?.sections ?? [];

    const add = async (classTeacher: boolean) => {
        try {
            await api.post('/teachers/assignments', { session_id: sessionId, teacher_id: form.teacher_id, class_id: form.class_id, section_id: form.section_id, subject_id: classTeacher ? null : form.subject_id, is_class_teacher: classTeacher });
            await load();
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

    return (
        <div className="max-w-5xl space-y-6">
            <h1 className="text-2xl font-semibold tracking-tight">{k('assignments.title')}</h1>

            <div className="flex items-center gap-3">
                <span className="text-sm text-muted-foreground">{k('assignments.session')}</span>
                <NativeSelect className="max-w-48" value={sessionId} onChange={(event) => setSessionId(Number(event.target.value))} options={options.sessions.map((session) => ({ value: session.id, label: session.name }))} />
            </div>

            {manage && (
                <section className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-4">
                    <Field label={k('assignments.teacher')}>
                        <NativeSelect value={form.teacher_id} onChange={(event) => setForm({ ...form, teacher_id: event.target.value })} placeholder={k('assignments.choose')} options={teachers.map((teacher) => ({ value: teacher.id, label: teacher.name_bn }))} />
                    </Field>
                    <Field label={k('assignments.class')}>
                        <NativeSelect value={form.class_id} onChange={(event) => setForm({ ...form, class_id: event.target.value, section_id: '', subject_id: '' })} placeholder={k('assignments.choose')} options={options.classes.map((item) => ({ value: item.id, label: item.name }))} />
                    </Field>
                    <Field label={k('assignments.section')}>
                        <NativeSelect value={form.section_id} onChange={(event) => setForm({ ...form, section_id: event.target.value })} placeholder={k('assignments.choose')} options={sections.map((item) => ({ value: item.id, label: item.name }))} />
                    </Field>
                    <Field label={k('assignments.subject')}>
                        <NativeSelect value={form.subject_id} onChange={(event) => setForm({ ...form, subject_id: event.target.value })} placeholder={k('assignments.choose')} options={subjects.map((item) => ({ value: item.id, label: item.name }))} />
                    </Field>
                    <div className="flex flex-wrap gap-2 md:col-span-4">
                        <Button disabled={!form.teacher_id || !form.section_id || !form.subject_id} onClick={() => void add(false)}>{k('assignments.add_subject')}</Button>
                        <Button variant="outline" disabled={!form.teacher_id || !form.section_id} onClick={() => void add(true)}><UserRoundCheckIcon /> {k('assignments.add_class_teacher')}</Button>
                    </div>
                </section>
            )}

            <ul className="divide-y rounded-xl border bg-card">
                {rows.length === 0 && <li className="p-4 text-sm text-muted-foreground">{k('assignments.empty')}</li>}
                {rows.map((row) => (
                    <li key={row.id} className="flex items-center justify-between gap-3 p-3 text-sm">
                        <span>
                            <strong>{row.class} — {row.section}</strong> · {row.teacher} · {row.is_class_teacher ? <Badge>{k('assignments.class_teacher')}</Badge> : row.subject}
                        </span>
                        {manage && <Button variant="ghost" size="sm" onClick={() => void api.delete(`/teachers/assignments/${row.id}`).then(load)}>{k('assignments.remove')}</Button>}
                    </li>
                ))}
            </ul>

            {manage && (
                <section className="flex flex-wrap items-end gap-3 rounded-xl border bg-card p-4">
                    <Field label={k('assignments.copy_from')}>
                        <NativeSelect value={copyFrom} onChange={(event) => setCopyFrom(event.target.value)} placeholder={k('assignments.choose')} options={options.sessions.filter((session) => session.id !== sessionId).map((session) => ({ value: session.id, label: session.name }))} />
                    </Field>
                    <Button variant="outline" disabled={!copyFrom || sessionId === ''} onClick={() => void copy()}>{k('assignments.copy_action')}</Button>
                </section>
            )}
        </div>
    );
}

registerModule({
    id: 'teachers',
    menuFilter: (menu) =>
        can('teachers.manage') || can('teacher_assignments.manage')
            ? addToGroup(menu, k('group'), [
                  { label: k('teachers.menu'), icon: UsersRoundIcon, path: '/teachers' },
                  { label: k('assignments.menu'), icon: UserRoundCheckIcon, path: '/teachers/assignments' },
              ])
            : menu,
    routes: [
        { path: 'teachers', element: <TeachersPage /> },
        { path: 'teachers/assignments', element: <AssignmentsPage /> },
    ],
});
