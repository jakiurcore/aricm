import { InboxIcon, MegaphoneIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { registerModule } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { DataTable } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { formatDate, bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Inquiry {
    id: number;
    student_name: string;
    guardian_name: string;
    phone: string;
    desired_class: string | null;
    message: string | null;
    status: string;
    admin_note: string | null;
    created_at: string;
}

const k = (key: string) => t(`admission::admin.${key}`);
const STATUSES = ['new', 'contacted', 'admitted', 'rejected'];

function InquiriesPage() {
    const [status, setStatus] = useState('');
    const [search, setSearch] = useState('');
    const { rows, meta, loading, setPage, reload } = usePaginated<Inquiry>('/admission/inquiries', { status, search });

    const update = async (inquiry: Inquiry, patch: Partial<Inquiry>) => {
        try {
            await api.put(`/admission/inquiries/${inquiry.id}`, { status: patch.status ?? inquiry.status, admin_note: patch.admin_note ?? inquiry.admin_note });
            reload();
        } catch {
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async (inquiry: Inquiry) => {
        await api.delete(`/admission/inquiries/${inquiry.id}`);
        reload();
    };

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>
            <div className="flex flex-wrap gap-2">
                <Input className="max-w-xs" placeholder={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                <NativeSelect className="w-auto" placeholder={k('status')} value={status} onChange={(event) => setStatus(event.target.value)} options={STATUSES.map((value) => ({ value, label: t(`admission::admin.statuses.${value}`) }))} />
            </div>
            <DataTable
                data={rows}
                loading={loading}
                meta={meta}
                onPageChange={setPage}
                columns={[
                    { header: k('student'), cell: ({ row }) => (<div><div className="font-medium">{row.original.student_name}</div><div className="text-xs text-muted-foreground">{row.original.desired_class} · {formatDate(row.original.created_at)}</div></div>) },
                    { header: k('guardian'), cell: ({ row }) => (<div><div>{row.original.guardian_name}</div><a className="text-xs underline" href={`tel:${row.original.phone}`}>{bnDigits(row.original.phone)}</a></div>) },
                    { header: k('message'), cell: ({ row }) => <span className="text-sm text-muted-foreground">{row.original.message}</span> },
                    {
                        header: k('status'),
                        cell: ({ row }) =>
                            can('admission.manage') ? (
                                <NativeSelect className="w-auto" value={row.original.status} onChange={(event) => void update(row.original, { status: event.target.value })} options={STATUSES.map((value) => ({ value, label: t(`admission::admin.statuses.${value}`) }))} />
                            ) : (
                                <Badge variant="outline">{t(`admission::admin.statuses.${row.original.status}`)}</Badge>
                            ),
                    },
                    { id: 'actions', header: '', cell: ({ row }) => (can('admission.manage') ? <Button variant="ghost" size="sm" onClick={() => void remove(row.original)}>{t('core::admin.common.delete')}</Button> : null) },
                ]}
            />
        </div>
    );
}

type Settings = Record<string, any>;

function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const readOnly = !can('admission.manage');
    const s = (key: string) => t(`admission::admin.settings.${key}`);

    useEffect(() => {
        void api.get<{ data: Settings }>('/admission/settings').then((response) => setValues(response.data));
    }, []);

    if (!values) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const set = (name: string, value: unknown) => setValues({ ...values, [name]: value });
    const text = (name: string, type = 'text') => (
        <Field label={s(name)} htmlFor={`a-${name}`} error={errors[name]?.[0]}>
            <Input id={`a-${name}`} type={type} disabled={readOnly} value={values[name] ?? ''} onChange={(event) => set(name, event.target.value)} />
        </Field>
    );
    const area = (name: string) => (
        <Field label={s(name)} htmlFor={`a-${name}`} error={errors[name]?.[0]}>
            <Textarea id={`a-${name}`} rows={4} disabled={readOnly} value={values[name] ?? ''} onChange={(event) => set(name, event.target.value)} />
        </Field>
    );

    const save = async () => {
        setErrors({});

        try {
            await api.put('/admission/settings', values);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    return (
        <div className="max-w-3xl space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{s('title')}</h1>
            <Card>
                <CardContent className="grid gap-4 pt-4 sm:grid-cols-2">
                    <label className="flex items-center gap-3 text-sm sm:col-span-2">
                        <Switch disabled={readOnly} checked={Boolean(values.banner_enabled)} onCheckedChange={(checked) => set('banner_enabled', checked)} />
                        {s('banner_enabled')}
                    </label>
                    {text('banner_title_bn')}
                    {text('banner_title_en')}
                    {text('admission_start', 'date')}
                    {text('class_start', 'date')}
                    {text('free_class_note_bn')}
                    {text('free_class_note_en')}
                    {text('program_bn')}
                    {text('program_en')}
                    <div className="sm:col-span-2">
                        <Field label={s('classes')} htmlFor="a-classes">
                            <Textarea id="a-classes" rows={5} disabled={readOnly} value={(values.classes ?? []).join('\n')} onChange={(event) => set('classes', event.target.value.split('\n').map((line) => line.trim()).filter(Boolean))} />
                        </Field>
                    </div>
                    <div className="sm:col-span-2">{area('content_bn')}</div>
                    <div className="sm:col-span-2">{area('content_en')}</div>
                </CardContent>
            </Card>
            {!readOnly && <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>}
        </div>
    );
}

registerModule({
    id: 'admission',
    menuFilter: (menu) =>
        can('admission.view')
            ? addToGroup(menu, t('core::admin.content_group'), [
                  { label: k('menu'), icon: InboxIcon, path: '/admission' },
                  { label: k('settings_menu'), icon: MegaphoneIcon, path: '/admission-settings' },
              ])
            : menu,
    routes: [
        { path: 'admission', element: <InquiriesPage /> },
        { path: 'admission-settings', element: <SettingsPage /> },
    ],
});
