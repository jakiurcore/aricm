import { NavLink } from 'react-router';

import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

const TABS = ['sessions', 'classes', 'sections', 'subjects', 'mapping', 'calendar'] as const;

/** The design's tab strip: 44px pills that scroll sideways on a phone, the open tab solid green. */
export function AcademicTabs() {
    if (!can('academic.view')) {
        return null;
    }

    return (
        <nav className="flex gap-1.5 overflow-x-auto" aria-label={t('academic::admin.setup')}>
            {TABS.map((tab) => (
                <NavLink
                    key={tab}
                    to={`/academic/${tab}`}
                    className={({ isActive }) =>
                        `inline-flex h-11 flex-none items-center rounded-full border px-4 font-semibold whitespace-nowrap no-underline transition-colors ${isActive ? 'border-primary bg-primary text-primary-foreground' : 'border-line-strong bg-surface text-foreground hover:bg-surface-2'}`
                    }
                >
                    {t(`academic::admin.tabs.${tab}`)}
                </NavLink>
            ))}
        </nav>
    );
}
