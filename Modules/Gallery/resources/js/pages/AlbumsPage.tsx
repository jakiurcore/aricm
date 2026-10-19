import { ImageIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';

import { HeaderActions } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Dropzone } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

import type { Album } from '../types';
import { AlbumFormDialog } from './AlbumFormDialog';

const k = (key: string, replacements?: Record<string, string | number>) => t(`gallery::admin.${key}`, replacements);

/** The design's "গ্যালারি": a drop area with a progress bar, then the album cards. */
export function AlbumsPage() {
    const manage = can('gallery.manage');
    const input = useRef<HTMLInputElement>(null);
    const [albums, setAlbums] = useState<Album[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [target, setTarget] = useState('');
    const [progress, setProgress] = useState(0);
    const [dragging, setDragging] = useState(false);
    const [formOpen, setFormOpen] = useState(false);

    const load = useCallback(() => {
        setFailed(false);
        api.get<{ data: Album[] }>('/gallery/albums')
            .then((response) => setAlbums(response.data))
            .catch(() => setFailed(true));
    }, []);

    useEffect(load, [load]);

    useEffect(() => {
        if (!target && albums && albums.length > 0) {
            setTarget(String(albums[0].id));
        }
    }, [albums, target]);

    const upload = async (files: FileList | File[]) => {
        const list = Array.from(files);

        if (list.length === 0 || !target) {
            return;
        }

        let done = 0;
        setProgress(5);

        try {
            // One request per photo, so the bar moves with every file that lands.
            for (const file of list) {
                const form = new FormData();
                form.append('photos[]', file);
                await api.post(`/gallery/albums/${target}/photos`, form);
                done++;
                setProgress(Math.max(5, Math.round((done * 100) / list.length)));
            }

            toast.success(k('page.uploaded', { count: bnDigits(done) }));
            load();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        } finally {
            setProgress(0);
        }
    };

    return (
        <div className="grid gap-3.5">
            {manage && (
                <HeaderActions.Fill>
                    <Button onClick={() => setFormOpen(true)}>+ {k('new')}</Button>
                </HeaderActions.Fill>
            )}

            {manage && (
                <Dropzone
                    className="p-6"
                    style={dragging ? { borderColor: 'var(--green-700)' } : undefined}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={(event) => {
                        event.preventDefault();
                        setDragging(false);
                        void upload(event.dataTransfer.files);
                    }}
                >
                    <span className="text-[30px]" aria-hidden="true">
                        🖼
                    </span>
                    <strong>{k('page.drop')}</strong>
                    <div className="grid w-[min(100%,360px)] gap-2">
                        {albums && albums.length > 1 && <NativeSelect aria-label={k('page.into')} value={target} onChange={(event) => setTarget(event.target.value)} options={albums.map((album) => ({ value: album.id, label: album.title_bn }))} />}
                        {progress > 0 && (
                            <div className="h-2.5 overflow-hidden rounded-full bg-surface" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}>
                                <div className="h-full bg-green-500 transition-[width]" style={{ width: `${progress}%` }} />
                            </div>
                        )}
                    </div>
                    <Button disabled={!target || progress > 0} onClick={() => input.current?.click()}>
                        {k('page.choose')}
                    </Button>
                    <input ref={input} type="file" hidden multiple accept="image/png,image/jpeg,image/webp" onChange={(event) => { void upload(event.target.files ?? []); event.target.value = ''; }} />
                </Dropzone>
            )}

            {failed && <ErrorState onRetry={load} />}
            {!failed && albums === null && <LoadingState />}
            {albums?.length === 0 && (
                <div className="grid justify-items-center gap-2 rounded-[20px] border-2 border-dashed border-input px-5 py-12 text-center">
                    <span className="text-[32px]" aria-hidden="true">
                        📭
                    </span>
                    <strong className="text-lg">{k('page.empty')}</strong>
                    <span className="text-muted-foreground">{k('page.empty_body')}</span>
                </div>
            )}

            {albums && albums.length > 0 && (
                <div className="grid gap-3" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(min(100%, 220px), 1fr))' }}>
                    {albums.map((album) => (
                        <Link key={album.id} to={`/gallery/${album.id}`} className="overflow-hidden rounded-2xl border border-line bg-surface text-foreground no-underline transition-shadow hover:shadow-md">
                            <div className="grid aspect-[4/3] place-items-center bg-surface-2 text-muted-foreground">
                                {album.cover_url ? <img src={album.cover_url} alt="" className="size-full object-cover" /> : <ImageIcon className="size-7" aria-hidden="true" />}
                            </div>
                            <div className="grid px-3 py-2.5 leading-[1.4]">
                                <strong className="text-[15px]">{album.title_bn}</strong>
                                <span className="text-[13px] text-muted-foreground">
                                    {k('page.photos_n', { count: bnDigits(album.photos_count) })}
                                    {!album.is_published && (
                                        <>
                                            {' · '}
                                            <Badge variant="secondary" className="text-xs">
                                                {k('page.unpublished')}
                                            </Badge>
                                        </>
                                    )}
                                </span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            <AlbumFormDialog album={null} open={formOpen} onClose={() => setFormOpen(false)} onSaved={() => { setFormOpen(false); load(); }} />
        </div>
    );
}
