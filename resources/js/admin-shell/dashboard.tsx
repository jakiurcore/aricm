import { GaugeIcon } from 'lucide-react';

import { DashboardWidgets, registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { DashboardPage } from './DashboardPage';

// Replaces laramod-admin's own dashboard module (see adminShell() in vite.config.ts): the same registration, the design's page.
registerModule({
    id: 'dashboard',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('core::admin.dashboard.menu_group'),
            items: [{ label: t('core::admin.dashboard.menu'), icon: GaugeIcon, path: '/dashboard' }],
        },
    ],
    routes: [{ path: 'dashboard', element: <DashboardPage /> }],
});

export { DashboardWidgets };
