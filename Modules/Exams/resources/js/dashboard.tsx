import { ClipboardListIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { ExamDetailPage } from './pages/ExamDetailPage';
import { ExamsPage } from './pages/ExamsPage';

registerModule({
    id: 'exams',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('exams::admin.menu_group'),
            items: [{ label: t('exams::admin.menu'), icon: ClipboardListIcon, path: '/exams' }],
        },
    ],
    routes: [
        { path: 'exams', element: <ExamsPage /> },
        { path: 'exams/:id', element: <ExamDetailPage /> },
    ],
});
