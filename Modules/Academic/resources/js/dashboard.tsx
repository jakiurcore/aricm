import { BookOpenIcon, CalendarRangeIcon, LayersIcon, ListChecksIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { ClassesPage } from './pages/ClassesPage';
import { MappingPage } from './pages/MappingPage';
import { SessionsPage } from './pages/SessionsPage';
import { SubjectsPage } from './pages/SubjectsPage';

registerModule({
    id: 'academic',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('academic::admin.menu_group'),
            items: [
                { label: t('academic::admin.sessions.menu'), icon: CalendarRangeIcon, path: '/academic/sessions' },
                { label: t('academic::admin.classes.menu'), icon: LayersIcon, path: '/academic/classes' },
                { label: t('academic::admin.subjects.menu'), icon: BookOpenIcon, path: '/academic/subjects' },
                { label: t('academic::admin.mapping.menu'), icon: ListChecksIcon, path: '/academic/mapping' },
            ],
        },
    ],
    routes: [
        { path: 'academic/sessions', element: <SessionsPage /> },
        { path: 'academic/classes', element: <ClassesPage /> },
        { path: 'academic/subjects', element: <SubjectsPage /> },
        { path: 'academic/mapping', element: <MappingPage /> },
    ],
});
