import { SchoolIcon } from 'lucide-react';

import { Navigate } from 'react-router';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { CalendarPage } from './pages/CalendarPage';
import { ClassesPage } from './pages/ClassesPage';
import { SectionsPage } from './pages/SectionsPage';
import { MappingPage } from './pages/MappingPage';
import { SessionsPage } from './pages/SessionsPage';
import { SubjectsPage } from './pages/SubjectsPage';

registerModule({
    id: 'academic',
    menuFilter: (menu) => !can('academic.view') ? menu : [
        ...menu,
        {
            label: t('academic::admin.menu_group'),
            items: [{ label: t('academic::admin.setup'), icon: SchoolIcon, path: '/academic' }],
        },
    ],
    routes: [
        { path: 'academic', element: <Navigate to="/academic/sessions" replace /> },
        { path: 'academic/sessions', element: <SessionsPage /> },
        { path: 'academic/sections', element: <SectionsPage /> },
        { path: 'academic/classes', element: <ClassesPage /> },
        { path: 'academic/subjects', element: <SubjectsPage /> },
        { path: 'academic/mapping', element: <MappingPage /> },
        { path: 'academic/calendar', element: <CalendarPage /> },
    ],
});
