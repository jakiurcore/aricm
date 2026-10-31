import { GlobeIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { can } from '@core/js/permissions';

import { WebsitePage } from './pages/WebsitePage';

registerModule({
    id: 'website',
    menuFilter: (menu) => (can('website.view') ? addToGroup(menu, t('core::admin.content_group'), [{ label: t('website::admin.ui.menu_label'), icon: GlobeIcon, path: '/website' }]) : menu),
    routes: [{ path: 'website', element: <WebsitePage /> }],
});
