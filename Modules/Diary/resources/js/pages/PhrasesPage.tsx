import { Badge } from '@/components/ui/badge';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

interface Phrase {
    id: number;
    field: string;
    text_bn: string;
    sort_order: number;
    is_active: boolean;
}

const k = (key: string) => t(`diary::admin.${key}`);
const fields = ['lesson', 'homework', 'note', 'remark'];

/** The shared quick phrases teachers tap into the diary. Not in the design, so it uses the standard table and dialog. */
export function PhrasesPage() {
    return (
        <ResourcePage<Phrase>
            title={k('phrases.title')}
            newLabel={k('phrases.new')}
            editLabel={k('phrases.edit')}
            endpoint="/diary/phrases"
            searchable={false}
            hideCreate={!can('diary.manage')}
            canEdit={() => can('diary.manage')}
            canDelete={() => can('diary.manage')}
            defaults={{ field: 'lesson', text_bn: '', sort_order: 0, is_active: true }}
            fields={[
                { name: 'field', label: k('phrases.field'), type: 'select', options: fields.map((field) => ({ value: field, label: k(`phrases.fields.${field}`) })) },
                { name: 'text_bn', label: k('phrases.text_bn'), type: 'text', wide: true },
                { name: 'sort_order', label: k('phrases.sort_order'), type: 'number' },
                { name: 'is_active', label: k('phrases.is_active'), type: 'switch' },
            ]}
            columns={[
                { header: k('phrases.field'), cell: ({ row }) => k(`phrases.fields.${row.original.field}`) },
                { header: k('phrases.text_bn'), cell: ({ row }) => row.original.text_bn },
                { header: '', id: 'state', cell: ({ row }) => <Badge variant={row.original.is_active ? 'success' : 'secondary'}>{row.original.is_active ? k('phrases.is_active') : '—'}</Badge> },
            ]}
        />
    );
}
