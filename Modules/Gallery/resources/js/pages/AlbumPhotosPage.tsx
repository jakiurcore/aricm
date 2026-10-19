import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';

import { usePageTitle } from '@/admin-shell/pageTitle';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Dropzone } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

import type { Album, Photo } from '../types';
import { AlbumFormDialog } from './AlbumFormDialog';

const k = (key: string, replacements?: Record<string, string | number>) => t(`gallery::admin.${key}`, replacements);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

type Detail = Album & { photos: Photo[] };

/** One album: its photos as cards (drag to reorder, star for cover, ✕ to remove), an upload area, edit and delete. */
export function AlbumPhotosPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const manage = can('gallery.manage');
    const input = useRef<HTMLInputElement>(null);
    const dragged = useRef<number | null>(null);
    const [album, setAlbum] = useState<Detail | null>(null);
    const [failed, setFailed] = useState(false);
    const [progress, setProgress] = useState(0);
    const [formOpen, setFormOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);

    usePageTitle(album?.title_bn ?? k('title'));

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: Detail }>(`/gallery/albums/${id}`)
            .then((response) => setAlbum(response.data))
            .catch(() => setFailed(true));
    }, [id]);

    useEffect(load, [load]);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!album) {
        return <LoadingState />;
    }

    const upload = async (files: FileList | File[]) => {
        const list = Array.from(files);

        if (list.length === 0) {
            return;
        }

        setProgress(5);
        let done = 0;

        try {
            for (const file of list) {
                const form = new FormData();
                form.append('photos[]', file);
                await api.post(`/gallery/albums/${id}/photos`, form);
                done++;
                setProgress(Math.max(5, Math.round((done * 100) / list.length)));
            }

            toast.success(k('page.uploaded', { count: bnDigits(done) }));
        } catch (error) {
            toast.error(message(error));
        } finally {
            setProgress(0);
            load();
        }
    };

    const drop = async (targetId: number) => {
        const from = dragged.current;
        dragged.current = null;

        if (from === null || from === targetId) {
            return;
        }

        const ids = album.photos.map((photo) => photo.id).filter((photoId) => photoId !== from);
        ids.splice(ids.indexOf(targetId), 0, from);
        setAlbum({ ...album, photos: ids.map((photoId) => album.photos.find((photo) => photo.id === photoId)!) });
        await api.put(`/gallery/albums/${id}/photos/order`, { ids });
    };

    const removeAlbum = async () => {
        try {
            await api.delete(`/gallery/albums/${id}`);
            toast.success(t('core::admin.common.deleted'));
            navigate('/gallery');
        } catch (error) {
            toast.error(message(error));
            setDeleting(false);
        }
    };

    return (
        <div className="grid gap-3.5">
            <div className="flex flex-wrap items-center gap-2">
                <Link to="/gallery" className="font-semibold text-green-700 no-underline">
                    ← {k('back')}
                </Link>
                <span className="flex-1" />
                {manage && (
                    <>
                        <Button variant="secondary" onClick={() => setFormOpen(true)}>
                            {k('page.edit_album')}
                        </Button>
                        <Button variant="outline" className="border-danger text-danger" onClick={() => setDeleting(true)}>
                            {k('page.delete_album')}
                        </Button>
                    </>
                )}
            </div>

            {manage && (
                <Dropzone className="p-6" onDragOver={(event) => event.preventDefault()} onDrop={(event) => { event.preventDefault(); void upload(event.dataTransfer.files); }}>
                    <span className="text-[30px]" aria-hidden="true">
                        🖼
                    </span>
                    <strong>{k('page.drop')}</strong>
                    <span className="text-sm text-muted-foreground">{k('drag_hint')}</span>
                    {progress > 0 && (
                        <div className="h-2.5 w-[min(100%,360px)] overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}>
                            <div className="h-full bg-green-500 transition-[width]" style={{ width: `${progress}%` }} />
                        </div>
                    )}
                    <Button disabled={progress > 0} onClick={() => input.current?.click()}>
                        {k('page.choose')}
                    </Button>
                    <input ref={input} type="file" hidden multiple accept="image/png,image/jpeg,image/webp" onChange={(event) => { void upload(event.target.files ?? []); event.target.value = ''; }} />
                </Dropzone>
            )}

            {album.photos.length === 0 && <div className="rounded-2xl border-2 border-dashed border-input p-9 text-center text-muted-foreground">{k('empty')}</div>}

            <div className="grid gap-3" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 200px), 1fr))' }}>
                {album.photos.map((photo) => (
                    <div key={photo.id} draggable={manage} onDragStart={() => (dragged.current = photo.id)} onDragOver={(event) => event.preventDefault()} onDrop={() => void drop(photo.id)} className="group relative overflow-hidden rounded-2xl border border-line bg-surface">
                        <img src={photo.thumbnail_url} alt={photo.caption_bn ?? ''} className="aspect-[4/3] w-full object-cover" />
                        {album.cover_url === photo.thumbnail_url && <Badge className="absolute top-2 left-2">{k('is_cover')}</Badge>}
                        {manage && (
                            <div className="absolute inset-x-0 bottom-0 flex justify-between bg-black/60 p-1 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                                <button type="button" className="h-9 cursor-pointer rounded-lg border-0 bg-transparent px-2.5 text-[15px] text-white" aria-label={k('cover')} title={k('cover')} onClick={() => void api.put(`/gallery/albums/${id}/cover/${photo.id}`).then(load)}>
                                    ★ {k('page.make_cover')}
                                </button>
                                <button type="button" className="size-9 cursor-pointer rounded-lg border-0 bg-transparent text-white" aria-label={k('page.remove_photo')} title={k('page.remove_photo')} onClick={() => void api.delete(`/gallery/photos/${photo.id}`).then(load)}>
                                    🗑
                                </button>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            <AlbumFormDialog album={album} open={formOpen} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); load(); }} />
            <ConfirmDialog open={deleting} description={`${album.title_bn} — ${k('page.confirm_delete')}`} onConfirm={() => void removeAlbum()} onClose={() => setDeleting(false)} />
        </div>
    );
}
