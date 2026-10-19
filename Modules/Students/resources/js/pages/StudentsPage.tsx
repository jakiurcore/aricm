import type { ColumnDef } from '@tanstack/react-table';
import { DownloadIcon, EyeIcon, IdCardIcon, PencilIcon, PlusIcon, Trash2Icon, UploadIcon, ArrowUpRightIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { HeaderActions } from '@admin/js';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DataTable } from '@core/js/components/DataTable';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

import { ImportDialog } from '../components/ImportDialog';
import { PromoteDialog } from '../components/PromoteDialog';
import { StudentForm } from '../components/StudentForm';
import { STATUSES, type Student } from '../types';

export function StudentsPage() {
    const academic = useAcademicOptions();
    const [filters, setFilters] = useState<Record<string, string>>({ status: 'active' });
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const { rows, meta, loading, setPage, reload } = usePaginated<Student>('/students', { ...filters, search: debounced });
    const [selected, setSelected] = useState<number[]>([]);
    const [editing, setEditing] = useState<Student | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [promoteOpen, setPromoteOpen] = useState(false);
    const [deleting, setDeleting] = useState<Student | null>(null);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === filters.class_id)?.sections ?? [];
    const setFilter = (name: string, value: string) => setFilters((current) => ({ ...current, [name]: value, ...(name === 'class_id' ? { section_id: '' } : {}) }));
    const toggle = (id: number, checked: boolean) => setSelected((current) => (checked ? [...current, id] : current.filter((value) => value !== id)));
    const exportQuery = new URLSearchParams(Object.entries({ ...filters, search: debounced }).filter(([, value]) => value)).toString();

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/students/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            reload();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    const columns: ColumnDef<Student>[] = [
        {
            id: 'select',
            header: () => (
                <Checkbox checked={rows.length > 0 && rows.every((row) => selected.includes(row.id))} onCheckedChange={(checked) => setSelected(checked ? rows.map((row) => row.id) : [])} />
            ),
            cell: ({ row }) => <Checkbox checked={selected.includes(row.original.id)} onCheckedChange={(checked) => toggle(row.original.id, Boolean(checked))} />,
        },
        {
            header: t('students::admin.fields.name_bn'),
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    <Avatar>
                        {row.original.photo_url && <AvatarImage src={row.original.photo_url} alt="" />}
                        <AvatarFallback>{row.original.name_bn.slice(0, 1)}</AvatarFallback>
                    </Avatar>
                    <div>
                        <div className="font-medium">{row.original.name_bn}</div>
                        <div className="text-xs text-muted-foreground">{row.original.student_id}</div>
                    </div>
                </div>
            ),
        },
        { header: t('students::admin.fields.class_id'), cell: ({ row }) => [row.original.class_name, row.original.section_name].filter(Boolean).join(' / ') },
        { header: t('students::admin.fields.roll'), cell: ({ row }) => (row.original.roll ? bnDigits(row.original.roll) : '—') },
        { header: t('students::admin.fields.guardian_phone'), cell: ({ row }) => bnDigits(row.original.guardian_phone ?? '') },
        { header: t('students::admin.fields.status'), cell: ({ row }) => <Badge variant={row.original.status === 'active' ? 'default' : 'outline'}>{t(`students::admin.status.${row.original.status}`)}</Badge> },
        {
            id: 'actions',
            header: t('core::admin.common.actions'),
            cell: ({ row }) => (
                <div className="flex gap-1">
                    <Button variant="ghost" size="icon" aria-label={t('students::admin.profile')} render={<Link to={`/students/${row.original.id}`} />}>
                        <EyeIcon />
                    </Button>
                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.edit')} onClick={() => { setEditing(row.original); setFormOpen(true); }}>
                        <PencilIcon />
                    </Button>
                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(row.original)}>
                        <Trash2Icon />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-4">
            <HeaderActions.Fill>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => setImportOpen(true)}>
                        <UploadIcon /> {t('students::admin.import.button')}
                    </Button>
                    {(selected.length > 0 || filters.class_id) && (
                        <Button variant="outline" render={<a href={`/api/students/id-cards?${new URLSearchParams(selected.length > 0 ? selected.map((id) => ['ids[]', String(id)]) : [['class_id', filters.class_id], ['section_id', filters.section_id ?? ''], ['session_id', filters.session_id ?? '']]).toString()}`} target="_blank" rel="noreferrer" />}>
                            <IdCardIcon /> {t('students::admin.id_cards')}
                        </Button>
                    )}
                    <Button variant="outline" render={<a href={`/api/students/export?${exportQuery}`} />}>
                        <DownloadIcon /> {t('students::admin.export')}
                    </Button>
                    <Button onClick={() => { setEditing(null); setFormOpen(true); }}>
                        <PlusIcon /> {t('students::admin.new')}
                    </Button>
                </div>
            </HeaderActions.Fill>

            <h1 className="text-2xl font-semibold tracking-tight">{t('students::admin.title')}</h1>

            <div className="flex flex-wrap items-center gap-2">
                <Input className="max-w-xs" placeholder={t('students::admin.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                <NativeSelect className="w-auto" aria-label={t('students::admin.fields.session_id')} placeholder={t('students::admin.fields.session_id')} value={filters.session_id ?? ''} onChange={(event) => setFilter('session_id', event.target.value)} options={academic.sessions.map((session) => ({ value: session.id, label: session.name }))} />
                <NativeSelect className="w-auto" aria-label={t('students::admin.fields.class_id')} placeholder={t('students::admin.fields.class_id')} value={filters.class_id ?? ''} onChange={(event) => setFilter('class_id', event.target.value)} options={academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                <NativeSelect className="w-auto" aria-label={t('students::admin.fields.section_id')} placeholder={t('students::admin.fields.section_id')} value={filters.section_id ?? ''} onChange={(event) => setFilter('section_id', event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                <NativeSelect className="w-auto" aria-label={t('students::admin.fields.status')} placeholder={t('students::admin.fields.status')} value={filters.status ?? ''} onChange={(event) => setFilter('status', event.target.value)} options={STATUSES.map((value) => ({ value, label: t(`students::admin.status.${value}`) }))} />
                {selected.length > 0 && (
                    <Button variant="secondary" onClick={() => setPromoteOpen(true)}>
                        <ArrowUpRightIcon /> {t('students::admin.promote.button', { count: bnDigits(selected.length) })}
                    </Button>
                )}
            </div>

            <DataTable columns={columns} data={rows} loading={loading} meta={meta} onPageChange={setPage} />

            <StudentForm student={editing} open={formOpen} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); reload(); }} />
            <ImportDialog open={importOpen} onClose={() => setImportOpen(false)} onImported={() => { setImportOpen(false); reload(); }} />
            <PromoteDialog studentIds={selected} open={promoteOpen} onClose={() => setPromoteOpen(false)} onPromoted={() => { setPromoteOpen(false); setSelected([]); reload(); }} />
            <ConfirmDialog open={deleting !== null} description={deleting?.name_bn} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
