import { BookOpenCheckIcon, MessageSquareTextIcon, SettingsIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { can } from '@core/js/permissions';

import { MonitorPage } from './pages/MonitorPage';
import { PhrasesPage } from './pages/PhrasesPage';
import { SettingsPage } from './pages/SettingsPage';

const k = (key: string) => t(`diary::admin.${key}`);

registerModule({
    id: 'diary',
    menuFilter: (menu) =>
        can('diary.monitor') || can('diary.manage')
            ? addToGroup(menu, k('group'), [
                  { label: k('monitor.menu'), icon: BookOpenCheckIcon, path: '/diary' },
                  { label: k('phrases.menu'), icon: MessageSquareTextIcon, path: '/diary/phrases' },
                  { label: k('settings.menu'), icon: SettingsIcon, path: '/diary/settings' },
              ])
            : menu,
    routes: [
        { path: 'diary', element: <MonitorPage /> },
        { path: 'diary/phrases', element: <PhrasesPage /> },
        { path: 'diary/settings', element: <SettingsPage /> },
    ],
});
