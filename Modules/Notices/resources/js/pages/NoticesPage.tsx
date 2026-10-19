import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { TablePagination } from '@core/js/components/DataTable';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { RichText } from '@core/js/components/RichText';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { IconButton, ListCard, ListRow, Panel, PanelTitle } from '@core/js/components/Surface';
import { usePaginated } from '@core/js/hooks/usePaginated';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Notice {
    id: number;
    title_bn: string;
    title_en: string | null;
    body_bn: string;
    body_en: string | null;
    category: string;
    attachment_url: string | null;
    attachment_name: string | null;
    is_pinned: boolean;
    show_in_ticker: boolean;
    is_published: boolean;
    publish_at: string | null;
    visible: boolean;
    created_at: string;
}

interface Draft {
    title_bn: string;
    title_en: string;
    body_bn: string;
    body_en: string;
    category: string;
    publish_at: string;
    is_pinned: boolean;
    show_in_ticker: boolean;
    is_published: boolean;
}

const k = (key: string, replacements?: Record<string, string | number>) => t(`notices::admin.form.${key}`, replacements);
const CATEGORIES = ['general', 'exam', 'admission', 'holiday', 'event', 'result'];
const categoryLabel = (value: string) => t(`notices::admin.categories.${value}`);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
const today = () => new Date().toISOString().slice(0, 10);
const emptyDraft = (): Draft => ({ title_bn: '', title_en: '', body_bn: '', body_en: '', category: 'general', publish_at: today(), is_pinned: false, show_in_ticker: true, is_published: true });
const hasText = (html: string) => html.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim() !== '';

/** A checkbox row as the design draws it: a 22px native box in green. */
function CheckRow({ checked, onChange, children }: { checked: boolean; onChange: (checked: boolean) => void; children: string }) {
    return (
        <label className="flex min-h-11 cursor-pointer items-center gap-2.5">
            <input type="checkbox" className="m-[3px_3px_3px_4px] size-[22px] min-h-0 accent-green-700" checked={checked} onChange={(event) => onChange(event.target.checked)} />
            {children}
        </label>
    );
}

/** The design's "নোটিশ": the notice list beside a publish card; the ✎ button loads a notice into that card. */
export function NoticesPage() {
    const manage = can('notices.manage');
    const { rows, meta, loading, failed, setPage, reload } = usePaginated<Notice>('/notices');
    const [editing, setEditing] = useState<Notice | null>(null);
    const [draft, setDraft] = useState<Draft>(emptyDraft);
    const [file, setFile] = useState<File | null>(null);
    const [removeFile, setRemoveFile] = useState(false);
    const [busy, setBusy] = useState(false);
    const [deleting, setDeleting] = useState<Notice | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const picker = useRef<HTMLInputElement>(null);
    const originalPublishAt = useRef<string | null>(null);

    const set = <K extends keyof Draft>(key: K, value: Draft[K]) => setDraft((current) => ({ ...current, [key]: value }));

    const reset = () => {
        setEditing(null);
        setDraft(emptyDraft());
        setFile(null);
        setRemoveFile(false);
        setErrors({});
        originalPublishAt.current = null;
    };

    useEffect(() => {
        if (picker.current && !file) {
            picker.current.value = '';
        }
    }, [file]);

    const edit = (notice: Notice) => {
        setEditing(notice);
        setFile(null);
        setRemoveFile(false);
        setErrors({});
        originalPublishAt.current = notice.publish_at;
        setDraft({
            title_bn: notice.title_bn, title_en: notice.title_en ?? '', body_bn: notice.body_bn, body_en: notice.body_en ?? '', category: notice.category,
            publish_at: notice.publish_at ? notice.publish_at.slice(0, 10) : notice.created_at.slice(0, 10), is_pinned: notice.is_pinned, show_in_ticker: notice.show_in_ticker, is_published: notice.is_published,
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const submit = async () => {
        if (!draft.title_bn.trim() || !hasText(draft.body_bn)) {
            setErrors({ ...(draft.title_bn.trim() ? {} : { title_bn: k('title_required') }), ...(hasText(draft.body_bn) ? {} : { body_bn: k('body_required') }) });

            return;
        }

        setBusy(true);
        setErrors({});
        const body = new FormData();
        const publishAt = originalPublishAt.current && originalPublishAt.current.slice(0, 10) === draft.publish_at ? originalPublishAt.current : draft.publish_at;

        Object.entries({ ...draft, publish_at: publishAt }).forEach(([key, value]) => body.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : value));

        if (file) {
            body.append('attachment', file);
        }

        body.append('remove_attachment', removeFile ? '1' : '0');

        try {
            if (editing) {
                body.append('_method', 'PUT');
                await api.post(`/notices/${editing.id}`, body);
            } else {
                await api.post('/notices', body);
            }

            toast.success(t('core::admin.common.saved'));
            reset();
            reload();
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(Object.fromEntries(Object.entries(error.errors).map(([field, messages]) => [field, messages[0]])));
            }

            toast.error(message(error));
        } finally {
            setBusy(false);
        }
    };

    const remove = async () => {
        if (!deleting) {
            return;
        }

        try {
            await api.delete(`/notices/${deleting.id}`);
            toast.success(t('core::admin.common.deleted'));

            if (editing?.id === deleting.id) {
                reset();
            }

            reload();
        } catch (error) {
            toast.error(message(error));
        } finally {
            setDeleting(null);
        }
    };

    const meta2 = (notice: Notice) =>
        [
            formatDate(notice.publish_at ?? notice.created_at),
            categoryLabel(notice.category),
            notice.is_pinned ? `📌 ${k('pinned_label')}` : '',
            notice.show_in_ticker ? k('ticker_label') : '',
            notice.attachment_url ? 'PDF' : '',
            !notice.is_published ? k('draft_label') : !notice.visible ? k('scheduled_label') : '',
        ]
            .filter(Boolean)
            .join(' · ');

    return (
        <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))' }}>
            <div className="grid gap-3">
                {failed && rows.length === 0 && <ErrorState onRetry={reload} />}
                {loading && rows.length === 0 && !failed && <LoadingState />}
                {!loading && !failed && rows.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('empty')}</div>}
                {rows.length > 0 && (
                    <ListCard>
                        {rows.map((notice) => (
                            <ListRow key={notice.id} className={`border-t-0 border-b border-line py-3 last:border-b-0 ${loading ? 'opacity-60' : ''} ${editing?.id === notice.id ? 'bg-green-50' : ''}`}>
                                <span className="grid min-w-0 flex-1 leading-[1.4]">
                                    <strong className="font-semibold">{notice.title_bn}</strong>
                                    <span className="text-[13px] text-muted-foreground">{meta2(notice)}</span>
                                </span>
                                {manage && (
                                    <>
                                        <IconButton aria-label={t('core::admin.common.edit')} title={t('core::admin.common.edit')} onClick={() => edit(notice)}>
                                            ✎
                                        </IconButton>
                                        <IconButton tone="danger" aria-label={t('core::admin.common.delete')} title={t('core::admin.common.delete')} onClick={() => setDeleting(notice)}>
                                            🗑
                                        </IconButton>
                                    </>
                                )}
                            </ListRow>
                        ))}
                    </ListCard>
                )}
                {meta && <TablePagination meta={meta} onPageChange={setPage} />}
            </div>

            {manage && (
                <Panel className="gap-3 shadow-md">
                    <PanelTitle>{editing ? k('edit_title') : `+ ${k('new_title')}`}</PanelTitle>
                    <Field label={k('title')} htmlFor="n-title" error={errors.title_bn}>
                        <Input id="n-title" value={draft.title_bn} onChange={(event) => set('title_bn', event.target.value)} />
                    </Field>
                    <div className="grid gap-1 text-sm font-semibold">
                        {k('body')}
                        <RichText value={draft.body_bn} onChange={(html) => set('body_bn', html)} label={k('body')} labels={{ bold: k('bold'), italic: k('italic'), list: k('list'), link: k('link'), linkPrompt: k('link_prompt') }} />
                        {errors.body_bn && <p className="text-[13px] font-medium text-danger">{errors.body_bn}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-2.5">
                        <Field label={k('category')} htmlFor="n-category" error={errors.category}>
                            <NativeSelect id="n-category" value={draft.category} onChange={(event) => set('category', event.target.value)} options={CATEGORIES.map((value) => ({ value, label: categoryLabel(value) }))} />
                        </Field>
                        <Field label={k('pub_date')} htmlFor="n-date" error={errors.publish_at}>
                            <Input id="n-date" type="date" value={draft.publish_at} onChange={(event) => set('publish_at', event.target.value)} />
                        </Field>
                    </div>
                    <button type="button" onClick={() => picker.current?.click()} className="h-11 cursor-pointer rounded-xl border border-dashed border-line-strong bg-surface-2 font-semibold">
                        {file ? k('attached', { name: file.name }) : editing?.attachment_name && !removeFile ? k('attached', { name: editing.attachment_name }) : `📎 ${k('attach')}`}
                    </button>
                    <input ref={picker} type="file" hidden accept=".pdf,image/png,image/jpeg" onChange={(event) => { setFile(event.target.files?.[0] ?? null); setRemoveFile(false); }} />
                    {errors.attachment && <p className="text-[13px] font-medium text-danger">{errors.attachment}</p>}
                    {editing?.attachment_url && !file && <CheckRow checked={removeFile} onChange={setRemoveFile}>{k('remove_attachment')}</CheckRow>}
                    <CheckRow checked={draft.is_pinned} onChange={(value) => set('is_pinned', value)}>{k('pin')}</CheckRow>
                    <CheckRow checked={draft.show_in_ticker} onChange={(value) => set('show_in_ticker', value)}>{k('ticker')}</CheckRow>
                    <CheckRow checked={draft.is_published} onChange={(value) => set('is_published', value)}>{k('published')}</CheckRow>
                    <details className="rounded-xl border border-line px-3 py-2">
                        <summary className="cursor-pointer font-semibold">{k('english')}</summary>
                        <div className="mt-3 grid gap-3">
                            <Field label={k('title_en')} htmlFor="n-title-en" error={errors.title_en}>
                                <Input id="n-title-en" value={draft.title_en} onChange={(event) => set('title_en', event.target.value)} />
                            </Field>
                            <div className="grid gap-1 text-sm font-semibold">
                                {k('body_en')}
                                <RichText value={draft.body_en} onChange={(html) => set('body_en', html)} minHeight={80} label={k('body_en')} labels={{ bold: k('bold'), italic: k('italic'), list: k('list'), link: k('link'), linkPrompt: k('link_prompt') }} />
                            </div>
                        </div>
                    </details>
                    <Button size="lg" disabled={busy} onClick={() => void submit()}>
                        {editing ? k('save') : k('publish')}
                    </Button>
                    {editing && (
                        <Button variant="outline" onClick={reset}>
                            {k('cancel_edit')}
                        </Button>
                    )}
                </Panel>
            )}

            <ConfirmDialog open={deleting !== null} description={`${deleting?.title_bn ?? ''} — ${k('confirm_delete')}`} onConfirm={() => void remove()} onClose={() => setDeleting(null)} />
        </div>
    );
}
