import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router';

import { Badge } from '@/components/ui/badge';
import { api } from '@core/js/api';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';

import { AdmitCardTab } from '../components/AdmitCardTab';
import { RoutineTab } from '../components/RoutineTab';
import { SubjectsTab } from '../components/SubjectsTab';
import { getExamTabs, type ExamSummary } from '../tabs';

export function ExamDetailPage() {
    const { id } = useParams();
    const [exam, setExam] = useState<ExamSummary | null>(null);
    const [classId, setClassId] = useState<number | null>(null);
    const [tab, setTab] = useState('subjects');

    const load = useCallback(async () => {
        const response = await api.get<{ data: ExamSummary }>(`/exams/${id}`);
        setExam(response.data);
        setClassId((current) => current ?? response.data.classes[0]?.id ?? null);
    }, [id]);

    useEffect(() => {
        void load();
    }, [load]);

    if (!exam) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const own = [
        { id: 'subjects', label: () => t('exams::admin.tabs.subjects'), order: 10, render: () => (classId ? <SubjectsTab examId={exam.id} classId={classId} /> : null) },
        { id: 'routine', label: () => t('exams::admin.tabs.routine'), order: 20, render: () => (classId ? <RoutineTab examId={exam.id} classId={classId} /> : null) },
        { id: 'admit', label: () => t('exams::admin.tabs.admit'), order: 30, render: () => (classId ? <AdmitCardTab examId={exam.id} classId={classId} released={exam.admit_card_released} onReleased={(value) => setExam({ ...exam, admit_card_released: value })} /> : null) },
    ];
    const tabs = [...own, ...getExamTabs()].sort((a, b) => a.order - b.order);
    const active = tabs.find((candidate) => candidate.id === tab) ?? tabs[0];

    return (
        <div className="max-w-5xl space-y-4">
            <Link to="/exams" className="text-sm text-muted-foreground underline">
                ← {t('exams::admin.title')}
            </Link>
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">{exam.name_bn}</h1>
                <p className="text-sm text-muted-foreground">
                    {[formatDate(exam.start_date), formatDate(exam.end_date)].filter(Boolean).join(' – ')} <Badge variant="outline">{t(`exams::admin.status.${exam.status}`)}</Badge>
                </p>
            </div>

            {exam.classes.length === 0 ? (
                <p className="rounded-xl border bg-card p-4 text-sm text-muted-foreground">{t('exams::admin.no_classes')}</p>
            ) : (
                <>
                    <div className="flex flex-wrap items-center gap-3">
                        <NativeSelect className="w-auto" aria-label={t('students::admin.fields.class_id')} value={classId ?? ''} onChange={(event) => setClassId(Number(event.target.value))} options={exam.classes.map((schoolClass) => ({ value: schoolClass.id, label: schoolClass.name }))} />
                        <div className="flex flex-wrap gap-1 rounded-lg bg-muted p-1">
                            {tabs.map((candidate) => (
                                <button key={candidate.id} type="button" onClick={() => setTab(candidate.id)} className={`rounded-md px-3 py-1.5 text-sm ${candidate.id === active.id ? 'bg-background font-medium shadow-sm' : 'text-muted-foreground'}`}>
                                    {candidate.label()}
                                </button>
                            ))}
                        </div>
                    </div>
                    {active.render({ exam })}
                </>
            )}
        </div>
    );
}
