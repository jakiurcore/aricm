import { RotateCcwIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Pill } from '@core/js/components/design';
import { PhotoSlot } from '@core/js/components/PhotoSlot';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Panel, PanelTitle, StickyBar } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';

import type { ContentEntry } from '../types';

const k = (key: string, replacements?: Record<string, string | number>) => t(`website::admin.ui.${key}`, replacements);

type Draft = { bn: string; en: string };

/**
 * The editable texts and pictures of one page of the public site, generated from the server's schema. Texts sit side by
 * side in Bangla and English with the current default as the placeholder; a blank field keeps showing the default.
 */
export function ContentForm({ page, editable = true }: { page: string; editable?: boolean }) {
    const [entries, setEntries] = useState<ContentEntry[] | null>(null);
    const [drafts, setDrafts] = useState<Record<string, Draft>>({});
    const [failed, setFailed] = useState(false);
    const [saving, setSaving] = useState(false);
    const [busyImage, setBusyImage] = useState<string | null>(null);
    const [errors, setErrors] = useState<string | null>(null);

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ schema: ContentEntry[] }>('/website/content')
            .then((response) => setEntries(response.schema.filter((entry) => entry.page === page)))
            .catch(() => setFailed(true));
    }, [page]);

    useEffect(load, [load]);

    const adopt = (schema: ContentEntry[]) => setEntries(schema.filter((entry) => entry.page === page));

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!entries) {
        return <LoadingState rows={3} height={140} />;
    }

    const current = (entry: ContentEntry): Draft => drafts[entry.key] ?? { bn: entry.value_bn ?? '', en: entry.value_en ?? '' };
    const changedKeys = entries.filter((entry) => entry.type !== 'image' && drafts[entry.key] && (drafts[entry.key].bn !== (entry.value_bn ?? '') || drafts[entry.key].en !== (entry.value_en ?? ''))).map((entry) => entry.key);

    const edit = (entry: ContentEntry, locale: 'bn' | 'en', value: string) => setDrafts((all) => ({ ...all, [entry.key]: { ...current(entry), [locale]: value } }));

    const save = async () => {
        setSaving(true);
        setErrors(null);

        try {
            const values = Object.fromEntries(changedKeys.map((key) => [key, drafts[key]]));
            const response = await api.put<{ schema: ContentEntry[] }>('/website/content', { values });
            adopt(response.schema);
            setDrafts((all) => Object.fromEntries(Object.entries(all).filter(([key]) => !changedKeys.includes(key))));
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            const message = error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error');
            setErrors(message);
            toast.error(message);
        } finally {
            setSaving(false);
        }
    };

    const upload = async (entry: ContentEntry, file: File | null) => {
        if (!file) {
            return;
        }

        setBusyImage(entry.key);
        const form = new FormData();
        form.append('image', file);

        try {
            const response = await api.post<{ schema: ContentEntry[] }>(`/website/content/image/${entry.key}`, form);
            adopt(response.schema);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(error instanceof ApiError ? (error.errors.image?.[0] ?? error.message) : t('core::admin.common.error'));
        } finally {
            setBusyImage(null);
        }
    };

    const resetImage = async (entry: ContentEntry) => {
        setBusyImage(entry.key);

        try {
            const response = await api.delete<{ schema: ContentEntry[] }>(`/website/content/image/${entry.key}`);
            adopt(response.schema);
            toast.success(k('image_reset_done'));
        } catch {
            toast.error(t('core::admin.common.error'));
        } finally {
            setBusyImage(null);
        }
    };

    const groups = [...new Set(entries.map((entry) => entry.group))];

    return (
        <fieldset disabled={!editable} className="m-0 grid min-w-0 gap-4 border-0 p-0">
            {groups.map((group) => (
                <Panel key={group} className="gap-3.5">
                    <PanelTitle>{t(`website::admin.groups.${group}`)}</PanelTitle>
                    {entries
                        .filter((entry) => entry.group === group)
                        .map((entry) =>
                            entry.type === 'image' ? (
                                <div key={entry.key} className="grid gap-1.5">
                                    <span className="text-sm font-semibold">{t(entry.label)}</span>
                                    <div className="flex flex-wrap items-center gap-3.5">
                                        <PhotoSlot id={`img-${entry.key}`} className="h-[116px] w-40" src={entry.image_url} placeholder={k('choose_image')} onChange={(file) => void upload(entry, file)} />
                                        <div className="grid justify-items-start gap-1.5">
                                            <span className="text-[13px] text-muted-foreground">{busyImage === entry.key ? k('uploading') : entry.has_image_override ? k('image_custom') : k('image_default')}</span>
                                            {entry.has_image_override && (
                                                <Button type="button" variant="outline" size="sm" disabled={busyImage === entry.key} onClick={() => void resetImage(entry)}>
                                                    <RotateCcwIcon className="size-4" />
                                                    {k('use_default_image')}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div key={entry.key} className="grid gap-1">
                                    <span className="text-sm font-semibold">{t(entry.label)}</span>
                                    <div className="grid gap-2.5 sm:grid-cols-2">
                                        {(['bn', 'en'] as const).map((locale) => {
                                            const id = `${entry.key}-${locale}`;
                                            const placeholder = (locale === 'bn' ? entry.default_bn : entry.default_en) ?? '';
                                            const common = { id, value: current(entry)[locale], placeholder, onChange: (event: { target: { value: string } }) => edit(entry, locale, event.target.value), lang: locale, 'aria-label': `${t(entry.label)} (${locale === 'bn' ? k('bangla') : k('english')})` };

                                            return (
                                                <div key={locale} className="grid gap-1">
                                                    <span className="text-xs font-semibold text-muted-foreground">{locale === 'bn' ? k('bangla') : k('english')}</span>
                                                    {entry.type === 'textarea' ? <Textarea rows={3} className="min-h-0 py-2.5 font-normal" {...common} /> : <Input {...common} />}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ),
                        )}
                </Panel>
            ))}
            {editable && (
                <StickyBar className="items-center">
                    {errors && <span className="mr-auto text-sm font-medium text-danger">{errors}</span>}
                    {changedKeys.length > 0 && <Pill tone="warn" size="md">{k('unsaved', { count: changedKeys.length })}</Pill>}
                    <Button size="lg" onClick={() => void save()} disabled={saving || changedKeys.length === 0}>
                        {saving ? t('core::admin.common.saving') : k('save_changes')}
                    </Button>
                </StickyBar>
            )}
        </fieldset>
    );
}

