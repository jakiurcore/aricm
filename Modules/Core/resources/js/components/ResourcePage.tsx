import type { ColumnDef } from '@tanstack/react-table';
import { PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

import { api, ApiError } from '../api';
import { usePaginated } from '../hooks/usePaginated';
import { t } from '../i18n';
import { ConfirmDialog } from './ConfirmDialog';
import { DataTable } from './DataTable';
import { Field } from './Field';
import { NativeSelect, type Option } from './NativeSelect';

export interface FieldDef {
    name: string;
    label: string;
    type: 'text' | 'textarea' | 'number' | 'date' | 'time' | 'email' | 'password' | 'select' | 'switch' | 'file' | 'checkboxes';
    options?: Option[];
    placeholder?: string;
    hint?: string;
    /** Span both columns of the two-column form grid. */
    wide?: boolean;
    accept?: string;
    multiple?: boolean;
}

export interface FilterDef {
    name: string;
    label: string;
    options: Option[];
}

export interface ResourcePageProps<T extends { id: number }> {
    title: string;
    newLabel: string;
    editLabel: string;
    endpoint: string;
    columns: ColumnDef<T, any>[];
    fields: FieldDef[];
    defaults: Record<string, unknown>;
    searchable?: boolean;
    filters?: FilterDef[];
    /** Convert a row from the API into form values (defaults to copying matching keys). */
    toForm?: (row: T) => Record<string, unknown>;
    rowActions?: (row: T, reload: () => void) => ReactNode;
    canEdit?: (row: T) => boolean;
    canDelete?: (row: T) => boolean;
    hideCreate?: boolean;
    description?: ReactNode;
}

type Values = Record<string, any>;

function toPayload(values: Values, editing: boolean): Values | FormData {
    const hasFile = Object.values(values).some((value) => value instanceof FileList || value instanceof File);

    if (!hasFile) {
        return values;
    }

    const form = new FormData();
    for (const [key, value] of Object.entries(values)) {
        if (value instanceof FileList) {
            Array.from(value).forEach((file) => form.append(`${key}${value.length > 1 ? '[]' : ''}`, file));
        } else if (value instanceof File) {
            form.append(key, value);
        } else if (typeof value === 'boolean') {
            form.append(key, value ? '1' : '0');
        } else if (value !== null && value !== undefined) {
            form.append(key, String(value));
        }
    }

    if (editing) {
        form.append('_method', 'PUT');
    }

    return form;
}

export function ResourcePage<T extends { id: number }>(props: ResourcePageProps<T>) {
    const { title, newLabel, editLabel, endpoint, columns, fields, defaults, searchable = true, filters = [], toForm, rowActions, canEdit, canDelete, hideCreate, description } = props;

    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const [filterValues, setFilterValues] = useState<Record<string, string>>({});
    const { rows, meta, loading, setPage, reload } = usePaginated<T>(endpoint, { search: debounced, ...filterValues });
    const [editing, setEditing] = useState<T | null>(null);
    const [open, setOpen] = useState(false);
    const [deleting, setDeleting] = useState<T | null>(null);

    const form = useForm<Values>({ defaultValues: defaults });

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const openForm = (row: T | null) => {
        setEditing(row);
        form.reset(row ? (toForm ? toForm(row) : { ...defaults, ...Object.fromEntries(Object.keys(defaults).map((key) => [key, (row as Values)[key] ?? defaults[key]])) }) : defaults);
        setOpen(true);
    };

    const submit = form.handleSubmit(async (values) => {
        try {
            const payload = toPayload(values, editing !== null);

            if (editing) {
                // PUT carries JSON, a multipart form is spoofed as POST + _method=PUT.
                payload instanceof FormData ? await api.post(`${endpoint}/${editing.id}`, payload) : await api.put(`${endpoint}/${editing.id}`, payload);
            } else {
                await api.post(endpoint, payload);
            }

            toast.success(t('core::admin.common.saved'));
            setOpen(false);
            reload();
        } catch (error) {
            if (error instanceof ApiError) {
                for (const [field, messages] of Object.entries(error.errors)) {
                    form.setError(field.split('.')[0], { message: messages[0] });
                }
                toast.error(Object.keys(error.errors).length ? t('core::admin.common.error') : error.message);
            } else {
                toast.error(t('core::admin.common.error'));
            }
        }
    });

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`${endpoint}/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            reload();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    const allColumns: ColumnDef<T, any>[] = [
        ...columns,
        {
            id: 'actions',
            header: t('core::admin.common.actions'),
            cell: ({ row }) => (
                <div className="flex items-center gap-1">
                    {rowActions?.(row.original, reload)}
                    {(canEdit?.(row.original) ?? true) && (
                        <Button variant="ghost" size="icon" aria-label={t('core::admin.common.edit')} onClick={() => openForm(row.original)}>
                            <PencilIcon />
                        </Button>
                    )}
                    {(canDelete?.(row.original) ?? true) && (
                        <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(row.original)}>
                            <Trash2Icon />
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const errors = form.formState.errors;

    const renderField = (field: FieldDef) => {
        const error = errors[field.name]?.message as string | undefined;
        const common = { id: `f-${field.name}` };

        let control: ReactNode;
        switch (field.type) {
            case 'textarea':
                control = <Textarea rows={4} {...common} {...form.register(field.name)} />;
                break;
            case 'select':
                control = <NativeSelect options={field.options ?? []} placeholder="—" {...common} {...form.register(field.name)} />;
                break;
            case 'switch':
                control = (
                    <Switch
                        checked={Boolean(form.watch(field.name))}
                        onCheckedChange={(checked) => form.setValue(field.name, checked, { shouldDirty: true })}
                    />
                );
                break;
            case 'checkboxes': {
                const current: Array<string | number> = form.watch(field.name) ?? [];
                control = (
                    <div className="grid gap-2 sm:grid-cols-3">
                        {(field.options ?? []).map((option) => (
                            <label key={option.value} className="flex items-center gap-2 text-sm">
                                <Checkbox
                                    checked={current.includes(option.value)}
                                    onCheckedChange={(checked) => form.setValue(field.name, checked ? [...current, option.value] : current.filter((value) => value !== option.value), { shouldDirty: true })}
                                />
                                {option.label}
                            </label>
                        ))}
                    </div>
                );
                break;
            }
            case 'file':
                control = <Input type="file" accept={field.accept} multiple={field.multiple} {...common} onChange={(event) => form.setValue(field.name, field.multiple ? event.target.files : event.target.files?.[0])} />;
                break;
            default:
                control = <Input type={field.type} placeholder={field.placeholder} {...common} {...form.register(field.name)} />;
        }

        return (
            <div key={field.name} className={field.wide ? 'sm:col-span-2' : undefined}>
                <Field label={field.label} htmlFor={common.id} error={error} hint={field.hint}>
                    {control}
                </Field>
            </div>
        );
    };

    return (
        <div className="space-y-4">
            {!hideCreate && (
                <HeaderActions.Fill>
                    <Button onClick={() => openForm(null)}>
                        <PlusIcon /> {newLabel}
                    </Button>
                </HeaderActions.Fill>
            )}

            <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
            {description && <p className="text-sm text-muted-foreground">{description}</p>}

            {(searchable || filters.length > 0) && (
                <div className="flex flex-wrap gap-2">
                    {searchable && <Input className="max-w-xs" placeholder={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />}
                    {filters.map((filter) => (
                        <NativeSelect
                            key={filter.name}
                            className="w-auto"
                            aria-label={filter.label}
                            placeholder={filter.label}
                            options={filter.options}
                            value={filterValues[filter.name] ?? ''}
                            onChange={(event) => setFilterValues({ ...filterValues, [filter.name]: event.target.value })}
                        />
                    ))}
                </div>
            )}

            <DataTable columns={allColumns} data={rows} loading={loading} meta={meta} onPageChange={setPage} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>{editing ? editLabel : newLabel}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                        {fields.map(renderField)}
                        <DialogFooter className="sm:col-span-2">
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                {t('core::admin.common.cancel')}
                            </Button>
                            <Button type="submit" disabled={form.formState.isSubmitting}>
                                {form.formState.isSubmitting ? t('core::admin.common.saving') : t('core::admin.common.save')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog open={deleting !== null} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
