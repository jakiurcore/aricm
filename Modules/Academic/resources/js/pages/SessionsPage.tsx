import { Badge } from '@/components/ui/badge';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';

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
            columns={[
                { header: k('name'), accessorKey: 'name' },
                { header: k('start_date'), cell: ({ row }) => formatDate(row.original.start_date) },
                { header: k('end_date'), cell: ({ row }) => formatDate(row.original.end_date) },
                { header: '', id: 'current', cell: ({ row }) => (row.original.is_current ? <Badge>{k('is_current')}</Badge> : null) },
            ]}
        />
    );
}
