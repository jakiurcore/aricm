import { ImagesIcon, StarIcon, Trash2Icon, UploadIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router';
import { toast } from 'sonner';

import { registerModule } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { addToGroup } from '@core/js/menu';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';
import { bnDigits } from '@core/js/number';

interface Album {
    id: number;
    title_bn: string;
    title_en: string | null;
    description_bn: string | null;
    description_en: string | null;
    album_date: string | null;
    is_published: boolean;
    sort_order: number;
    photos_count: number;
    cover_url: string | null;
}

interface Photo {
    id: number;
    url: string;
    thumbnail_url: string;
    caption_bn: string | null;
    sort_order: number;
}

const k = (key: string) => t(`gallery::admin.${key}`);

function AlbumsPage() {
    return (
        <ResourcePage<Album>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/gallery/albums"
            searchable={false}
            hideCreate={!can('gallery.manage')}
            canEdit={() => can('gallery.manage')}
            canDelete={() => can('gallery.manage')}
            defaults={{ title_bn: '', title_en: '', description_bn: '', description_en: '', album_date: '', is_published: true, sort_order: 0 }}
            fields={[
                { name: 'title_bn', label: k('title_bn'), type: 'text' },
                { name: 'title_en', label: k('title_en'), type: 'text' },
                { name: 'description_bn', label: k('description_bn'), type: 'textarea', wide: true },
                { name: 'description_en', label: k('description_en'), type: 'textarea', wide: true },
                { name: 'album_date', label: k('album_date'), type: 'date' },
                { name: 'sort_order', label: k('sort_order'), type: 'number' },
                { name: 'is_published', label: k('is_published'), type: 'switch' },
            ]}
            toForm={(row) => ({ title_bn: row.title_bn, title_en: row.title_en ?? '', description_bn: row.description_bn ?? '', description_en: row.description_en ?? '', album_date: row.album_date ?? '', is_published: row.is_published, sort_order: row.sort_order })}
            columns={[
                { header: '', id: 'cover', cell: ({ row }) => (row.original.cover_url ? <img src={row.original.cover_url} alt="" className="size-12 rounded-md object-cover" /> : <div className="size-12 rounded-md bg-muted" />) },
                { header: k('title_bn'), cell: ({ row }) => <span className="font-medium">{row.original.title_bn}</span> },
                { header: k('photos'), cell: ({ row }) => bnDigits(row.original.photos_count) },
                { header: '', id: 'state', cell: ({ row }) => <Badge variant={row.original.is_published ? 'default' : 'outline'}>{row.original.is_published ? k('is_published') : '—'}</Badge> },
            ]}
            rowActions={(row) => (
                <Button variant="ghost" size="icon" aria-label={k('manage')} title={k('manage')} render={<Link to={`/gallery/${row.id}`} />}>
                    <ImagesIcon />
                </Button>
            )}
        />
    );
}

function AlbumPhotosPage() {
    const { id } = useParams();
    const [album, setAlbum] = useState<(Album & { photos: Photo[] }) | null>(null);
    const [uploading, setUploading] = useState(false);
    const dragged = useRef<number | null>(null);
    const manage = can('gallery.manage');

    const load = useCallback(async () => setAlbum((await api.get<{ data: Album & { photos: Photo[] } }>(`/gallery/albums/${id}`)).data), [id]);

    useEffect(() => {
        void load();
    }, [load]);

    if (!album) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const upload = async (files: FileList | null) => {
        if (!files || files.length === 0) {
            return;
        }

        setUploading(true);
        const form = new FormData();
        Array.from(files).forEach((file) => form.append('photos[]', file));

        try {
            await api.post(`/gallery/albums/${id}/photos`, form);
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        } finally {
            setUploading(false);
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

    const remove = async (photo: Photo) => {
        await api.delete(`/gallery/photos/${photo.id}`);
        await load();
    };

    const setCover = async (photo: Photo) => {
        await api.put(`/gallery/albums/${id}/cover/${photo.id}`);
        await load();
    };

    return (
        <div className="max-w-5xl space-y-4">
            <Link to="/gallery" className="text-sm text-muted-foreground underline">← {k('back')}</Link>
            <h1 className="text-2xl font-semibold tracking-tight">{album.title_bn}</h1>

            {manage && (
                <div className="flex flex-wrap items-center gap-3 rounded-xl border bg-card p-4">
                    <UploadIcon className="size-5 text-muted-foreground" />
                    <Input type="file" multiple accept="image/png,image/jpeg,image/webp" className="max-w-sm" disabled={uploading} onChange={(event) => { void upload(event.target.files); event.target.value = ''; }} aria-label={k('upload')} />
                    <span className="text-sm text-muted-foreground">{uploading ? k('uploading') : k('drag_hint')}</span>
                </div>
            )}

            {album.photos.length === 0 && <p className="text-muted-foreground">{k('empty')}</p>}

            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                {album.photos.map((photo) => (
                    <div key={photo.id} draggable={manage} onDragStart={() => (dragged.current = photo.id)} onDragOver={(event) => event.preventDefault()} onDrop={() => void drop(photo.id)} className="group relative overflow-hidden rounded-lg border bg-card">
                        <img src={photo.thumbnail_url} alt="" className="aspect-square w-full object-cover" />
                        {album.cover_url === photo.thumbnail_url && <Badge className="absolute left-2 top-2">{k('is_cover')}</Badge>}
                        {manage && (
                            <div className="absolute inset-x-0 bottom-0 flex justify-between bg-black/60 p-1 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                                <Button variant="ghost" size="icon-sm" className="text-white" aria-label={k('cover')} title={k('cover')} onClick={() => void setCover(photo)}>
                                    <StarIcon />
                                </Button>
                                <Button variant="ghost" size="icon-sm" className="text-white" aria-label={t('core::admin.common.delete')} onClick={() => void remove(photo)}>
                                    <Trash2Icon />
                                </Button>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

registerModule({
    id: 'gallery',
    menuFilter: (menu) => (can('gallery.view') ? addToGroup(menu, t('core::admin.content_group'), [{ label: k('menu'), icon: ImagesIcon, path: '/gallery' }]) : menu),
    routes: [
        { path: 'gallery', element: <AlbumsPage /> },
        { path: 'gallery/:id', element: <AlbumPhotosPage /> },
    ],
});
