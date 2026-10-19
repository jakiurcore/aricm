import { MegaphoneIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { can } from '@core/js/permissions';

import { NoticesPage } from './pages/NoticesPage';

registerModule({
    id: 'notices',
    menuFilter: (menu) => (can('notices.view') ? addToGroup(menu, t('core::admin.content_group'), [{ label: t('notices::admin.menu'), icon: MegaphoneIcon, path: '/notices' }]) : menu),
    routes: [{ path: 'notices', element: <NoticesPage /> }],
});
