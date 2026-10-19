import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { t } from '@core/js/i18n';

import type { Album } from '../types';

const k = (key: string) => t(`gallery::admin.${key}`);

const blank = { title_bn: '', title_en: '', description_bn: '', description_en: '', album_date: '', sort_order: 0, is_published: true };

/** Create or edit an album: its names, description, date, order and whether it is shown. */
export function AlbumFormDialog({ album, open, onClose, onSaved }: { album: Album | null; open: boolean; onClose: () => void; onSaved: (saved: Album) => void }) {
    const [values, setValues] = useState(blank);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (open) {
            setErrors({});
            setValues(album ? { title_bn: album.title_bn, title_en: album.title_en ?? '', description_bn: album.description_bn ?? '', description_en: album.description_en ?? '', album_date: album.album_date ?? '', sort_order: album.sort_order, is_published: album.is_published } : blank);
        }
    }, [open, album]);

    const set = (key: keyof typeof blank, value: string | number | boolean) => setValues((current) => ({ ...current, [key]: value }));

    const save = async () => {
        setBusy(true);
        setErrors({});

        try {
            const response = album ? await api.put<{ data: Album }>(`/gallery/albums/${album.id}`, values) : await api.post<{ data: Album }>('/gallery/albums', values);
            toast.success(t('core::admin.common.saved'));
            onSaved(response.data);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(Object.fromEntries(Object.entries(error.errors).map(([field, messages]) => [field, messages[0]])));
            }

            toast.error(t('core::admin.common.error'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{album ? k('edit') : k('new')}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="grid gap-2.5 sm:grid-cols-2">
                        <Field label={k('title_bn')} htmlFor="a-title-bn" error={errors.title_bn}>
                            <Input id="a-title-bn" value={values.title_bn} onChange={(event) => set('title_bn', event.target.value)} />
                        </Field>
                        <Field label={k('title_en')} htmlFor="a-title-en" error={errors.title_en}>
                            <Input id="a-title-en" value={values.title_en} onChange={(event) => set('title_en', event.target.value)} />
                        </Field>
                    </div>
                    <Field label={k('description_bn')} htmlFor="a-desc-bn" error={errors.description_bn}>
                        <Textarea id="a-desc-bn" rows={2} value={values.description_bn} onChange={(event) => set('description_bn', event.target.value)} />
                    </Field>
                    <Field label={k('description_en')} htmlFor="a-desc-en" error={errors.description_en}>
                        <Textarea id="a-desc-en" rows={2} value={values.description_en} onChange={(event) => set('description_en', event.target.value)} />
                    </Field>
                    <div className="grid gap-2.5 sm:grid-cols-2">
                        <Field label={k('album_date')} htmlFor="a-date" error={errors.album_date}>
                            <Input id="a-date" type="date" value={values.album_date} onChange={(event) => set('album_date', event.target.value)} />
                        </Field>
                        <Field label={k('sort_order')} htmlFor="a-order" error={errors.sort_order}>
                            <Input id="a-order" type="number" value={values.sort_order} onChange={(event) => set('sort_order', Number(event.target.value))} />
                        </Field>
                    </div>
                    <label className="flex min-h-11 cursor-pointer items-center gap-2.5">
                        <input type="checkbox" className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700" checked={values.is_published} onChange={(event) => set('is_published', event.target.checked)} />
                        {k('is_published')}
                    </label>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {k('page.cancel')}
                        </Button>
                        <Button disabled={busy || !values.title_bn.trim()} onClick={() => void save()}>
                            {k('page.save')}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
