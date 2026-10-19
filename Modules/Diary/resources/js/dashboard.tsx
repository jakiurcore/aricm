import { BookOpenCheckIcon, MessageSquareTextIcon, SettingsIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { registerModule } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Row {
    teacher_id: number;
    teacher: string;
    class: string;
    section: string;
    subject: string;
    status: 'published' | 'draft' | 'missing';
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
    homework: string | null;
    note: string | null;
    edited: boolean;
    remarks: number;
    seen: number;
    acknowledged: number;
}

interface TeacherStat {
    teacher_id: number;
    teacher: string;
    expected: number;
    published: number;
    percent: number;
}

const k = (key: string) => t(`diary::admin.${key}`);
const today = () => new Date().toISOString().slice(0, 10);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

const statusBadge = (status: Row['status']) => (
    <Badge variant={status === 'published' ? 'default' : status === 'draft' ? 'secondary' : 'destructive'}>{k(`monitor.status_${status}`)}</Badge>
);

function MonitorPage() {
    const [date, setDate] = useState(today());
    const [data, setData] = useState<Compliance | null>(null);
    const [teachers, setTeachers] = useState<TeacherStat[]>([]);
    const [entries, setEntries] = useState<Entry[]>([]);

    const load = useCallback(async () => {
        const [compliance, entryList] = await Promise.all([api.get<{ data: Compliance }>('/diary/compliance', { date }), api.get<{ data: Entry[] }>('/diary/entries', { date })]);
        setData(compliance.data);
        setEntries(entryList.data);
    }, [date]);

    useEffect(() => {
        void load();
    }, [load]);

    useEffect(() => {
        void api.get<{ data: TeacherStat[] }>('/diary/compliance/teachers').then((response) => setTeachers(response.data));
    }, []);

    const remove = async (entry: Entry) => {
        try {
            await api.delete(`/diary/entries/${entry.id}`);
            await load();
        } catch (error) {
            toast.error(message(error));
        }
    };

    return (
        <div className="max-w-6xl space-y-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-semibold tracking-tight">{k('monitor.title')}</h1>
                <Input type="date" className="w-44" value={date} onChange={(event) => setDate(event.target.value)} aria-label={k('monitor.date')} />
            </div>

            {data && !data.is_school_day && <p className="rounded-xl border bg-card p-4 text-sm text-muted-foreground">{k('monitor.holiday')}</p>}

            {data && data.is_school_day && (
                <>
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
                        {(['expected', 'published', 'draft', 'missing'] as const).map((key) => (
                            <div key={key} className="rounded-xl border bg-card p-4">
                                <div className="text-xs text-muted-foreground">{k(`monitor.${key}`)}</div>
                                <div className="text-2xl font-semibold">{bnDigits(data.summary[key])}</div>
                            </div>
                        ))}
                        <div className="rounded-xl border bg-card p-4">
                            <div className="text-xs text-muted-foreground">{k('monitor.percent')}</div>
                            <div className="text-2xl font-semibold">{bnDigits(data.summary.percent)}%</div>
                        </div>
                    </div>

                    <div className="overflow-x-auto rounded-xl border bg-card">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    {['teacher', 'class', 'section', 'subject', 'status'].map((column) => (
                                        <th key={column} className="p-3 font-medium">{k(`monitor.${column}`)}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {data.rows.map((row, index) => (
                                    <tr key={index}>
                                        <td className="p-3">{row.teacher}</td>
                                        <td className="p-3">{row.class}</td>
                                        <td className="p-3">{row.section}</td>
                                        <td className="p-3">{row.subject}</td>
                                        <td className="p-3">{statusBadge(row.status)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}

            <section className="space-y-2">
                <h2 className="font-semibold">{k('monitor.by_teacher')}</h2>
                <ul className="divide-y rounded-xl border bg-card text-sm">
                    {teachers.map((teacher) => (
                        <li key={teacher.teacher_id} className="flex items-center justify-between p-3">
                            <span>{teacher.teacher}</span>
                            <span className="text-muted-foreground">{bnDigits(teacher.published)}/{bnDigits(teacher.expected)} · {bnDigits(teacher.percent)}%</span>
                        </li>
                    ))}
                </ul>
            </section>

            <section className="space-y-2">
                <h2 className="font-semibold">{k('monitor.entries')}</h2>
                {entries.length === 0 && <p className="text-sm text-muted-foreground">{k('monitor.empty')}</p>}
                <ul className="space-y-3">
                    {entries.map((entry) => (
                        <li key={entry.id} className="space-y-1 rounded-xl border bg-card p-4 text-sm">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <strong>{entry.class} — {entry.section} · {entry.subject}</strong>
                                <span className="text-muted-foreground">{entry.teacher} · {formatDate(entry.date)}</span>
                            </div>
                            {entry.lesson && <p><span className="text-muted-foreground">{k('monitor.lesson')}: </span>{entry.lesson}</p>}
                            {entry.homework && <p><span className="text-muted-foreground">{k('monitor.homework')}: </span>{entry.homework}</p>}
                            {entry.note && <p><span className="text-muted-foreground">{k('monitor.note')}: </span>{entry.note}</p>}
                            <div className="flex flex-wrap items-center gap-2 pt-1 text-xs text-muted-foreground">
                                <Badge variant={entry.status === 'published' ? 'default' : 'secondary'}>{entry.status === 'published' ? k('monitor.status_published') : k('monitor.status_draft')}</Badge>
                                {entry.edited && <Badge variant="outline">{k('monitor.edited')}</Badge>}
                                <span>{k('monitor.seen')}: {bnDigits(entry.seen)}</span>
                                <span>{k('monitor.acknowledged')}: {bnDigits(entry.acknowledged)}</span>
                                <span>{k('monitor.remarks')}: {bnDigits(entry.remarks)}</span>
                                {can('diary.manage') && <Button variant="ghost" size="sm" onClick={() => void remove(entry)}>{k('monitor.delete')}</Button>}
                            </div>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}

type Settings = Record<string, number | boolean>;

function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const manage = can('diary.manage');

    useEffect(() => {
        void api.get<{ data: Settings }>('/diary/settings').then((response) => setValues(response.data));
    }, []);

    if (!values) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const set = (key: string, value: number | boolean) => setValues({ ...values, [key]: value });
    const save = async () => {
        try {
            setValues((await api.put<{ data: Settings }>('/diary/settings', values)).data);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(message(error));
        }
    };

    const toggle = (key: string) => (
        <label className="flex items-center gap-3 text-sm">
            <Switch disabled={!manage} checked={Boolean(values[key])} onCheckedChange={(checked) => set(key, checked)} />
            {k(`settings.${key}`)}
        </label>
    );
    const number = (key: string) => (
        <Field label={k(`settings.${key}`)} htmlFor={key}>
            <Input id={key} type="number" min={0} disabled={!manage} value={Number(values[key])} onChange={(event) => set(key, Number(event.target.value))} />
        </Field>
    );

    return (
        <div className="max-w-xl space-y-5">
            <h1 className="text-2xl font-semibold tracking-tight">{k('settings.title')}</h1>
            <div className="space-y-4 rounded-xl border bg-card p-5">
                {toggle('enabled')}
                {number('edit_window_hours')}
                {number('backdate_days')}
                {number('max_attachments')}
                {toggle('sms_enabled')}
                {toggle('sms_teacher_reminder')}
                <p className="text-xs text-muted-foreground">{k('settings.sms_hint')}</p>
            </div>
            {manage && <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>}
        </div>
    );
}

interface Phrase {
    id: number;
    field: string;
    text_bn: string;
    sort_order: number;
    is_active: boolean;
}

function PhrasesPage() {
    const fields = ['lesson', 'homework', 'note', 'remark'];

    return (
        <ResourcePage<Phrase>
            title={k('phrases.title')}
            newLabel={k('phrases.new')}
            editLabel={k('phrases.edit')}
            endpoint="/diary/phrases"
            searchable={false}
            hideCreate={!can('diary.manage')}
            canEdit={() => can('diary.manage')}
            canDelete={() => can('diary.manage')}
            defaults={{ field: 'lesson', text_bn: '', sort_order: 0, is_active: true }}
            fields={[
                { name: 'field', label: k('phrases.field'), type: 'select', options: fields.map((field) => ({ value: field, label: k(`phrases.fields.${field}`) })) },
                { name: 'text_bn', label: k('phrases.text_bn'), type: 'text', wide: true },
                { name: 'sort_order', label: k('phrases.sort_order'), type: 'number' },
                { name: 'is_active', label: k('phrases.is_active'), type: 'switch' },
            ]}
            columns={[
                { header: k('phrases.field'), cell: ({ row }) => k(`phrases.fields.${row.original.field}`) },
                { header: k('phrases.text_bn'), cell: ({ row }) => row.original.text_bn },
                { header: '', id: 'state', cell: ({ row }) => <Badge variant={row.original.is_active ? 'default' : 'outline'}>{row.original.is_active ? k('phrases.is_active') : '—'}</Badge> },
            ]}
        />
    );
}

registerModule({
    id: 'diary',
    menuFilter: (menu) =>
        can('diary.monitor') || can('diary.manage')
            ? addToGroup(menu, k('group'), [
                  { label: k('monitor.menu'), icon: BookOpenCheckIcon, path: '/diary' },
                  { label: k('phrases.menu'), icon: MessageSquareTextIcon, path: '/diary/phrases' },
                  { label: k('settings.menu'), icon: SettingsIcon, path: '/diary/settings' },
              ])
            : menu,
    routes: [
        { path: 'diary', element: <MonitorPage /> },
        { path: 'diary/phrases', element: <PhrasesPage /> },
        { path: 'diary/settings', element: <SettingsPage /> },
    ],
});
