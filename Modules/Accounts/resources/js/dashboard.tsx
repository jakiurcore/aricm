import { ShieldIcon, UsersIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { RolesPage } from './pages/RolesPage';
import { UsersPage } from './pages/UsersPage';

registerModule({
    id: 'accounts',
    menuFilter: (menu) => {
        const items = [
            can('users.manage') && { label: t('accounts::admin.users.menu'), icon: UsersIcon, path: '/users' },
            (can('roles.manage') || can('users.manage')) && { label: t('accounts::admin.roles.menu'), icon: ShieldIcon, path: '/roles' },
        ].filter((item): item is { label: string; icon: typeof UsersIcon; path: string } => Boolean(item));

        return items.length ? [...menu, { label: t('accounts::admin.menu_group'), items }] : menu;
    },
    routes: [
        { path: 'users', element: <UsersPage /> },
        { path: 'roles', element: <RolesPage /> },
    ],
});
