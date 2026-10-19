import { Badge } from '@/components/ui/badge';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface Head {
    id: number;
    name_bn: string;
    name_en: string | null;
    type: string;
    is_active: boolean;
    sort_order: number;
}

const k = (key: string) => t(`fees::admin.heads.${key}`);
const types = ['monthly', 'one_time', 'exam'].map((value) => ({ value, label: t(`fees::admin.types.${value}`) }));

export function HeadsPage() {
    return (
        <ResourcePage<Head>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/fees/heads"
            searchable={false}
            defaults={{ name_bn: '', name_en: '', type: 'monthly', is_active: true, sort_order: 0 }}
            fields={[
                { name: 'name_bn', label: k('name_bn'), type: 'text' },
                { name: 'name_en', label: k('name_en'), type: 'text' },
                { name: 'type', label: k('type'), type: 'select', options: types },
                { name: 'sort_order', label: k('sort_order'), type: 'number' },
                { name: 'is_active', label: k('is_active'), type: 'switch' },
            ]}
            toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', type: row.type, is_active: row.is_active, sort_order: row.sort_order })}
            columns={[
                { header: k('sort_order'), cell: ({ row }) => bnDigits(row.original.sort_order) },
                { header: k('name_bn'), cell: ({ row }) => <span className="font-medium">{row.original.name_bn}</span> },
                { header: k('name_en'), accessorKey: 'name_en' },
                { header: k('type'), cell: ({ row }) => t(`fees::admin.types.${row.original.type}`) },
                { header: '', id: 'state', cell: ({ row }) => (row.original.is_active ? null : <Badge variant="outline">{k('inactive')}</Badge>) },
            ]}
        />
    );
}
