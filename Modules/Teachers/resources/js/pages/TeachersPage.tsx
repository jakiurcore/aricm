import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { TablePagination } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { PhotoSlot } from '@core/js/components/PhotoSlot';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

import type { Teacher } from '../types';

const k = (key: string, replacements?: Record<string, string | number>) => t(`teachers::admin.list.${key}`, replacements);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

const STATUS_VARIANT = { active: 'default', leave: 'warning', inactive: 'secondary' } as const;

interface Draft {
    name_bn: string;
    name_en: string;
    designation: string;
    phone: string;
    email: string;
    joined_on: string;
    status: string;
    create_login: boolean;
}

const emptyDraft: Draft = { name_bn: '', name_en: '', designation: '', phone: '', email: '', joined_on: '', status: 'active', create_login: false };

function TeacherDialog({ teacher, open, onClose, onSaved, onDelete }: { teacher: Teacher | null; open: boolean; onClose: () => void; onSaved: (message?: string) => void; onDelete: (teacher: Teacher) => void }) {
    const [draft, setDraft] = useState<Draft>(emptyDraft);
    const [photo, setPhoto] = useState<File | null>(null);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (open) {
            setPhoto(null);
            setError('');
            setDraft(teacher ? { name_bn: teacher.name_bn, name_en: teacher.name_en ?? '', designation: teacher.designation ?? '', phone: teacher.phone ?? '', email: teacher.email ?? '', joined_on: teacher.joined_on ?? '', status: teacher.status, create_login: false } : emptyDraft);
        }
    }, [open, teacher]);

    const set = (key: keyof Draft, value: string | boolean) => setDraft((current) => ({ ...current, [key]: value }));
    const hasLogin = teacher?.user_id != null;

    const save = async () => {
        setBusy(true);
        setError('');
        const body = new FormData();
        Object.entries(draft).forEach(([key, value]) => key !== 'create_login' && body.append(key, String(value)));

        if (draft.create_login && !hasLogin) {
            body.append('create_login', '1');
        }

        if (photo) {
            body.append('photo', photo);
        }

        try {
            let response: { temporary_password?: string };

            if (teacher) {
                body.append('_method', 'PUT');
                response = await api.post(`/teachers/${teacher.id}`, body);
            } else {
                response = await api.post('/teachers', body);
            }

            onSaved(response.temporary_password ? k('login_created', { password: response.temporary_password }) : undefined);
        } catch (failure) {
            setError(message(failure));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k(teacher ? 'title_edit' : 'title_new')}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="flex items-center gap-3.5">
                        <PhotoSlot shape="circle" src={teacher?.photo_url} placeholder={k('photo')} onChange={setPhoto} />
                        <span className="text-[13px] text-muted-foreground">{k('photo_hint')}</span>
                    </div>
                    <div className="grid gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 220px), 1fr))' }}>
                        <Field label={k('name_bn')} htmlFor="t-name_bn">
                            <Input id="t-name_bn" value={draft.name_bn} onChange={(event) => set('name_bn', event.target.value)} />
                        </Field>
                        <Field label={k('name_en')} htmlFor="t-name_en">
                            <Input id="t-name_en" value={draft.name_en} onChange={(event) => set('name_en', event.target.value)} />
                        </Field>
                        <Field label={k('designation')} htmlFor="t-designation">
                            <Input id="t-designation" value={draft.designation} onChange={(event) => set('designation', event.target.value)} />
                        </Field>
                        <Field label={k('phone')} htmlFor="t-phone">
                            <Input id="t-phone" type="tel" value={draft.phone} onChange={(event) => set('phone', event.target.value)} />
                        </Field>
                        <Field label={k('email')} htmlFor="t-email">
                            <Input id="t-email" type="email" value={draft.email} onChange={(event) => set('email', event.target.value)} />
                        </Field>
                        <Field label={k('joined_on')} htmlFor="t-joined_on">
                            <Input id="t-joined_on" type="date" value={draft.joined_on} onChange={(event) => set('joined_on', event.target.value)} />
                        </Field>
                        <Field label={k('status')} htmlFor="t-status">
                            <NativeSelect id="t-status" value={draft.status} onChange={(event) => set('status', event.target.value)} options={['active', 'leave', 'inactive'].map((value) => ({ value, label: k(`statuses.${value}`) }))} />
                        </Field>
                    </div>
                    <button
                        type="button"
                        disabled={hasLogin}
                        onClick={() => set('create_login', !draft.create_login)}
                        className="flex min-h-12 cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 px-3 text-left text-foreground disabled:cursor-default"
                    >
                        <span className={`grid size-[22px] flex-none place-items-center rounded-md border-2 border-green-700 text-[13px] text-white ${draft.create_login || hasLogin ? 'bg-green-700' : 'bg-transparent'}`}>{draft.create_login || hasLogin ? '✓' : ''}</span>
                        <span className="flex-1 font-semibold">{hasLogin ? k('has_login') : k('create_login')}</span>
                    </button>
                    {error && <div className="rounded-xl bg-danger-bg px-3.5 py-2.5 text-sm font-semibold text-danger">{error}</div>}
                    <DialogFooter>
                        {teacher && can('teachers.manage') && (
                            <Button type="button" variant="outline" className="border-danger text-danger sm:mr-auto" onClick={() => onDelete(teacher)}>
                                {k('delete')}
                            </Button>
                        )}
                        <Button type="button" variant="outline" onClick={onClose}>
                            {k('cancel')}
                        </Button>
                        <Button disabled={busy || !draft.name_bn} onClick={() => void save()}>
                            {k('save')}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function ResetDialog({ teacher, onClose }: { teacher: Teacher | null; onClose: () => void }) {
    const [done, setDone] = useState<{ password: string | null; sms: boolean } | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => setDone(null), [teacher]);

    const reset = async () => {
        if (!teacher) {
            return;
        }

        setBusy(true);

        try {
            const response = await api.put<{ data: { temporary_password: string | null; sms_sent: boolean } }>(`/teachers/${teacher.id}/password`, {});
            setDone({ password: response.data.temporary_password, sms: response.data.sms_sent });
        } catch (failure) {
            toast.error(message(failure));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={teacher !== null} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k('reset_title')}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <span>{k('reset_body', { name: teacher?.name_bn ?? '', phone: bnDigits(teacher?.phone ?? '') })}</span>
                    {done && (
                        <div className="grid gap-1 rounded-[14px] bg-success-bg p-3.5 text-success">
                            <strong>
                                ✓ {k('reset_done', { password: '' })}
                                <span className="font-[Poppins] tracking-[.05em]">{done.password}</span>
                            </strong>
                            <span className="text-sm text-foreground">{done.sms ? k('reset_sms') : k('reset_no_sms')}</span>
                        </div>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {k('close')}
                        </Button>
                        {!done && (
                            <Button variant="destructive" disabled={busy} className="bg-red-600" onClick={() => void reset()}>
                                {k('do_reset')}
                            </Button>
                        )}
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}

/** The design's "শিক্ষকবৃন্দ": search + add, the teacher table, the edit and reset dialogs. */
export function TeachersPage() {
    const manage = can('teachers.manage');
    const [search, setSearch] = useState('');
    const [debounced, setDebounced] = useState('');
    const { rows, meta, loading, failed, setPage, reload } = usePaginated<Teacher>('/teachers', { search: debounced });
    const [editing, setEditing] = useState<Teacher | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [resetting, setResetting] = useState<Teacher | null>(null);
    const [deleting, setDeleting] = useState<Teacher | null>(null);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(search), 300);

        return () => clearTimeout(timer);
    }, [search]);

    const openForm = useCallback((teacher: Teacher | null) => {
        setEditing(teacher);
        setFormOpen(true);
    }, []);

    const createLogin = async (teacher: Teacher) => {
        if (!teacher.email) {
            toast.error(k('need_email'));
            openForm(teacher);

            return;
        }

        try {
            const response = await api.post<{ temporary_password?: string }>(`/teachers/${teacher.id}/login`, {});
            toast.success(k('login_created', { password: response.temporary_password ?? '' }));
            reload();
        } catch (failure) {
            toast.error(message(failure));
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/teachers/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            setFormOpen(false);
            reload();
        } catch (failure) {
            toast.error(message(failure));
        } finally {
            setDeleting(null);
        }
    };

    return (
        <div className="grid gap-3.5">
            <div className="flex flex-wrap items-center gap-2">
                <Input className="flex-[1_1_240px] basis-60" style={{ width: 'auto' }} placeholder={k('search')} aria-label={k('search')} value={search} onChange={(event) => setSearch(event.target.value)} />
                {manage && <Button onClick={() => openForm(null)}>+ {k('add')}</Button>}
            </div>

            {failed && rows.length === 0 && <ErrorState onRetry={reload} />}
            {loading && rows.length === 0 && !failed && <LoadingState />}
            {!loading && !failed && rows.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{t('core::admin.common.empty')}</div>}

            {rows.length > 0 && (
                <div className="overflow-x-auto rounded-[20px] border border-line bg-surface">
                    <table className="w-full min-w-[1080px] border-collapse text-[15px]">
                        <thead>
                            <tr className="bg-surface-2 text-left">
                                <th className="px-3.5 py-3">{k('teacher')}</th>
                                <th className="px-2 py-3">{k('designation')}</th>
                                <th className="px-2 py-3">{k('contact')}</th>
                                <th className="px-2 py-3">{k('joined')}</th>
                                <th className="px-2 py-3">{k('status')}</th>
                                <th className="px-2 py-3">{k('login')}</th>
                                <th className="px-3.5 py-3 text-right">{k('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((teacher) => (
                                <tr key={teacher.id} className={`border-t border-line ${loading ? 'opacity-60' : ''}`}>
                                    <td className="px-3.5 py-2.5">
                                        <span className="flex items-center gap-2.5">
                                            <span className="grid size-10 flex-none place-items-center overflow-hidden rounded-full bg-green-100 font-bold text-green-700">
                                                {teacher.photo_url ? <img src={teacher.photo_url} alt="" className="size-full object-cover" /> : teacher.name_bn.trim().slice(0, 1)}
                                            </span>
                                            <span className="grid leading-[1.35] whitespace-nowrap">
                                                <strong>{teacher.name_bn}</strong>
                                                {teacher.name_en && <span className="font-[Poppins] text-[13px] text-muted-foreground">{teacher.name_en}</span>}
                                            </span>
                                        </span>
                                    </td>
                                    <td className="px-2 py-2.5 text-sm">{teacher.designation ?? '—'}</td>
                                    <td className="px-2 py-2.5 text-sm leading-[1.45]">
                                        {teacher.phone ? bnDigits(teacher.phone) : '—'}
                                        {teacher.email && (
                                            <>
                                                <br />
                                                <span className="font-[Poppins] text-[13px] text-muted-foreground">{teacher.email}</span>
                                            </>
                                        )}
                                    </td>
                                    <td className="px-2 py-2.5 text-sm whitespace-nowrap">{teacher.joined_on ? formatDate(teacher.joined_on) : '—'}</td>
                                    <td className="px-2 py-2.5">
                                        <Badge variant={STATUS_VARIANT[teacher.status]}>{k(`statuses.${teacher.status}`)}</Badge>
                                    </td>
                                    <td className="px-2 py-2.5">
                                        {teacher.user_id ? (
                                            <span className="text-[13px] font-bold text-success">{k('login_active')}</span>
                                        ) : (
                                            manage && (
                                                <button type="button" onClick={() => void createLogin(teacher)} className="h-9 min-h-0 cursor-pointer rounded-full border border-green-700 bg-transparent px-3 text-[13px] font-bold whitespace-nowrap text-green-700">
                                                    {k('make_login')}
                                                </button>
                                            )
                                        )}
                                    </td>
                                    <td className="px-3.5 py-2.5">
                                        {manage && (
                                            <span className="flex justify-end gap-1.5">
                                                <button type="button" onClick={() => openForm(teacher)} className="h-9 min-h-0 cursor-pointer rounded-full border border-line-strong bg-surface px-3 text-[13px] font-semibold">
                                                    {k('edit')}
                                                </button>
                                                {teacher.user_id && (
                                                    <button type="button" onClick={() => setResetting(teacher)} className="h-9 min-h-0 cursor-pointer rounded-full border border-line-strong bg-surface px-3 text-[13px] font-semibold whitespace-nowrap">
                                                        {k('reset')}
                                                    </button>
                                                )}
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {meta && <TablePagination meta={meta} onPageChange={setPage} />}

            <TeacherDialog teacher={editing} open={formOpen} onClose={() => setFormOpen(false)} onDelete={setDeleting} onSaved={(note) => { setFormOpen(false); toast.success(note ?? t('core::admin.common.saved')); reload(); }} />
            <ResetDialog teacher={resetting} onClose={() => setResetting(null)} />
            <ConfirmDialog open={deleting !== null} description={k('confirm_delete')} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
