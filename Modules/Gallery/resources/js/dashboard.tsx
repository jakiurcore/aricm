import { ImagesIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { can } from '@core/js/permissions';

import { AlbumPhotosPage } from './pages/AlbumPhotosPage';
import { AlbumsPage } from './pages/AlbumsPage';

registerModule({
    id: 'gallery',
    menuFilter: (menu) => (can('gallery.view') ? addToGroup(menu, t('core::admin.content_group'), [{ label: t('gallery::admin.menu'), icon: ImagesIcon, path: '/gallery' }]) : menu),
    routes: [
        { path: 'gallery', element: <AlbumsPage /> },
        { path: 'gallery/:id', element: <AlbumPhotosPage /> },
    ],
});
