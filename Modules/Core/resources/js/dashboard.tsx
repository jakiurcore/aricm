import { GaugeIcon, SettingsIcon } from 'lucide-react';

import { registerModule } from '@admin/js';

import { Toaster } from './components/Toaster';
import { t } from './i18n';
import { can } from './permissions';
import { SettingsPage } from './pages/SettingsPage';

registerModule({
    id: 'core',
    menuFilter: (menu) =>
        can('settings.manage')
            ? [...menu, { label: t('core::admin.settings.menu_group'), items: [{ label: t('core::admin.settings.menu'), icon: SettingsIcon, path: '/settings' }] }]
            : menu,
    routes: [{ path: 'settings', element: <SettingsPage /> }],
    // Kept mounted for every admin page so any module can call toast().
    fills: <Toaster />,
});

// The admin's own Dashboard module registers after every dashboard script, with English labels and last in the menu.
// This one runs after it (negative order) to translate those labels and put the dashboard first.
registerModule({
    id: 'core-chrome',
    order: -1,
    menuFilter: (menu) => {
        const translated = menu.map((group) => ({
            ...group,
            label: group.items.some((item) => item.path === '/dashboard') ? t('core::admin.dashboard.menu_group') : group.label,
            items: group.items.map((item) => (item.path === '/dashboard' ? { ...item, label: t('core::admin.dashboard.menu'), icon: GaugeIcon } : item)),
        }));

        return [...translated.filter((group) => group.items.some((item) => item.path === '/dashboard')), ...translated.filter((group) => !group.items.some((item) => item.path === '/dashboard'))];
    },
});
