import { BadgeCheckIcon, BarChart3Icon, CoinsIcon, FileTextIcon, ListChecksIcon, PercentIcon, SettingsIcon, TableIcon, WalletIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { registerExamTab } from '@exams/js/tabs';
import { canAny, can } from '@core/js/permissions';
import { t } from '@core/js/i18n';
import { registerMenuBadge } from '@core/js/menu';

import { ExamFeesTab } from './components/ExamFeesTab';
import { VerificationBadge } from './components/VerificationBadge';
import { CollectionPage } from './pages/CollectionPage';
import { ConcessionsPage } from './pages/ConcessionsPage';
import { HeadsPage } from './pages/HeadsPage';
import { InvoicesPage } from './pages/InvoicesPage';
import { ReportsPage } from './pages/ReportsPage';
import { SettingsPage } from './pages/SettingsPage';
import { StructurePage } from './pages/StructurePage';
import { VerificationPage } from './pages/VerificationPage';

// The "fee" tab of the exam page: Exams owns the page and the registry, Fees only plugs in.
registerExamTab({
    id: 'fees',
    label: () => t('fees::admin.exam_fees.tab'),
    order: 30,
    render: ({ exam }) => (can('fees.view') ? <ExamFeesTab examId={exam.id} /> : null),
});

// The yellow count on "verify payments" in the sidebar, as in the design.
registerMenuBadge('/fees/verification', VerificationBadge);

registerModule({
    id: 'fees',
    menuFilter: (menu) => {
        const items = [
            can('fees.collect') && { label: t('fees::admin.collection.menu'), icon: WalletIcon, path: '/fees/collection' },
            can('fees.verify') && { label: t('fees::admin.verification.menu'), icon: BadgeCheckIcon, path: '/fees/verification' },
            can('fees.view') && { label: t('fees::admin.invoices.menu'), icon: FileTextIcon, path: '/fees/invoices' },
            can('fees.view') && { label: t('fees::admin.heads.menu'), icon: ListChecksIcon, path: '/fees/heads' },
            can('fees.view') && { label: t('fees::admin.structure.menu'), icon: TableIcon, path: '/fees/structure' },
            can('fees.view') && { label: t('fees::admin.concessions.menu'), icon: PercentIcon, path: '/fees/concessions' },
            can('fees.reports') && { label: t('fees::admin.reports.menu'), icon: BarChart3Icon, path: '/fees/reports' },
            canAny('fees.settings', 'fees.view') && { label: t('fees::admin.settings.menu'), icon: SettingsIcon, path: '/fees/settings' },
        ].filter((item): item is { label: string; icon: typeof CoinsIcon; path: string } => Boolean(item));

        return items.length ? [...menu, { label: t('fees::admin.menu_group'), items }] : menu;
    },
    routes: [
        { path: 'fees/collection', element: <CollectionPage /> },
        { path: 'fees/verification', element: <VerificationPage /> },
        { path: 'fees/invoices', element: <InvoicesPage /> },
        { path: 'fees/heads', element: <HeadsPage /> },
        { path: 'fees/structure', element: <StructurePage /> },
        { path: 'fees/concessions', element: <ConcessionsPage /> },
        { path: 'fees/reports', element: <ReportsPage /> },
        { path: 'fees/settings', element: <SettingsPage /> },
    ],
});
