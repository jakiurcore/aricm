import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';

import { AcademicTabs } from '../components/AcademicTabs';

interface Subject {
    id: number;
    name_bn: string;
    name_en: string | null;
    code: string | null;
}

const k = (key: string) => t(`academic::admin.subjects.${key}`);

export function SubjectsPage() {
    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            <ResourcePage<Subject>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/academic/subjects"
            searchable={false}
            defaults={{ name_bn: '', name_en: '', code: '' }}
            fields={[
                { name: 'name_bn', label: k('name_bn'), type: 'text' },
                { name: 'name_en', label: k('name_en'), type: 'text' },
                { name: 'code', label: k('code'), type: 'text' },
            ]}
            toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', code: row.code ?? '' })}
            columns={[]}
            list={{
                title: t('academic::admin.tabs.subjects'),
                primary: (row) => row.name_bn,
                secondary: (row) => [row.name_en, row.code].filter(Boolean).join(' · '),
            }}
            />
        </div>
    );
}
