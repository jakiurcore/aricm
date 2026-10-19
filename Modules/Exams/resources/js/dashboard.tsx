import { ClipboardListIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { ExamDetailPage } from './pages/ExamDetailPage';
import { ExamsPage } from './pages/ExamsPage';

registerModule({
    id: 'exams',
    menuFilter: (menu) =>
        can('exams.view') ? [...menu, { label: t('exams::admin.menu_group'), items: [{ label: t('exams::admin.menu'), icon: ClipboardListIcon, path: '/exams' }] }] : menu,
    routes: [
        { path: 'exams', element: <ExamsPage /> },
        { path: 'exams/:id', element: <ExamDetailPage /> },
    ],
});
