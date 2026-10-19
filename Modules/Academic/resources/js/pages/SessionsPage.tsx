import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { bnDigits, formatDate } from '@core/js/number';

import { AcademicTabs } from '../components/AcademicTabs';

interface Session {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
}

const k = (key: string) => t(`academic::admin.sessions.${key}`);

export function SessionsPage() {
    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            <ResourcePage<Session>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/academic/sessions"
            searchable={false}
            defaults={{ name: '', start_date: '', end_date: '', is_current: false }}
            fields={[
                { name: 'name', label: k('name'), type: 'text', wide: true },
                { name: 'start_date', label: k('start_date'), type: 'date' },
                { name: 'end_date', label: k('end_date'), type: 'date' },
                { name: 'is_current', label: k('is_current'), type: 'switch' },
            ]}
            toForm={(row) => ({
                name: row.name,
                start_date: row.start_date?.slice(0, 10) ?? '',
                end_date: row.end_date?.slice(0, 10) ?? '',
                is_current: row.is_current,
            })}
            columns={[]}
            list={{
                title: t('academic::admin.tabs.sessions'),
                primary: (row) => bnDigits(row.name),
                secondary: (row) => `${row.is_current ? `${t('academic::admin.rows.current')} · ` : ''}${formatDate(row.start_date)} – ${formatDate(row.end_date)}`,
            }}
            />
        </div>
    );
}
