import { PencilIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { Chip, PageTitle } from '@core/js/components/design';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

import { AdmitCardTab } from '../components/AdmitCardTab';
import { ExamFormDialog, type ExamForm } from '../components/ExamFormDialog';
import { ExamStatusPill } from '../components/ExamStatusPill';
import { RoutineTab } from '../components/RoutineTab';
import { SubjectsTab } from '../components/SubjectsTab';
import { getExamTabs, type ExamSummary, type ExamTab } from '../tabs';

type Detail = ExamSummary & { name_en: string | null; description: string | null; class_ids: number[] };

/** The exam page of the design: back pill, serif title, status pill and dates, an underline tab bar, class chips. */
export function ExamDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const [params, setParams] = useSearchParams();
    const [exam, setExam] = useState<Detail | null>(null);
    const [failed, setFailed] = useState(false);
    const [classId, setClassId] = useState<number | null>(null);
    const [editing, setEditing] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            const response = await api.get<{ data: Detail }>(`/exams/${id}`);
            setExam(response.data);
            setClassId((current) => (current !== null && response.data.classes.some((schoolClass) => schoolClass.id === current) ? current : (response.data.classes.find((schoolClass) => schoolClass.id === Number(params.get('class')))?.id ?? response.data.classes[0]?.id ?? null)));
        } catch {
            setFailed(true);
        }
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    useEffect(() => {
        void load();
    }, [load]);

    if (failed) {
        return <ErrorState onRetry={() => void load()} />;
    }

    if (!exam) {
        return <LoadingState rows={3} height={64} />;
    }

    const own: ExamTab[] = [
        { id: 'subjects', label: () => t('exams::admin.tabs.subjects'), order: 10, classPick: true, render: ({ classId: chosen }) => (chosen ? <SubjectsTab examId={exam.id} classId={chosen} /> : null) },
        { id: 'routine', label: () => t('exams::admin.tabs.routine'), order: 20, classPick: true, render: ({ classId: chosen }) => (chosen ? <RoutineTab examId={exam.id} classId={chosen} /> : null) },
        {
            id: 'admit',
            label: () => t('exams::admin.tabs.admit'),
            order: 40,
            classPick: true,
            render: ({ classId: chosen }) => (chosen ? <AdmitCardTab examId={exam.id} classId={chosen} released={exam.admit_card_released} onReleased={(value) => setExam({ ...exam, admit_card_released: value })} /> : null),
        },
    ];
    const tabs = [...own, ...getExamTabs()].sort((a, b) => a.order - b.order);
    const active = tabs.find((candidate) => candidate.id === params.get('tab')) ?? tabs[0];

    const remove = async () => {
        try {
            await api.delete(`/exams/${exam.id}`);
            toast.success(t('core::admin.common.deleted'));
            navigate('/exams');
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        } finally {
            setDeleting(false);
        }
    };

    return (
        <div className="grid gap-3.5">
            <PageTitle>{exam.name}</PageTitle>
            <div className="flex flex-wrap items-center gap-2.5">
                <Button variant="outline" className="h-10 px-3" aria-label={t('exams::admin.title')} onClick={() => navigate('/exams')}>
                    ←
                </Button>
                <strong className="font-head text-[22px] text-heading">{exam.name}</strong>
                <ExamStatusPill exam={exam} size="md" />
                <span className="text-sm text-muted-foreground">{[formatDate(exam.start_date), formatDate(exam.end_date)].filter(Boolean).join(' – ')}</span>
                {can('exams.manage') && (
                    <span className="ml-auto flex gap-1.5">
                        <Button variant="outline" size="icon" aria-label={t('core::admin.common.edit')} onClick={() => setEditing(true)}>
                            <PencilIcon />
                        </Button>
                        <Button variant="outline" size="icon" aria-label={t('core::admin.common.delete')} className="text-danger" onClick={() => setDeleting(true)}>
                            <Trash2Icon />
                        </Button>
                    </span>
                )}
            </div>

            <div role="tablist" className="flex gap-1 overflow-x-auto border-b border-line">
                {tabs.map((candidate) => {
                    const on = candidate.id === active.id;

                    return (
                        <button
                            key={candidate.id}
                            type="button"
                            role="tab"
                            aria-selected={on}
                            onClick={() => setParams({ tab: candidate.id }, { replace: true })}
                            className="min-h-11 flex-none cursor-pointer border-0 bg-transparent px-3.5 font-bold whitespace-nowrap"
                            style={{ color: on ? 'var(--green-700)' : 'var(--ds-muted)', boxShadow: `inset 0 -3px 0 ${on ? 'var(--green-700)' : 'transparent'}` }}
                        >
                            {candidate.label()}
                        </button>
                    );
                })}
            </div>

            {active.classPick && exam.classes.length > 0 && (
                <div className="flex gap-1.5 overflow-x-auto">
                    {exam.classes.map((schoolClass) => (
                        <Chip key={schoolClass.id} size={38} tone="navy" active={schoolClass.id === classId} className="font-semibold" onClick={() => setClassId(schoolClass.id)}>
                            {schoolClass.name}
                        </Chip>
                    ))}
                </div>
            )}

            {active.classPick && exam.classes.length === 0 ? <EmptyState glyph="🏫" title={t('exams::admin.no_classes')} /> : active.render({ exam, classId, reload: () => void load() })}

            <ExamFormDialog
                open={editing}
                exam={{ id: exam.id, name_bn: exam.name_bn, name_en: exam.name_en ?? '', session_id: exam.session_id, start_date: exam.start_date ?? '', end_date: exam.end_date ?? '', status: exam.status, description: exam.description ?? '', class_ids: exam.class_ids } satisfies ExamForm}
                onClose={() => setEditing(false)}
                onSaved={() => {
                    setEditing(false);
                    void load();
                }}
            />
            <ConfirmDialog open={deleting} description={exam.name} onConfirm={() => void remove()} onClose={() => setDeleting(false)} />
        </div>
    );
}
