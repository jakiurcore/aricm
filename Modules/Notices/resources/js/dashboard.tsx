import { MegaphoneIcon } from 'lucide-react';

import { registerModule } from '@admin/js';
import { Badge } from '@/components/ui/badge';
import { addToGroup } from '@core/js/menu';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';
import { formatDate } from '@core/js/number';

interface Notice {
    id: number;
    title_bn: string;
    title_en: string | null;
    body_bn: string;
    body_en: string | null;
    category: string;
    attachment_url: string | null;
    attachment_name: string | null;
    is_pinned: boolean;
    show_in_ticker: boolean;
    is_published: boolean;
    publish_at: string | null;
    visible: boolean;
    created_at: string;
}

const k = (key: string) => t(`notices::admin.${key}`);
const categories = ['general', 'exam', 'admission', 'holiday', 'event', 'result'].map((value) => ({ value, label: t(`notices::messages.category.${value}`) }));

function NoticesPage() {
    return (
        <ResourcePage<Notice>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/notices"
            hideCreate={!can('notices.manage')}
            canEdit={() => can('notices.manage')}
            canDelete={() => can('notices.manage')}
            filters={[{ name: 'category', label: k('category'), options: categories }]}
            defaults={{ title_bn: '', title_en: '', body_bn: '', body_en: '', category: 'general', attachment: undefined, remove_attachment: false, is_pinned: false, show_in_ticker: false, is_published: true, publish_at: '' }}
            fields={[
                { name: 'title_bn', label: k('title_bn'), type: 'text', wide: true },
                { name: 'title_en', label: k('title_en'), type: 'text', wide: true },
                { name: 'category', label: k('category'), type: 'select', options: categories },
                { name: 'publish_at', label: k('publish_at'), type: 'datetime-local', hint: k('publish_hint') },
                { name: 'body_bn', label: k('body_bn'), type: 'textarea', wide: true },
                { name: 'body_en', label: k('body_en'), type: 'textarea', wide: true },
                { name: 'attachment', label: k('attachment'), type: 'file', accept: '.pdf,image/png,image/jpeg', wide: true },
                { name: 'remove_attachment', label: k('remove_attachment'), type: 'switch' },
                { name: 'is_pinned', label: k('is_pinned'), type: 'switch' },
                { name: 'show_in_ticker', label: k('show_in_ticker'), type: 'switch' },
                { name: 'is_published', label: k('is_published'), type: 'switch' },
            ]}
            toForm={(row) => ({ ...row, attachment: undefined, remove_attachment: false, title_en: row.title_en ?? '', body_en: row.body_en ?? '', publish_at: row.publish_at ?? '' })}
            columns={[
                { header: k('title_bn'), cell: ({ row }) => (<div><div className="font-medium">{row.original.title_bn}</div><div className="text-xs text-muted-foreground">{t(`notices::messages.category.${row.original.category}`)} · {formatDate(row.original.created_at)}</div></div>) },
                {
                    header: '',
                    id: 'state',
                    cell: ({ row }) => (
                        <div className="flex flex-wrap gap-1">
                            {row.original.is_pinned && <Badge variant="secondary">📌</Badge>}
                            {row.original.show_in_ticker && <Badge variant="secondary">{k('show_in_ticker')}</Badge>}
                            <Badge variant={row.original.visible ? 'default' : 'outline'}>{row.original.visible ? k('live') : row.original.is_published ? k('scheduled') : k('draft')}</Badge>
                        </div>
                    ),
                },
            ]}
        />
    );
}

registerModule({
    id: 'notices',
    menuFilter: (menu) => (can('notices.view') ? addToGroup(menu, t('core::admin.content_group'), [{ label: k('menu'), icon: MegaphoneIcon, path: '/notices' }]) : menu),
    routes: [{ path: 'notices', element: <NoticesPage /> }],
});
