import { ShieldIcon, UsersIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { RolesPage } from './pages/RolesPage';
import { UsersPage } from './pages/UsersPage';

registerModule({
    id: 'accounts',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('accounts::admin.menu_group'),
            items: [
                { label: t('accounts::admin.users.menu'), icon: UsersIcon, path: '/users' },
                { label: t('accounts::admin.roles.menu'), icon: ShieldIcon, path: '/roles' },
            ],
        },
    ],
    routes: [
        { path: 'users', element: <UsersPage /> },
        { path: 'roles', element: <RolesPage /> },
    ],
});
