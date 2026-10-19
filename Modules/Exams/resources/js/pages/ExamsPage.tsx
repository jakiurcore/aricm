import { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router';

import { HeaderActions } from '@admin/js';
import { Button } from '@/components/ui/button';
import { api } from '@core/js/api';
import { PageTitle } from '@core/js/components/design';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits, formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

import { ExamFormDialog } from '../components/ExamFormDialog';
import { ExamStatusPill } from '../components/ExamStatusPill';
import type { ExamSummary } from '../tabs';

const k = (key: string) => t(`exams::admin.${key}`);

/** The exam list of the design: a responsive grid of 20px cards, the "+ new exam" pill at the top right. */
export function ExamsPage() {
    const navigate = useNavigate();
    const [exams, setExams] = useState<ExamSummary[] | null>(null);
    const [failed, setFailed] = useState(false);
    const [creating, setCreating] = useState(false);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            setExams((await api.get<{ data: ExamSummary[] }>('/exams', { per_page: '60' })).data);
        } catch {
            setFailed(true);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    return (
        <div className="grid gap-3.5">
            <PageTitle>{k('title')}</PageTitle>
            {can('exams.manage') && (
                <HeaderActions.Fill>
                    <Button onClick={() => setCreating(true)}>+ {k('new')}</Button>
                </HeaderActions.Fill>
            )}

            {failed && <ErrorState onRetry={() => void load()} />}
            {!failed && exams === null && <LoadingState rows={3} height={112} />}
            {exams?.length === 0 && <EmptyState glyph="📝" title={k('empty')} />}

            <div className="grid gap-3" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 300px), 1fr))' }}>
                {exams?.map((exam) => (
                    <button key={exam.id} type="button" onClick={() => navigate(`/exams/${exam.id}`)} className="grid cursor-pointer gap-2 rounded-[20px] border border-line bg-surface p-[18px] text-left text-ink shadow-sm transition-colors hover:border-line-strong focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400">
                        <span className="flex items-center justify-between gap-2">
                            <strong className="text-[17px]">{exam.name}</strong>
                            <ExamStatusPill exam={exam} />
                        </span>
                        <span className="text-sm text-muted-foreground">{[formatDate(exam.start_date), formatDate(exam.end_date)].filter(Boolean).join(' – ')}</span>
                        <span className="text-sm">
                            {bnDigits(exam.classes.length)} {k('classes_n')} · {bnDigits(exam.subjects_count ?? 0)} {k('subjects_n')}
                        </span>
                    </button>
                ))}
            </div>

            <ExamFormDialog open={creating} exam={null} onClose={() => setCreating(false)} onSaved={(id) => navigate(`/exams/${id}`)} />
        </div>
    );
}
