import { PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Pill, Toggle } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { PhotoSlot } from '@core/js/components/PhotoSlot';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, ListCard, ListCardHead, ListRow } from '@core/js/components/Surface';
import { locale, t } from '@core/js/i18n';

import { firstErrors, moved } from '../types';
import type { WebsiteItem } from '../types';
import { IconPicker, IconPreview } from './IconPicker';
import { ReorderButtons } from './ReorderButtons';

const k = (key: string, replacements?: Record<string, string | number>) => t(`website::admin.ui.${key}`, replacements);

export interface MetaField {
    name: string;
    /** Translation key under website::admin.ui.meta. */
    label: string;
    type?: 'text' | 'number';
    /** The key of a division can only be chosen once: it links prices to the column. */
    lockedWhenEditing?: boolean;
}

interface ItemListEditorProps {
    collection: string;
    title: string;
    hint?: string;
    /** Which optional inputs the dialog shows besides the title. */
    fields?: { body?: boolean; icon?: boolean; image?: boolean; meta?: MetaField[] };
    /** Called after the list changed, so a sibling (the price table columns) can refresh. */
    onChanged?: () => void;
    editable?: boolean;
}

interface Draft {
    title_bn: string;
    title_en: string;
    body_bn: string;
    body_en: string;
    icon: string;
    is_active: boolean;
    meta: Record<string, string>;
}

const toDraft = (item: WebsiteItem | null): Draft => ({
    title_bn: item?.title_bn ?? '',
    title_en: item?.title_en ?? '',
    body_bn: item?.body_bn ?? '',
    body_en: item?.body_en ?? '',
    icon: item?.icon ?? '',
    is_active: item?.is_active ?? true,
    meta: Object.fromEntries(Object.entries(item?.meta ?? {}).map(([name, value]) => [name, value === null ? '' : String(value)])),
});

/** The API takes a multipart form so that a picture can travel with the fields; an update is a POST spoofed as PUT. */
function toForm(draft: Draft, image: File | null, removeImage: boolean, update: boolean): FormData {
    const form = new FormData();
    const text = ['title_bn', 'title_en', 'body_bn', 'body_en', 'icon'] as const;

    text.forEach((name) => form.append(name, draft[name]));
    form.append('is_active', draft.is_active ? '1' : '0');
    Object.entries(draft.meta).forEach(([name, value]) => form.append(`meta[${name}]`, value));

    if (image) {
        form.append('image', image);
    }

    if (removeImage) {
        form.append('remove_image', '1');
    }

    if (update) {
        form.append('_method', 'PUT');
    }

    return form;
}

/** A list of the site's repeated blocks (programs, divisions...): add, edit, hide, reorder and delete. */
export function ItemListEditor({ collection, title, hint, fields = {}, onChanged, editable = true }: ItemListEditorProps) {
    const [items, setItems] = useState<WebsiteItem[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [editing, setEditing] = useState<WebsiteItem | 'new' | null>(null);
    const [deleting, setDeleting] = useState<WebsiteItem | null>(null);

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: WebsiteItem[] }>(`/website/items/${collection}`)
            .then((response) => setItems(response.data))
            .catch(() => setFailed(true));
    }, [collection]);

    useEffect(load, [load]);

    const localized = (item: WebsiteItem) => (locale() === 'en' && item.title_en ? item.title_en : item.title_bn);
    const secondary = (item: WebsiteItem) => (locale() === 'en' ? item.title_bn : item.title_en);

    const reorder = async (index: number, step: -1 | 1) => {
        if (!items) {
            return;
        }

        const next = moved(items, index, step);
        setItems(next);

        try {
            const response = await api.post<{ data: WebsiteItem[] }>(`/website/items/${collection}/reorder`, { ids: next.map((item) => item.id) });
            setItems(response.data);
            onChanged?.();
        } catch {
            toast.error(t('core::admin.common.error'));
            load();
        }
    };

    const toggle = async (item: WebsiteItem) => {
        try {
            const response = await api.post<{ data: WebsiteItem }>(`/website/items/${collection}/${item.id}`, toForm({ ...toDraft(item), is_active: !item.is_active }, null, false, true));
            setItems((all) => all?.map((row) => (row.id === item.id ? response.data : row)) ?? null);
            onChanged?.();
        } catch {
            toast.error(t('core::admin.common.error'));
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/website/items/${collection}/${deleting.id}`);
            setItems((all) => all?.filter((row) => row.id !== deleting.id) ?? null);
            toast.success(k('deleted'));
            onChanged?.();
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setDeleting(null);
        }
    };

    const add = editable && (
        <Button type="button" size="sm" onClick={() => setEditing('new')}>
            <PlusIcon className="size-4" />
            {k('add')}
        </Button>
    );

    return (
        <div className="grid gap-2">
            {hint && <p className="m-0 text-[13px] text-muted-foreground">{hint}</p>}
            {failed ? (
                <ErrorState onRetry={load} />
            ) : !items ? (
                <LoadingState rows={2} height={64} />
            ) : (
                <ListCard>
                    <ListCardHead action={add}>{title}</ListCardHead>
                    {items.length === 0 && (
                        <div className="p-3">
                            <EmptyState title={k('list_empty')} glyph="🗂" />
                        </div>
                    )}
                    {items.map((item, index) => (
                        <ListRow key={item.id} data-testid={`item-${collection}`}>
                            {editable && <ReorderButtons index={index} count={items.length} onMove={(step) => void reorder(index, step)} />}
                            {fields.image && item.image_url && <img src={item.image_url} alt="" className="size-11 flex-none rounded-lg object-cover" />}
                            {fields.icon && item.icon && <span className="grid size-10 flex-none place-items-center rounded-[10px] bg-surface-2 text-xl text-green-700"><IconPreview value={item.icon} /></span>}
                            <span className="grid min-w-0 flex-1 leading-snug">
                                <strong className="truncate">{localized(item)}</strong>
                                {secondary(item) && <span className="truncate text-[13px] text-muted-foreground">{secondary(item)}</span>}
                            </span>
                            {!item.is_active && <Pill tone="muted">{k('hidden')}</Pill>}
                            {editable && (
                                <>
                                    <Toggle checked={item.is_active} label={k('show_on_site')} onChange={() => void toggle(item)} />
                                    <IconButton aria-label={t('core::admin.common.edit')} onClick={() => setEditing(item)}>
                                        <PencilIcon className="size-4" />
                                    </IconButton>
                                    <IconButton tone="danger" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(item)}>
                                        <Trash2Icon className="size-4" />
                                    </IconButton>
                                </>
                            )}
                        </ListRow>
                    ))}
                </ListCard>
            )}

            {editing !== null && (
                <ItemDialog
                    collection={collection}
                    item={editing === 'new' ? null : editing}
                    fields={fields}
                    onClose={() => setEditing(null)}
                    onSaved={() => {
                        setEditing(null);
                        load();
                        onChanged?.();
                    }}
                />
            )}
            <ConfirmDialog open={deleting !== null} description={deleting ? localized(deleting) : undefined} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}

function ItemDialog({ collection, item, fields, onClose, onSaved }: { collection: string; item: WebsiteItem | null; fields: NonNullable<ItemListEditorProps['fields']>; onClose: () => void; onSaved: () => void }) {
    const [draft, setDraft] = useState<Draft>(() => toDraft(item));
    const [image, setImage] = useState<File | null>(null);
    const [removeImage, setRemoveImage] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const set = (name: keyof Omit<Draft, 'meta'>, value: string | boolean) => setDraft((all) => ({ ...all, [name]: value }));
    const setMeta = (name: string, value: string) => setDraft((all) => ({ ...all, meta: { ...all.meta, [name]: value } }));

    const save = async () => {
        setBusy(true);
        setErrors({});

        try {
            const form = toForm(draft, image, removeImage, item !== null);
            await api.post(item ? `/website/items/${collection}/${item.id}` : `/website/items/${collection}`, form);
            toast.success(t('core::admin.common.saved'));
            onSaved();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(firstErrors(error.errors));
            }

            toast.error(t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    const pair = (name: 'title' | 'body', multiline: boolean) =>
        (['bn', 'en'] as const).map((language) => {
            const field = `${name}_${language}` as 'title_bn';
            const label = `${k(name)} (${language === 'bn' ? k('bangla') : k('english')})`;

            return (
                <Field key={field} label={label} htmlFor={`i-${field}`} error={errors[field]}>
                    {multiline ? <Textarea id={`i-${field}`} rows={3} className="min-h-0 py-2.5 font-normal" value={draft[field]} onChange={(event) => set(field, event.target.value)} /> : <Input id={`i-${field}`} value={draft[field]} onChange={(event) => set(field, event.target.value)} />}
                </Field>
            );
        });

    return (
        <Dialog open onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-h-[92dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{item ? k('edit_item') : k('new_item')}</DialogTitle>
                    <DialogDescription>{k('blank_english_hint')}</DialogDescription>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="grid gap-2.5 sm:grid-cols-2">{pair('title', false)}</div>
                    {fields.body && <div className="grid gap-2.5 sm:grid-cols-2">{pair('body', true)}</div>}
                    {fields.icon && (
                        <Field label={k('icon')} htmlFor="i-icon" error={errors.icon}>
                            <IconPicker id="i-icon" value={draft.icon} onChange={(value) => set('icon', value)} />
                        </Field>
                    )}
                    {fields.meta && (
                        <div className="grid gap-2.5 sm:grid-cols-2">
                            {fields.meta.map((meta) => (
                                <Field key={meta.name} label={k(`meta.${meta.label}`)} htmlFor={`i-meta-${meta.name}`} hint={meta.lockedWhenEditing ? k('meta.key_hint') : undefined} error={errors[`meta.${meta.name}`]}>
                                    <Input id={`i-meta-${meta.name}`} type={meta.type === 'number' ? 'number' : 'text'} disabled={meta.lockedWhenEditing && item !== null} value={draft.meta[meta.name] ?? ''} onChange={(event) => setMeta(meta.name, event.target.value)} />
                                </Field>
                            ))}
                        </div>
                    )}
                    {fields.image && (
                        <div className="grid gap-1.5">
                            <span className="text-sm font-semibold">{k('image')}</span>
                            <div className="flex items-center gap-3">
                                <PhotoSlot src={removeImage ? null : item?.image_url} placeholder={k('choose_image')} onChange={(file) => { setImage(file); setRemoveImage(false); }} />
                                {item?.image_url && !removeImage && !image && (
                                    <Button type="button" variant="outline" size="sm" onClick={() => setRemoveImage(true)}>
                                        {k('remove_image')}
                                    </Button>
                                )}
                            </div>
                            {errors.image && <p className="text-[13px] font-medium text-danger">{errors.image}</p>}
                        </div>
                    )}
                    <label className="flex min-h-11 cursor-pointer items-center gap-2.5">
                        <input type="checkbox" className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700" checked={draft.is_active} onChange={(event) => set('is_active', event.target.checked)} />
                        {k('show_on_site')}
                    </label>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button disabled={busy || !draft.title_bn.trim()} onClick={() => void save()}>
                            {busy ? t('core::admin.common.saving') : t('core::admin.common.save')}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
