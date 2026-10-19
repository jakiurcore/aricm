import { AwardIcon, BarChart3Icon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { registerExamTab } from '@exams/js/tabs';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { MarksGrid } from './components/MarksGrid';
import { ResultPanel } from './components/ResultPanel';
import { GradingScalesPage } from './pages/GradingScalesPage';
import { MarksEntryPage } from './pages/MarksEntryPage';
import { PreviewPage } from './pages/PreviewPage';
import { ResultsPage } from './pages/ResultsPage';

// The "marks entry" and "result" tabs of the exam page: Exams owns the page and the registry, Results only plugs in.
registerExamTab({
    id: 'marks',
    label: () => t('results::admin.tabs.marks'),
    order: 50,
    classPick: true,
    render: ({ exam, classId }) => (can('results.enter_marks') && classId ? <MarksGrid key={`${exam.id}-${classId}`} examId={exam.id} classId={classId} /> : null),
});

registerExamTab({
    id: 'result',
    label: () => t('results::admin.tabs.result'),
    order: 60,
    classPick: true,
    render: ({ exam, classId, reload }) => (can('results.view') && classId ? <ResultPanel key={`${exam.id}-${classId}`} examId={exam.id} classId={classId} onChanged={reload} /> : null),
});

registerModule({
    id: 'results',
    menuFilter: (menu) =>
        can('results.view')
            ? [
                  ...menu,
                  {
                      label: t('results::admin.menu_group'),
                      items: [
                          { label: t('results::admin.overview.menu'), icon: BarChart3Icon, path: '/results' },
                          { label: t('results::admin.scales.menu'), icon: AwardIcon, path: '/results-grading' },
                      ],
                  },
              ]
            : menu,
    routes: [
        { path: 'results', element: <ResultsPage /> },
        { path: 'results/marks/:examId/:classId', element: <MarksEntryPage /> },
        { path: 'results/preview/:examId/:classId', element: <PreviewPage /> },
        { path: 'results-grading', element: <GradingScalesPage /> },
    ],
});
