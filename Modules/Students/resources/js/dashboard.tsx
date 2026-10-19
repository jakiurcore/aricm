import { GraduationCapIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { StudentProfilePage } from './pages/StudentProfilePage';
import { StudentsPage } from './pages/StudentsPage';

registerModule({
    id: 'students',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('students::admin.menu_group'),
            items: [{ label: t('students::admin.menu'), icon: GraduationCapIcon, path: '/students' }],
        },
    ],
    routes: [
        { path: 'students', element: <StudentsPage /> },
        { path: 'students/:id', element: <StudentProfilePage /> },
    ],
});
