import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

import { AcademicTabs } from '../components/AcademicTabs';

interface SchoolClass {
    id: number;
    name_bn: string;
    name_en: string | null;
    numeric_order: number;
    division: string | null;
    is_active: boolean;
    sections_count: number;
    subjects_count: number;
}

const k = (key: string) => t(`academic::admin.classes.${key}`);

export function ClassesPage() {
    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            <ResourcePage<SchoolClass>
                title={k('title')}
                newLabel={k('new')}
                editLabel={k('edit')}
                endpoint="/academic/classes"
                searchable={false}
                defaults={{ name_bn: '', name_en: '', numeric_order: 1, division: '', is_active: true }}
                fields={[
                    { name: 'name_bn', label: k('name_bn'), type: 'text' },
                    { name: 'name_en', label: k('name_en'), type: 'text' },
                    { name: 'numeric_order', label: k('numeric_order'), type: 'number' },
                    {
                        name: 'division',
                        label: k('division'),
                        type: 'select',
                        options: [
                            { value: 'kids', label: t('academic::admin.divisions.kids') },
                            { value: 'junior', label: t('academic::admin.divisions.junior') },
                        ],
                    },
                    { name: 'is_active', label: k('is_active'), type: 'switch' },
                ]}
                toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', numeric_order: row.numeric_order, division: row.division ?? '', is_active: row.is_active })}
                columns={[]}
                list={{
                    title: t('academic::admin.tabs.classes'),
                    primary: (row) => row.name_bn,
                    secondary: (row) => `${t('academic::admin.rows.sections_n', { count: bnDigits(row.sections_count) })}${row.is_active ? '' : ` · ${k('inactive')}`}`,
                }}
            />
        </div>
    );
}
