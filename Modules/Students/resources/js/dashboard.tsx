import { GraduationCapIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { IdCardsPage } from './pages/IdCardsPage';
import { ImportPage } from './pages/ImportPage';
import { PromotePage } from './pages/PromotePage';
import { StudentFormPage } from './pages/StudentFormPage';
import { StudentProfilePage } from './pages/StudentProfilePage';
import { StudentsPage } from './pages/StudentsPage';

registerModule({
    id: 'students',
    menuFilter: (menu) => {
        if (!can('students.view')) {
            return menu;
        }

        const items = [
            { label: t('students::admin.menu'), icon: GraduationCapIcon, path: '/students' },
            can('students.manage') && { label: t('students::admin.menu_new'), icon: GraduationCapIcon, path: '/students/new' },
            can('students.import') && { label: t('students::admin.menu_import'), icon: GraduationCapIcon, path: '/students/import' },
            can('students.promote') && { label: t('students::admin.menu_promote'), icon: GraduationCapIcon, path: '/students/promote' },
            can('students.id_card') && { label: t('students::admin.menu_cards'), icon: GraduationCapIcon, path: '/students/id-cards' },
        ].filter((item): item is { label: string; icon: typeof GraduationCapIcon; path: string } => Boolean(item));

        return [...menu, { label: t('students::admin.menu_group'), items }];
    },
    routes: [
        { path: 'students', element: <StudentsPage /> },
        { path: 'students/new', element: <StudentFormPage /> },
        { path: 'students/import', element: <ImportPage /> },
        { path: 'students/promote', element: <PromotePage /> },
        { path: 'students/id-cards', element: <IdCardsPage /> },
        { path: 'students/:id', element: <StudentProfilePage /> },
        { path: 'students/:id/edit', element: <StudentFormPage /> },
    ],
});
