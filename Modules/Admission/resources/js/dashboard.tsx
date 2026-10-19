import { InboxIcon, MegaphoneIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { registerModule } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DataTable } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { FilterBar, IconButton, Panel, PanelTitle, ToggleRow } from '@core/js/components/Surface';
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
const STATUS_VARIANT: Record<string, 'info' | 'warning' | 'success' | 'destructive'> = { new: 'info', contacted: 'warning', admitted: 'success', rejected: 'destructive' };

function InquiriesPage() {
    const manage = can('admission.manage');
    const [status, setStatus] = useState('');
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const [deleting, setDeleting] = useState<Inquiry | null>(null);
    const { rows, meta, loading, failed, setPage, reload } = usePaginated<Inquiry>('/admission/inquiries', { status, search: debounced });

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const update = async (inquiry: Inquiry, patch: Partial<Inquiry>) => {
        try {
            await api.put(`/admission/inquiries/${inquiry.id}`, { status: patch.status ?? inquiry.status, admin_note: patch.admin_note ?? inquiry.admin_note });
            reload();
        } catch {
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/admission/inquiries/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            reload();
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    return (
        <div className="grid gap-3.5">
            <FilterBar>
                <Input className="col-span-2" placeholder={`🔍 ${t('core::admin.common.search')}`} aria-label={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                <NativeSelect aria-label={k('status')} placeholder={`${k('status')}: ${k('all')}`} value={status} onChange={(event) => setStatus(event.target.value)} options={STATUSES.map((value) => ({ value, label: t(`admission::admin.statuses.${value}`) }))} />
            </FilterBar>
            <DataTable
                data={rows}
                loading={loading}
                failed={failed}
                onRetry={reload}
                meta={meta}
                onPageChange={setPage}
                columns={[
                    { header: k('student'), cell: ({ row }) => (<div className="grid leading-[1.4]"><strong className="font-semibold">{row.original.student_name}</strong><span className="text-[13px] text-muted-foreground">{row.original.desired_class} · {formatDate(row.original.created_at)}</span></div>) },
                    { header: k('guardian'), cell: ({ row }) => (<div className="grid leading-[1.4]"><span>{row.original.guardian_name}</span><a className="text-[13px] text-green-700 underline" href={`tel:${row.original.phone}`}>{bnDigits(row.original.phone)}</a></div>) },
                    { header: k('message'), cell: ({ row }) => <span className="block max-w-sm text-sm whitespace-normal text-muted-foreground">{row.original.message}</span> },
                    {
                        header: k('status'),
                        cell: ({ row }) =>
                            manage ? (
                                <NativeSelect className="h-9 w-auto" value={row.original.status} onChange={(event) => void update(row.original, { status: event.target.value })} options={STATUSES.map((value) => ({ value, label: t(`admission::admin.statuses.${value}`) }))} />
                            ) : (
                                <Badge variant={STATUS_VARIANT[row.original.status] ?? 'secondary'}>{t(`admission::admin.statuses.${row.original.status}`)}</Badge>
                            ),
                    },
                    { id: 'actions', header: '', cell: ({ row }) => (manage ? <IconButton tone="danger" aria-label={t('core::admin.common.delete')} title={t('core::admin.common.delete')} onClick={() => setDeleting(row.original)}>🗑</IconButton> : null) },
                ]}
            />
            <ConfirmDialog open={deleting !== null} description={deleting?.student_name} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}

type Settings = Record<string, any>;

function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [failed, setFailed] = useState(false);
    const readOnly = !can('admission.manage');
    const s = (key: string) => t(`admission::admin.settings.${key}`);

    const load = () => {
        setFailed(false);
        api.get<{ data: Settings }>('/admission/settings')
            .then((response) => setValues(response.data))
            .catch(() => setFailed(true));
    };

    useEffect(load, []);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!values) {
        return <LoadingState />;
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
        <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))' }}>
            <Panel className="gap-2.5">
                <PanelTitle>{s('groups.banner')}</PanelTitle>
                <ToggleRow label={s('banner_enabled')} on={Boolean(values.banner_enabled)} disabled={readOnly} onToggle={() => set('banner_enabled', !values.banner_enabled)} />
                {text('banner_title_bn')}
                {text('banner_title_en')}
                {text('admission_start', 'date')}
                {text('class_start', 'date')}
                {text('free_class_note_bn')}
                {text('free_class_note_en')}
            </Panel>
            <Panel className="gap-2.5">
                <PanelTitle>{s('groups.program')}</PanelTitle>
                {text('program_bn')}
                {text('program_en')}
                <Field label={s('classes')} htmlFor="a-classes">
                    <Textarea id="a-classes" rows={5} disabled={readOnly} value={(values.classes ?? []).join('\n')} onChange={(event) => set('classes', event.target.value.split('\n').map((line) => line.trim()).filter(Boolean))} />
                </Field>
            </Panel>
            <Panel className="gap-2.5">
                <PanelTitle>{s('groups.content')}</PanelTitle>
                {area('content_bn')}
                {area('content_en')}
                {!readOnly && (
                    <Button size="lg" onClick={() => void save()}>
                        {t('core::admin.common.save')}
                    </Button>
                )}
            </Panel>
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
