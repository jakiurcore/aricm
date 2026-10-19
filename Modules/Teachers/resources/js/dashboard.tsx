import { UserRoundCheckIcon, UsersRoundIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { addToGroup } from '@core/js/menu';
import { can } from '@core/js/permissions';

import { AssignmentsPage } from './pages/AssignmentsPage';
import { TeachersPage } from './pages/TeachersPage';

const k = (key: string) => t(`teachers::admin.${key}`);

registerModule({
    id: 'teachers',
    menuFilter: (menu) =>
        can('teachers.manage') || can('teacher_assignments.manage')
            ? addToGroup(menu, k('group'), [
                  { label: k('list.title'), icon: UsersRoundIcon, path: '/teachers' },
                  { label: k('assignments.menu'), icon: UserRoundCheckIcon, path: '/teachers/assignments' },
              ])
            : menu,
    routes: [
        { path: 'teachers', element: <TeachersPage /> },
        { path: 'teachers/assignments', element: <AssignmentsPage /> },
    ],
});
