import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DesignTable, PageTitle, Panel, Pill, TableCard, Td, Th, Toggle, type Tone } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

interface Head {
    id: number;
    name_bn: string;
    name_en: string | null;
    type: string;
    is_active: boolean;
    sort_order: number;
}

const k = (key: string) => t(`fees::admin.heads.${key}`);
const TONES: Record<string, Tone> = { monthly: 'green', one_time: 'yellow', exam: 'navy' };
const field = 'h-11 w-full min-w-0 rounded-xl border border-line-strong bg-surface px-3 text-base text-foreground outline-none placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 aria-invalid:border-danger';
const blank = { name_bn: '', name_en: '', type: 'monthly' };

/** Fee heads as in the design: an add / edit card on top, a table with the active switch and edit / delete pills below. */
export function HeadsPage() {
    const [heads, setHeads] = useState<Head[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState<Head | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [deleting, setDeleting] = useState<Head | null>(null);
    const editable = can('fees.manage_structure');

    const load = useCallback(async () => {
        setFailed(false);

        try {
            setHeads((await api.get<{ data: Head[] }>('/fees/heads')).data);
        } catch {
            setFailed(true);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const save = async () => {
        setErrors({});

        try {
            if (editing) {
                await api.put(`/fees/heads/${editing.id}`, { ...form, is_active: editing.is_active, sort_order: editing.sort_order });
            } else {
                await api.post('/fees/heads', { ...form, is_active: true, sort_order: Math.max(0, ...(heads ?? []).map((head) => head.sort_order)) + 1 });
            }

            toast.success(editing ? k('updated') : k('added'));
            setForm(blank);
            setEditing(null);
            await load();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const toggle = async (head: Head) => {
        try {
            await api.put(`/fees/heads/${head.id}`, { name_bn: head.name_bn, name_en: head.name_en ?? '', type: head.type, is_active: !head.is_active, sort_order: head.sort_order });
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/fees/heads/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>

            {editable && (
                <Panel className="items-end gap-2.5" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 180px), 1fr))' }}>
                    <Field label={k('name_bn')} htmlFor="head-bn" error={errors.name_bn?.[0]}>
                        <input id="head-bn" className={field} placeholder={k('name_bn_placeholder')} value={form.name_bn} onChange={(event) => setForm({ ...form, name_bn: event.target.value })} />
                    </Field>
                    <Field label={k('name_en')} htmlFor="head-en" error={errors.name_en?.[0]}>
                        <input id="head-en" className={field} style={{ fontFamily: 'Poppins, sans-serif' }} placeholder={k('name_en_placeholder')} value={form.name_en} onChange={(event) => setForm({ ...form, name_en: event.target.value })} />
                    </Field>
                    <Field label={k('type')} htmlFor="head-type">
                        <NativeSelect id="head-type" value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value })} options={['monthly', 'one_time', 'exam'].map((value) => ({ value, label: t(`fees::admin.types.${value}`) }))} />
                    </Field>
                    <span className="flex gap-2">
                        {editing && (
                            <Button variant="outline" onClick={() => { setEditing(null); setForm(blank); }}>
                                {t('core::admin.common.cancel')}
                            </Button>
                        )}
                        <Button disabled={!form.name_bn.trim()} onClick={() => void save()}>
                            {editing ? `✓ ${k('update')}` : `+ ${k('add')}`}
                        </Button>
                    </span>
                </Panel>
            )}

            {failed && <ErrorState onRetry={() => void load()} />}
            {heads === null && !failed && <LoadingState rows={4} height={48} />}
            {heads?.length === 0 && <EmptyState glyph="≡" title={t('core::admin.common.empty')} />}
            {heads && heads.length > 0 && (
                <TableCard>
                    <DesignTable minWidth={640}>
                        <thead>
                            <tr className="bg-surface-2">
                                <Th>{k('title_col')}</Th>
                                <Th>{k('name_en')}</Th>
                                <Th>{k('type')}</Th>
                                <Th>{k('is_active')}</Th>
                                <Th align="right">{t('core::admin.common.actions')}</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {heads.map((head) => (
                                <tr key={head.id} className="border-t border-line" style={{ opacity: head.is_active ? 1 : 0.55 }}>
                                    <Td className="font-bold">{head.name_bn}</Td>
                                    <Td className="text-sm text-muted-foreground" style={{ fontFamily: 'Poppins, sans-serif' }}>
                                        {head.name_en}
                                    </Td>
                                    <Td>
                                        <Pill tone={TONES[head.type] ?? 'muted'}>{t(`fees::admin.types.${head.type}`)}</Pill>
                                    </Td>
                                    <Td>
                                        <Toggle checked={head.is_active} disabled={!editable} label={k('is_active')} onChange={() => void toggle(head)} />
                                    </Td>
                                    <Td align="right">
                                        {editable && (
                                            <span className="inline-flex justify-end gap-1.5">
                                                <button type="button" onClick={() => { setEditing(head); setForm({ name_bn: head.name_bn, name_en: head.name_en ?? '', type: head.type }); window.scrollTo({ top: 0, behavior: 'smooth' }); }} className="h-[34px] cursor-pointer rounded-full border border-line-strong bg-surface px-3 text-[13px] font-bold whitespace-nowrap">
                                                    ✎ {t('core::admin.common.edit')}
                                                </button>
                                                <button type="button" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(head)} className="h-[34px] cursor-pointer rounded-full border border-red-600 bg-red-50 px-3 text-[13px] font-bold text-danger">
                                                    🗑
                                                </button>
                                            </span>
                                        )}
                                    </Td>
                                </tr>
                            ))}
                        </tbody>
                    </DesignTable>
                </TableCard>
            )}

            <ConfirmDialog open={deleting !== null} description={deleting?.name_bn} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
