import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Field } from '@core/js/components/Field';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';

import type { Role } from '../types';

const roleKey = (key: string) => t(`accounts::admin.roles.${key}`);

export function RolesPage() {
    const [roles, setRoles] = useState<Role[]>([]);
    const [groups, setGroups] = useState<Record<string, string[]>>({});
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [granted, setGranted] = useState<string[]>([]);
    const [newName, setNewName] = useState<string | null>(null);
    const [nameError, setNameError] = useState<string>();
    const [deleting, setDeleting] = useState<Role | null>(null);

    const load = useCallback(async () => {
        const [roleResponse, permissionResponse] = await Promise.all([
            api.get<{ data: Role[] }>('/accounts/roles'),
            api.get<{ data: Record<string, string[]> }>('/accounts/permissions'),
        ]);
        setRoles(roleResponse.data);
        setGroups(permissionResponse.data);
        setSelectedId((current) => current ?? roleResponse.data[0]?.id ?? null);
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const selected = roles.find((role) => role.id === selectedId) ?? null;

    useEffect(() => {
        setGranted(selected?.permissions ?? []);
    }, [selected]);

    const save = async () => {
        if (!selected) {
            return;
        }

        try {
            await api.put(`/accounts/roles/${selected.id}`, { permissions: granted });
            toast.success(t('core::admin.common.saved'));
            await load();
        } catch {
            toast.error(t('core::admin.common.error'));
        }
    };

    const create = async () => {
        try {
            const response = await api.post<{ data: Role }>('/accounts/roles', { name: newName, permissions: [] });
            setNewName(null);
            setSelectedId(response.data.id);
            await load();
        } catch (error) {
            setNameError(error instanceof ApiError ? error.errors.name?.[0] : t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/accounts/roles/${deleting.id}`);
            setSelectedId(null);
            await load();
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    const locked = selected?.name === 'super_admin';

    return (
        <div className="space-y-4">
            <HeaderActions.Fill>
                <Button onClick={() => { setNameError(undefined); setNewName(''); }}>
                    <PlusIcon /> {roleKey('new')}
                </Button>
            </HeaderActions.Fill>

            <h1 className="text-2xl font-semibold tracking-tight">{roleKey('title')}</h1>

            {newName !== null && (
                <Card>
                    <CardContent className="flex flex-wrap items-end gap-3 pt-4">
                        <div className="min-w-64 flex-1">
                            <Field label={roleKey('name')} htmlFor="role-name" error={nameError}>
                                <Input id="role-name" value={newName} onChange={(event) => setNewName(event.target.value)} />
                            </Field>
                        </div>
                        <Button onClick={() => void create()}>{t('core::admin.common.save')}</Button>
                        <Button variant="outline" onClick={() => setNewName(null)}>{t('core::admin.common.cancel')}</Button>
                    </CardContent>
                </Card>
            )}

            <div className="grid gap-4 lg:grid-cols-[16rem_1fr]">
                <div className="space-y-1">
                    {roles.map((role) => (
                        <button
                            key={role.id}
                            type="button"
                            onClick={() => setSelectedId(role.id)}
                            className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm ${role.id === selectedId ? 'border-primary bg-primary/5' : 'bg-card hover:bg-muted'}`}
                        >
                            <span>{role.label}</span>
                            <span className="text-xs text-muted-foreground">{bnDigits(role.users_count ?? 0)}</span>
                        </button>
                    ))}
                </div>

                {selected && (
                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle className="flex items-center gap-2">
                                {selected.label}
                                {selected.is_system && <Badge variant="secondary">{roleKey('system')}</Badge>}
                            </CardTitle>
                            {!selected.is_system && (
                                <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(selected)}>
                                    <Trash2Icon />
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {Object.entries(groups).map(([group, names]) => (
                                <div key={group}>
                                    <p className="mb-2 text-sm font-medium">{t(`accounts::admin.roles.groups.${group}`) === `accounts::admin.roles.groups.${group}` ? group : t(`accounts::admin.roles.groups.${group}`)}</p>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {names.map((name) => (
                                            <label key={name} className="flex items-center gap-2 text-sm">
                                                <Checkbox
                                                    disabled={locked}
                                                    checked={granted.includes(name)}
                                                    onCheckedChange={(checked) => setGranted(checked ? [...granted, name] : granted.filter((item) => item !== name))}
                                                />
                                                <code className="text-xs">{name}</code>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                            {!locked && <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>}
                        </CardContent>
                    </Card>
                )}
            </div>

            <ConfirmDialog open={deleting !== null} description={deleting?.label} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
