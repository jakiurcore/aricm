import { AwardIcon, BarChart3Icon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { t } from '@core/js/i18n';

import { GradingScalesPage } from './pages/GradingScalesPage';
import { MarksEntryPage } from './pages/MarksEntryPage';
import { PreviewPage } from './pages/PreviewPage';
import { ResultsPage } from './pages/ResultsPage';

registerModule({
    id: 'results',
    menuFilter: (menu) => [
        ...menu,
        {
            label: t('results::admin.menu_group'),
            items: [
                { label: t('results::admin.overview.menu'), icon: BarChart3Icon, path: '/results' },
                { label: t('results::admin.scales.menu'), icon: AwardIcon, path: '/results-grading' },
            ],
        },
    ],
    routes: [
        { path: 'results', element: <ResultsPage /> },
        { path: 'results/marks/:examId/:classId', element: <MarksEntryPage /> },
        { path: 'results/preview/:examId/:classId', element: <PreviewPage /> },
        { path: 'results-grading', element: <GradingScalesPage /> },
    ],
});
