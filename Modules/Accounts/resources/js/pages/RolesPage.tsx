import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Field } from '@core/js/components/Field';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, Panel, PanelTitle } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import type { Role } from '../types';

const roleKey = (key: string) => t(`accounts::admin.roles.${key}`);
const groupLabel = (group: string) => (t(`accounts::admin.roles.groups.${group}`) === `accounts::admin.roles.groups.${group}` ? group : t(`accounts::admin.roles.groups.${group}`));

/** Roles and what each may do: the role list beside a card of permission checkboxes, grouped by area. */
export function RolesPage() {
    const [roles, setRoles] = useState<Role[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [groups, setGroups] = useState<Record<string, string[]>>({});
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [granted, setGranted] = useState<string[]>([]);
    const [newName, setNewName] = useState<string | null>(null);
    const [nameError, setNameError] = useState<string>();
    const [deleting, setDeleting] = useState<Role | null>(null);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            const [roleResponse, permissionResponse] = await Promise.all([api.get<{ data: Role[] }>('/accounts/roles'), api.get<{ data: Record<string, string[]> }>('/accounts/permissions')]);
            setRoles(roleResponse.data);
            setGroups(permissionResponse.data);
            setSelectedId((current) => current ?? roleResponse.data[0]?.id ?? null);
        } catch {
            setFailed(true);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const selected = roles?.find((role) => role.id === selectedId) ?? null;

    useEffect(() => {
        setGranted(selected?.permissions ?? []);
    }, [selected]);

    if (failed) {
        return <ErrorState onRetry={() => void load()} />;
    }

    if (!roles) {
        return <LoadingState />;
    }

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
        <div className="grid gap-4">
            <HeaderActions.Fill>
                <Button onClick={() => { setNameError(undefined); setNewName(''); }}>+ {roleKey('new')}</Button>
            </HeaderActions.Fill>

            {newName !== null && (
                <Panel className="flex flex-wrap items-end gap-3">
                    <div className="min-w-64 flex-1">
                        <Field label={roleKey('name')} htmlFor="role-name" error={nameError}>
                            <Input id="role-name" value={newName} onChange={(event) => setNewName(event.target.value)} />
                        </Field>
                    </div>
                    <Button onClick={() => void create()}>{t('core::admin.common.save')}</Button>
                    <Button variant="outline" onClick={() => setNewName(null)}>
                        {t('core::admin.common.cancel')}
                    </Button>
                </Panel>
            )}

            <div className="grid items-start gap-4 lg:grid-cols-[16rem_1fr]">
                <div className="grid gap-1.5">
                    {roles.map((role) => (
                        <button
                            key={role.id}
                            type="button"
                            onClick={() => setSelectedId(role.id)}
                            className={`flex min-h-12 w-full cursor-pointer items-center justify-between rounded-[14px] border px-3.5 text-left font-semibold ${role.id === selectedId ? 'border-primary bg-green-50 text-green-700' : 'border-line bg-surface text-foreground hover:bg-surface-2'}`}
                        >
                            <span>{role.label}</span>
                            <span className="text-[13px] font-medium text-muted-foreground">{bnDigits(role.users_count ?? 0)}</span>
                        </button>
                    ))}
                </div>

                {selected && (
                    <Panel className="gap-4">
                        <div className="flex items-center justify-between gap-2">
                            <PanelTitle className="flex items-center gap-2">
                                {selected.label}
                                {selected.is_system && <Badge variant="secondary">{roleKey('system')}</Badge>}
                            </PanelTitle>
                            {!selected.is_system && (
                                <IconButton tone="danger" aria-label={t('core::admin.common.delete')} title={t('core::admin.common.delete')} onClick={() => setDeleting(selected)}>
                                    🗑
                                </IconButton>
                            )}
                        </div>
                        {Object.entries(groups).map(([group, names]) => (
                            <div key={group} className="grid gap-1">
                                <p className="m-0 text-sm font-bold text-muted-foreground">{groupLabel(group)}</p>
                                <div className="grid gap-x-3 sm:grid-cols-2">
                                    {names.map((name) => (
                                        <label key={name} className="flex min-h-11 cursor-pointer items-center gap-2.5">
                                            <input
                                                type="checkbox"
                                                className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700"
                                                disabled={locked}
                                                checked={granted.includes(name)}
                                                onChange={(event) => setGranted(event.target.checked ? [...granted, name] : granted.filter((item) => item !== name))}
                                            />
                                            <code className="text-[13px]">{name}</code>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        ))}
                        {!locked && (
                            <Button size="lg" onClick={() => void save()}>
                                {t('core::admin.common.save')}
                            </Button>
                        )}
                    </Panel>
                )}
            </div>

            <ConfirmDialog open={deleting !== null} description={deleting?.label} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
