import { PrinterIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';

import { useAcademicOptions } from '@academic/js/hooks/useAcademicOptions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { api } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { bnDigits, formatNumber } from '@core/js/number';

interface Row {
    id: number;
    code: string;
    name: string;
    roll: number | null;
    section: string | null;
    total_marks: number;
    full_marks: number;
    percentage: number;
    gpa: number;
    grade: string;
    is_passed: boolean;
    is_incomplete: boolean;
    position_in_class: number | null;
    position_in_section: number | null;
    failed_subjects_count: number;
    subjects: { name: string; grade: string; total: number; passed: boolean; absent: boolean }[];
}

const k = (key: string) => t(`results::admin.preview.${key}`);

export function PreviewPage() {
    const { examId, classId } = useParams();
    const academic = useAcademicOptions();
    const [sectionId, setSectionId] = useState('');
    const [rows, setRows] = useState<Row[]>([]);
    const [published, setPublished] = useState(false);
    const [view, setView] = useState<'tabulation' | 'merit'>('tabulation');
    const sections = academic.classes.find((schoolClass) => String(schoolClass.id) === classId)?.sections ?? [];

    useEffect(() => {
        void api.get<{ data: Row[]; published: boolean }>(`/results/exams/${examId}/classes/${classId}/preview`, { section_id: sectionId }).then((response) => {
            setRows(response.data);
            setPublished(response.published);
        });
    }, [examId, classId, sectionId]);

    const shown = view === 'merit' ? rows.filter((row) => row.is_passed) : rows;

    return (
        <div className="space-y-4">
            <Link to="/results" className="text-sm text-muted-foreground underline">
                ← {t('results::admin.overview.title')}
            </Link>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                    {k('title')} <Badge variant={published ? 'default' : 'outline'}>{published ? t('results::admin.overview.published') : t('results::admin.overview.unpublished')}</Badge>
                </h1>
                <div className="flex items-center gap-3">
                    <NativeSelect className="w-auto" placeholder={t('exams::admin.admit.all_sections')} value={sectionId} onChange={(event) => setSectionId(event.target.value)} options={sections.map((section) => ({ value: section.id, label: section.name }))} />
                    <div className="flex gap-1 rounded-lg bg-muted p-1">
                        {(['tabulation', 'merit'] as const).map((option) => (
                            <button key={option} type="button" onClick={() => setView(option)} className={`rounded-md px-3 py-1.5 text-sm ${view === option ? 'bg-background font-medium shadow-sm' : 'text-muted-foreground'}`}>
                                {k(option)}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                {(['tabulation', 'merit', 'marksheets'] as const).map((document) => (
                    <Button key={document} variant="outline" size="sm" render={<a href={`/api/results/exams/${examId}/classes/${classId}/${document}?section_id=${sectionId}`} target="_blank" rel="noreferrer" />}>
                        <PrinterIcon /> {k(`print_${document}`)}
                    </Button>
                ))}
            </div>

            <div className="overflow-x-auto rounded-xl border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b text-left text-muted-foreground">
                            <th className="p-3">{k('position')}</th>
                            <th className="p-3">{t('students::admin.fields.roll')}</th>
                            <th className="p-3">{t('students::admin.fields.name_bn')}</th>
                            <th className="p-3 text-right">{k('total')}</th>
                            <th className="p-3 text-right">%</th>
                            <th className="p-3 text-right">GPA</th>
                            <th className="p-3">{k('grade')}</th>
                            <th className="p-3">{k('subjects')}</th>
                            <th className="p-3">{k('result')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {shown.length === 0 && (
                            <tr>
                                <td colSpan={9} className="p-6 text-center text-muted-foreground">
                                    {k('empty')}
                                </td>
                            </tr>
                        )}
                        {shown.map((row) => (
                            <tr key={row.id} className="border-b last:border-0">
                                <td className="p-3 font-medium">{row.position_in_class ? bnDigits(row.position_in_class) : '—'}</td>
                                <td className="p-3">{row.roll ? bnDigits(row.roll) : '—'}</td>
                                <td className="p-3">
                                    {row.name}
                                    {row.is_incomplete && <Badge variant="outline" className="ml-2">{k('incomplete')}</Badge>}
                                    <div className="text-xs text-muted-foreground">{row.code}</div>
                                </td>
                                <td className="p-3 text-right">{bnDigits(row.total_marks)}/{bnDigits(row.full_marks)}</td>
                                <td className="p-3 text-right">{formatNumber(row.percentage, 2)}</td>
                                <td className="p-3 text-right font-semibold">{formatNumber(row.gpa, 2)}</td>
                                <td className="p-3">{row.grade}</td>
                                <td className="p-3 text-xs text-muted-foreground">
                                    {row.subjects.map((subject) => (
                                        <span key={subject.name} className={`mr-2 inline-block ${subject.passed ? '' : 'text-destructive'}`}>
                                            {subject.name}: {subject.absent ? t('results::messages.sheet.absent') : subject.grade}
                                        </span>
                                    ))}
                                </td>
                                <td className="p-3">{row.is_passed ? <Badge>{t('results::messages.passed')}</Badge> : <Badge variant="outline">{t('results::messages.failed')}</Badge>}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
