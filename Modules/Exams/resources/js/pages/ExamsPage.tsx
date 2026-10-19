import { SettingsIcon } from 'lucide-react';
import { Link } from 'react-router';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ResourcePage } from '@core/js/components/ResourcePage';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';

import type { ExamSummary } from '../tabs';

const k = (key: string) => t(`exams::admin.${key}`);

export function ExamsPage() {
    const academic = useAcademicOptions();

    return (
        <ResourcePage<ExamSummary & { name_en: string | null; description: string | null; session_name?: string; class_ids: number[] }>
            title={k('title')}
            newLabel={k('new')}
            editLabel={k('edit')}
            endpoint="/exams"
            defaults={{ name_bn: '', name_en: '', session_id: '', start_date: '', end_date: '', status: 'draft', description: '', class_ids: [] }}
            filters={[
                { name: 'session_id', label: k('fields.session_id'), options: academic.sessions.map((session) => ({ value: session.id, label: session.name })) },
                { name: 'status', label: k('fields.status'), options: ['draft', 'ongoing', 'completed'].map((value) => ({ value, label: t(`exams::admin.status.${value}`) })) },
            ]}
            fields={[
                { name: 'name_bn', label: k('fields.name_bn'), type: 'text', hint: k('fields.name_hint') },
                { name: 'name_en', label: k('fields.name_en'), type: 'text' },
                { name: 'session_id', label: k('fields.session_id'), type: 'select', options: academic.sessions.map((session) => ({ value: session.id, label: session.name })) },
                { name: 'status', label: k('fields.status'), type: 'select', options: ['draft', 'ongoing', 'completed'].map((value) => ({ value, label: t(`exams::admin.status.${value}`) })) },
                { name: 'start_date', label: k('fields.start_date'), type: 'date' },
                { name: 'end_date', label: k('fields.end_date'), type: 'date' },
                { name: 'class_ids', label: k('fields.classes'), type: 'checkboxes', wide: true, options: academic.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name })) },
                { name: 'description', label: k('fields.description'), type: 'textarea', wide: true },
            ]}
            toForm={(row) => ({ name_bn: row.name_bn, name_en: row.name_en ?? '', session_id: row.session_id, status: row.status, start_date: row.start_date ?? '', end_date: row.end_date ?? '', description: row.description ?? '', class_ids: row.class_ids })}
            columns={[
                { header: k('fields.name_bn'), cell: ({ row }) => <span className="font-medium">{row.original.name_bn}</span> },
                { header: k('fields.session_id'), accessorKey: 'session_name' },
                { header: k('fields.period'), cell: ({ row }) => [formatDate(row.original.start_date), formatDate(row.original.end_date)].filter(Boolean).join(' – ') },
                { header: k('fields.classes'), cell: ({ row }) => row.original.classes.map((schoolClass) => schoolClass.name).join(', ') },
                { header: k('fields.status'), cell: ({ row }) => <Badge variant={row.original.status === 'ongoing' ? 'default' : 'outline'}>{t(`exams::admin.status.${row.original.status}`)}</Badge> },
            ]}
            rowActions={(row) => (
                <Button variant="ghost" size="icon" aria-label={k('manage')} title={k('manage')} render={<Link to={`/exams/${row.id}`} />}>
                    <SettingsIcon />
                </Button>
            )}
        />
    );
}
