import { PencilIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { Pill, Toggle } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, ListCard, ListCardHead, ListRow, StickyBar } from '@core/js/components/Surface';
import { locale, t } from '@core/js/i18n';

import { moved } from '../types';
import type { NavItem } from '../types';
import { ReorderButtons } from './ReorderButtons';

const k = (key: string, replacements?: Record<string, string | number>) => t(`website::admin.ui.menu.${key}`, replacements);
const ui = (key: string) => t(`website::admin.ui.${key}`);

type Settings = Record<string, unknown> & { nav_items?: NavItem[] | null };
type Location = NavItem['location'];

/** The public pages a menu entry can point at: the route name, the English and Bangla label used when the menu is first customised. */
const PAGES = [
    { route: 'home', page: 'home', bn: 'হোম', en: 'Home' },
    { route: 'website.about', page: 'about', bn: 'আমাদের সম্পর্কে', en: 'About us' },
    { route: 'website.academics', page: 'academics', bn: 'শিক্ষা কার্যক্রম', en: 'Academics' },
    { route: 'admission.public', page: 'admission', bn: 'ভর্তি', en: 'Admission' },
    { route: 'notices.public.index', page: 'notices', bn: 'নোটিশ', en: 'Notices' },
    { route: 'website.routine', page: 'routine', bn: 'পরীক্ষার রুটিন', en: 'Exam routine' },
    { route: 'results.public', page: 'results', bn: 'ফলাফল', en: 'Results' },
    { route: 'gallery.public.index', page: 'gallery', bn: 'গ্যালারি', en: 'Gallery' },
    { route: 'website.contact', page: 'contact', bn: 'যোগাযোগ', en: 'Contact' },
];

const CUSTOM = '__custom';

const defaults = (): NavItem[] =>
    (['header', 'footer'] as const).flatMap((location) => PAGES.map((page, index) => ({ label_bn: page.bn, label_en: page.en, route: page.route, url: null, visible: true, sort_order: index, location })));

const blank = (location: Location): NavItem => ({ label_bn: '', label_en: '', route: PAGES[0].route, url: null, visible: true, sort_order: 0, location });

/** Header and footer links of the public site, saved in the `nav_items` setting. Empty means the built-in menu. */
export function MenuEditor({ editable = true }: { editable?: boolean }) {
    const [items, setItems] = useState<NavItem[] | null>(null);
    const [saved, setSaved] = useState('');
    const [failed, setFailed] = useState(false);
    const [saving, setSaving] = useState(false);
    const [editing, setEditing] = useState<{ index: number | null; item: NavItem } | null>(null);
    const [deleting, setDeleting] = useState<number | null>(null);
    const [confirmReset, setConfirmReset] = useState(false);

    const adopt = (settings: Settings) => {
        const list = (settings.nav_items ?? []).map((item) => ({ ...item, label_en: item.label_en ?? '', route: item.route ?? null, url: item.url ?? null, visible: item.visible !== false }));
        setItems(list);
        setSaved(JSON.stringify(list));
    };

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: Settings }>('/core/settings')
            .then((response) => adopt(response.data))
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!items) {
        return <LoadingState rows={2} height={64} />;
    }

    const dirty = JSON.stringify(items) !== saved;

    /**
     * The settings endpoint validates the whole form, so the current settings are read again and written back with only
     * nav_items changed. Nothing else is touched.
     */
    const persist = async (next: NavItem[]) => {
        setSaving(true);

        try {
            const current = (await api.get<{ data: Settings }>('/core/settings')).data;
            const payload: Settings = { ...current, nav_items: next.map((item, index) => ({ ...item, sort_order: index })) };
            delete payload.logo_url;
            delete payload.principal_signature_url;

            const response = await api.put<{ data: Settings }>('/core/settings', payload);
            adopt(response.data);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        } finally {
            setSaving(false);
        }
    };

    const labelOf = (item: NavItem) => (locale() === 'en' && item.label_en ? item.label_en : item.label_bn);
    const targetOf = (item: NavItem) => (item.route ? ui(`menu.pages.${PAGES.find((page) => page.route === item.route)?.page ?? 'home'}`) : (item.url ?? ''));

    const section = (location: Location) => {
        const positions = items.map((item, index) => ({ item, index })).filter((entry) => entry.item.location === location);

        return (
            <ListCard>
                <ListCardHead
                    action={editable && (
                        <Button type="button" size="sm" onClick={() => setEditing({ index: null, item: blank(location) })}>
                            <PlusIcon className="size-4" />
                            {ui('add')}
                        </Button>
                    )}
                >
                    {k(location)}
                </ListCardHead>
                {positions.length === 0 && <p className="m-0 px-4 py-3 text-sm text-muted-foreground">{k('none')}</p>}
                {positions.map(({ item, index }, position) => (
                    <ListRow key={index} data-testid={`menu-${location}`}>
                        {editable && (
                            <ReorderButtons
                                index={position}
                                count={positions.length}
                                onMove={(step) => {
                                    const target = positions[position + step].index;
                                    setItems((all) => {
                                        const next = [...(all ?? [])];
                                        [next[index], next[target]] = [next[target], next[index]];

                                        return next;
                                    });
                                }}
                            />
                        )}
                        <span className="grid min-w-0 flex-1 leading-snug">
                            <strong className="truncate">{labelOf(item)}</strong>
                            <span className="truncate text-[13px] text-muted-foreground">{targetOf(item)}</span>
                        </span>
                        {!item.visible && <Pill tone="muted">{ui('hidden')}</Pill>}
                        {editable && (
                            <>
                                <Toggle checked={item.visible} label={ui('show_on_site')} onChange={(checked) => setItems((all) => all?.map((row, at) => (at === index ? { ...row, visible: checked } : row)) ?? null)} />
                                <IconButton aria-label={t('core::admin.common.edit')} onClick={() => setEditing({ index, item })}>
                                    <PencilIcon className="size-4" />
                                </IconButton>
                                <IconButton tone="danger" aria-label={t('core::admin.common.delete')} onClick={() => setDeleting(index)}>
                                    <Trash2Icon className="size-4" />
                                </IconButton>
                            </>
                        )}
                    </ListRow>
                ))}
            </ListCard>
        );
    };

    return (
        <fieldset disabled={!editable} className="m-0 grid min-w-0 gap-3 border-0 p-0">
            {items.length === 0 ? (
                <div className="grid justify-items-start gap-2 rounded-[20px] border border-line bg-surface p-[18px]">
                    <strong>{k('default_title')}</strong>
                    <p className="m-0 text-sm text-muted-foreground">{k('default_body')}</p>
                    {editable && <Button type="button" onClick={() => setItems(defaults())}>{k('customize')}</Button>}
                </div>
            ) : (
                <>
                    <p className="m-0 text-[13px] text-muted-foreground">{k('hint')}</p>
                    <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 420px), 1fr))' }}>
                        {section('header')}
                        {section('footer')}
                    </div>
                </>
            )}
            {editable && (items.length > 0 || dirty) && (
                <StickyBar className="items-center">
                    <Button type="button" variant="outline" className="mr-auto" onClick={() => setConfirmReset(true)} disabled={saving}>
                        {k('restore')}
                    </Button>
                    {dirty && <Pill tone="warn" size="md">{ui('unsaved_generic')}</Pill>}
                    <Button size="lg" onClick={() => void persist(items)} disabled={saving || !dirty}>
                        {saving ? t('core::admin.common.saving') : ui('save_changes')}
                    </Button>
                </StickyBar>
            )}

            {editing && (
                <MenuItemDialog
                    item={editing.item}
                    onClose={() => setEditing(null)}
                    onDone={(item) => {
                        setItems((all) => (editing.index === null ? [...(all ?? []), item] : (all?.map((row, at) => (at === editing.index ? item : row)) ?? null)));
                        setEditing(null);
                    }}
                />
            )}
            <ConfirmDialog open={deleting !== null} description={deleting !== null ? labelOf(items[deleting]) : undefined} onConfirm={() => { setItems((all) => all?.filter((_, at) => at !== deleting) ?? null); setDeleting(null); }} onClose={() => setDeleting(null)} />
            <ConfirmDialog
                open={confirmReset}
                title={k('restore_title')}
                description={k('restore_body')}
                confirmLabel={k('restore')}
                onConfirm={() => { setConfirmReset(false); void persist([]); }}
                onClose={() => setConfirmReset(false)}
            />
        </fieldset>
    );
}

function MenuItemDialog({ item, onClose, onDone }: { item: NavItem; onClose: () => void; onDone: (item: NavItem) => void }) {
    const [draft, setDraft] = useState<NavItem>(item);
    const [target, setTarget] = useState(item.route ?? CUSTOM);
    const custom = target === CUSTOM;
    const valid = draft.label_bn.trim() !== '' && (!custom || (draft.url ?? '').trim() !== '');

    const done = () => onDone({ ...draft, route: custom ? null : target, url: custom ? (draft.url ?? '').trim() : null });

    return (
        <Dialog open onOpenChange={(next) => !next && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{k('item')}</DialogTitle>
                </DialogHeader>
                <div className="grid gap-3">
                    <div className="grid gap-2.5 sm:grid-cols-2">
                        <Field label={`${ui('menu.label')} (${ui('bangla')})`} htmlFor="m-bn">
                            <Input id="m-bn" value={draft.label_bn} onChange={(event) => setDraft({ ...draft, label_bn: event.target.value })} />
                        </Field>
                        <Field label={`${ui('menu.label')} (${ui('english')})`} htmlFor="m-en">
                            <Input id="m-en" value={draft.label_en} onChange={(event) => setDraft({ ...draft, label_en: event.target.value })} />
                        </Field>
                    </div>
                    <Field label={k('goes_to')} htmlFor="m-target">
                        <NativeSelect id="m-target" value={target} onChange={(event) => setTarget(event.target.value)} options={[...PAGES.map((page) => ({ value: page.route, label: ui(`menu.pages.${page.page}`) })), { value: CUSTOM, label: k('custom_url') }]} />
                    </Field>
                    {custom && (
                        <Field label={k('url')} htmlFor="m-url" hint={k('url_hint')}>
                            <Input id="m-url" placeholder="https://" value={draft.url ?? ''} onChange={(event) => setDraft({ ...draft, url: event.target.value })} />
                        </Field>
                    )}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('core::admin.common.cancel')}
                        </Button>
                        <Button disabled={!valid} onClick={done}>
                            {t('core::admin.common.save')}
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
