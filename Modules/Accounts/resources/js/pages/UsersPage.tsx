import { zodResolver } from '@hookform/resolvers/zod';
import type { ColumnDef } from '@tanstack/react-table';
import { PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';

import { HeaderActions } from '@admin/js';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DataTable } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';

import type { Role, User } from '../types';

const schema = z.object({
    name: z.string().min(1),
    email: z.string().email(),
    phone: z.string().optional(),
    password: z.string().optional(),
    is_active: z.boolean(),
    roles: z.array(z.string()),
});

type FormValues = z.infer<typeof schema>;

const label = (key: string) => t(`accounts::admin.users.${key}`);

function UserDialog({ user, roles, open, onClose, onSaved }: { user: User | null; roles: Role[]; open: boolean; onClose: () => void; onSaved: () => void }) {
    const {
        register,
        control,
        handleSubmit,
        reset,
        setError,
        formState: { errors, isSubmitting },
    } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { name: '', email: '', phone: '', password: '', is_active: true, roles: [] } });

    useEffect(() => {
        if (open) {
            reset(user ? { name: user.name, email: user.email, phone: user.phone ?? '', password: '', is_active: user.is_active, roles: user.roles } : { name: '', email: '', phone: '', password: '', is_active: true, roles: [] });
        }
    }, [open, user, reset]);

    const submit = handleSubmit(async (values) => {
        try {
            if (user) {
                await api.put(`/accounts/users/${user.id}`, values);
            } else {
                await api.post('/accounts/users', values);
            }
            toast.success(t('core::admin.common.saved'));
            onSaved();
        } catch (error) {
            if (error instanceof ApiError) {
                for (const [field, messages] of Object.entries(error.errors)) {
                    setError(field.split('.')[0] as keyof FormValues, { message: messages[0] });
                }
            }
            toast.error(t('core::admin.common.error'));
        }
    });

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{user ? label('edit') : label('new')}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <Field label={label('name')} htmlFor="name" error={errors.name?.message}>
                        <Input id="name" {...register('name')} />
                    </Field>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field label={label('email')} htmlFor="email" error={errors.email?.message}>
                            <Input id="email" type="email" {...register('email')} />
                        </Field>
                        <Field label={label('phone')} htmlFor="phone" error={errors.phone?.message}>
                            <Input id="phone" {...register('phone')} />
                        </Field>
                    </div>
                    <Field label={label('password')} htmlFor="password" error={errors.password?.message} hint={user ? label('password_hint') : undefined}>
                        <Input id="password" type="password" autoComplete="new-password" {...register('password')} />
                    </Field>
                    <Field label={label('roles')} error={errors.roles?.message}>
                        <Controller
                            control={control}
                            name="roles"
                            render={({ field }) => (
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {roles.map((role) => (
                                        <label key={role.name} className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={field.value.includes(role.name)}
                                                onCheckedChange={(checked) => field.onChange(checked ? [...field.value, role.name] : field.value.filter((name) => name !== role.name))}
                                            />
                                            {role.label}
                                        </label>
                                    ))}
                                </div>
                            )}
                        />
                    </Field>
                    <Controller
                        control={control}
                        name="is_active"
                        render={({ field }) => (
                            <label className="flex items-center gap-3 text-sm">
                                <Switch checked={field.value} onCheckedChange={field.onChange} />
                                {label('active')}
                            </label>
                        )}
                    />
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? t('core::admin.common.saving') : t('core::admin.common.save')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function UsersPage() {
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const { rows, meta, loading, setPage, reload } = usePaginated<User>('/accounts/users', { search: debounced });
    const [roles, setRoles] = useState<Role[]>([]);
    const [editing, setEditing] = useState<User | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleting, setDeleting] = useState<User | null>(null);

    useEffect(() => {
        void api.get<{ data: Role[] }>('/accounts/roles').then((response) => setRoles(response.data));
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const roleLabels = useMemo(() => Object.fromEntries(roles.map((role) => [role.name, role.label])), [roles]);

    const columns: ColumnDef<User>[] = [
        { header: label('name'), accessorKey: 'name' },
        { header: label('email'), accessorKey: 'email' },
        {
            header: label('roles'),
            cell: ({ row }) => (
                <div className="flex flex-wrap gap-1">
                    {row.original.roles.map((role) => (
                        <Badge key={role} variant="secondary">
                            {roleLabels[role] ?? role}
                        </Badge>
                    ))}
                </div>
            ),
        },
        {
            header: label('status'),
            cell: ({ row }) => <Badge variant={row.original.is_active ? 'default' : 'outline'}>{row.original.is_active ? label('active') : label('inactive')}</Badge>,
        },
        { header: label('last_login'), cell: ({ row }) => (row.original.last_login_at ? formatDate(row.original.last_login_at) : label('never')) },
        {
            header: t('core::admin.common.actions'),
            cell: ({ row }) => (
                <div className="flex gap-1">
                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.edit')} onClick={() => { setEditing(row.original); setDialogOpen(true); }}>
                        <PencilIcon />
                    </Button>
                    <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(row.original)}>
                        <Trash2Icon />
                    </Button>
                </div>
            ),
        },
    ];

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/accounts/users/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            reload();
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    return (
        <div className="space-y-4">
            <HeaderActions.Fill>
                <Button onClick={() => { setEditing(null); setDialogOpen(true); }}>
                    <PlusIcon /> {label('new')}
                </Button>
            </HeaderActions.Fill>

            <h1 className="text-2xl font-semibold tracking-tight">{label('title')}</h1>
            <Input className="max-w-sm" placeholder={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />

            <DataTable columns={columns} data={rows} loading={loading} meta={meta} onPageChange={setPage} />

            <UserDialog user={editing} roles={roles} open={dialogOpen} onClose={() => setDialogOpen(false)} onSaved={() => { setDialogOpen(false); reload(); }} />
            <ConfirmDialog open={deleting !== null} description={deleting?.name} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
