import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo, useState } from 'react';
import { Controller, useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';

import { HeaderActions } from '@admin/js';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { TablePagination } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { FilterBar, IconButton, ListCard, ToggleRow } from '@core/js/components/Surface';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

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
                <form onSubmit={submit} className="grid gap-3">
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
                                <div className="grid gap-1 sm:grid-cols-2">
                                    {roles.map((role) => (
                                        <label key={role.name} className="flex min-h-11 cursor-pointer items-center gap-2.5 font-normal">
                                            <input
                                                type="checkbox"
                                                className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700"
                                                checked={field.value.includes(role.name)}
                                                onChange={(event) => field.onChange(event.target.checked ? [...field.value, role.name] : field.value.filter((name) => name !== role.name))}
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
                        render={({ field }) => <ToggleRow label={label('active')} on={field.value} onToggle={() => field.onChange(!field.value)} />}
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

/** Admins read navy, teachers green (the design's two pills); any other role is a neutral grey one. */
const ROLE_CLASS: Record<string, string> = { super_admin: 'bg-navy-50 text-navy-800', admin: 'bg-navy-50 text-navy-800', teacher: 'bg-green-50 text-green-700' };

/** The design's "ব্যবহারকারী ও ভূমিকা": a list card, avatar and name on the left, the role pills on the right. */
export function UsersPage() {
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const { rows, meta, loading, failed, setPage, reload } = usePaginated<User>('/accounts/users', { search: debounced });
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
        <div className="grid max-w-[900px] gap-3.5">
            <HeaderActions.Fill>
                <Button onClick={() => { setEditing(null); setDialogOpen(true); }}>+ {label('new')}</Button>
            </HeaderActions.Fill>

            <FilterBar>
                <Input className="col-span-2" placeholder={`🔍 ${t('core::admin.common.search')}`} aria-label={t('core::admin.common.search')} value={search} onChange={(event) => setSearch(event.target.value)} />
            </FilterBar>

            {failed && rows.length === 0 && <ErrorState onRetry={reload} />}
            {loading && rows.length === 0 && !failed && <LoadingState />}
            {!loading && !failed && rows.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{t('core::admin.common.empty')}</div>}

            {rows.length > 0 && (
                <ListCard>
                    {rows.map((user) => (
                        <div key={user.id} className={`flex flex-wrap items-center gap-3 border-b border-line px-4 py-3 last:border-b-0 ${loading ? 'opacity-60' : ''}`}>
                            <span className="grid size-10 flex-none place-items-center rounded-full bg-navy-50 font-bold text-navy-800">{user.name.trim().slice(0, 1)}</span>
                            <span className="grid min-w-40 flex-1 leading-[1.4]">
                                <strong>{user.name}</strong>
                                <span className="text-[13px] text-muted-foreground">
                                    {user.email} · {label('last_login')}: {user.last_login_at ? formatDate(user.last_login_at) : label('never')}
                                </span>
                            </span>
                            <span className="flex flex-wrap items-center gap-1.5">
                                {user.roles.map((role) => (
                                    <Badge key={role} variant="secondary" className={`px-3 py-0.5 ${ROLE_CLASS[role] ?? ''}`}>
                                        {roleLabels[role] ?? role}
                                    </Badge>
                                ))}
                                {!user.is_active && (
                                    <Badge variant="secondary" className="px-3 py-0.5">
                                        {label('inactive')}
                                    </Badge>
                                )}
                            </span>
                            <IconButton aria-label={t('core::admin.common.edit')} title={t('core::admin.common.edit')} onClick={() => { setEditing(user); setDialogOpen(true); }}>
                                ✎
                            </IconButton>
                            <IconButton tone="danger" aria-label={t('core::admin.common.delete')} title={t('core::admin.common.delete')} onClick={() => setDeleting(user)}>
                                🗑
                            </IconButton>
                        </div>
                    ))}
                </ListCard>
            )}

            {meta && <TablePagination meta={meta} onPageChange={setPage} />}

            <UserDialog user={editing} roles={roles} open={dialogOpen} onClose={() => setDialogOpen(false)} onSaved={() => { setDialogOpen(false); reload(); }} />
            <ConfirmDialog open={deleting !== null} description={deleting?.name} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
