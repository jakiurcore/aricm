import { useAcademicOptions } from '../hooks/useAcademicOptions';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';

import { AcademicTabs } from '../components/AcademicTabs';

interface Section {
    id: number;
    class_id: number;
    name_bn: string;
    name_en: string | null;
    school_class?: { name_bn: string; name_en: string | null } | null;
    schoolClass?: { name_bn: string; name_en: string | null } | null;
}

const k = (key: string) => t(`academic::admin.sections.${key}`);

/** Sections belong to a class, so the list reads "class – section" and the form picks the class. */
export function SectionsPage() {
    const academic = useAcademicOptions();

    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            <ResourcePage<Section>
                title={k('title')}
                newLabel={k('new')}
                editLabel={k('edit')}
                endpoint="/academic/sections"
                searchable={false}
                defaults={{ class_id: '', name_bn: '', name_en: '' }}
                fields={[
                    { name: 'class_id', label: k('class'), type: 'select', wide: true, options: academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name })) },
                    { name: 'name_bn', label: k('name_bn'), type: 'text' },
                    { name: 'name_en', label: k('name_en'), type: 'text' },
                ]}
                toForm={(row) => ({ class_id: row.class_id, name_bn: row.name_bn, name_en: row.name_en ?? '' })}
                columns={[]}
                list={{
                    title: t('academic::admin.tabs.sections'),
                    primary: (row) => [(row.schoolClass ?? row.school_class)?.name_bn, row.name_bn].filter(Boolean).join(' – '),
                    secondary: (row) => row.name_en,
                }}
            />
        </div>
    );
}
