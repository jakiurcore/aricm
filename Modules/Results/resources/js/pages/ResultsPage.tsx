import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
import { DesignTable, PageTitle, Pill, TableCard, Td, Th } from '@core/js/components/design';
import { EmptyState, ErrorState, LoadingState } from '@core/js/components/States';
import { can } from '@core/js/permissions';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';

interface ClassProgress {
    id: number;
    name: string;
    processed: number;
    passed: number;
    incomplete: number;
}

interface ExamOverview {
    id: number;
    name: string;
    session: string;
    status: string;
    published: boolean;
    classes: ClassProgress[];
}

const k = (key: string, replacements: Record<string, string | number> = {}) => t(`results::admin.overview.${key}`, replacements);

export function ResultsPage() {
    const [exams, setExams] = useState<ExamOverview[]>([]);
    const [loading, setLoading] = useState(true);
    const [confirm, setConfirm] = useState<{ exam: ExamOverview; action: 'publish' | 'unpublish' } | null>(null);

    const [failed, setFailed] = useState(false);

    const load = useCallback(async () => {
        setFailed(false);

        try {
            const response = await api.get<{ data: ExamOverview[] }>('/results/overview');
            setExams(response.data);
        } catch {
            setFailed(true);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void load();
    }, [load]);

    const run = async (path: string, success: string) => {
        try {
            await api.post(path);
            toast.success(success);
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? error.message : t('core::admin.common.error'));
        }
    };

    return (
        <div className="grid gap-3.5">
            <PageTitle>{t('results::admin.overview.title')}</PageTitle>

            {failed && <ErrorState onRetry={() => void load()} />}
            {loading && !failed && <LoadingState rows={2} height={160} />}
            {!loading && !failed && exams.length === 0 && <EmptyState glyph="📊" title={t('core::admin.common.empty')} />}

            {exams.map((exam) => (
                <section key={exam.id} className="grid gap-3 rounded-[20px] border border-line bg-surface p-[18px] shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-2.5">
                        <span className="flex flex-wrap items-center gap-2.5">
                            <strong className="text-[17px]">{exam.name}</strong>
                            <Pill tone={exam.published ? 'success' : 'muted'}>{exam.published ? k('published') : k('unpublished')}</Pill>
                            <span className="text-sm text-muted-foreground">{exam.session}</span>
                        </span>
                        <span className="flex flex-wrap gap-2">
                            {can('results.process') && (
                                <Button variant="outline" onClick={() => void run(`/results/exams/${exam.id}/process`, k('processed'))} disabled={exam.published}>
                                    {k('process_all')}
                                </Button>
                            )}
                            {can('results.publish') &&
                                (exam.published ? (
                                    <Button variant="outline" className="border-2 border-danger bg-transparent text-danger hover:bg-danger-bg" onClick={() => setConfirm({ exam, action: 'unpublish' })}>
                                        {k('unpublish')}
                                    </Button>
                                ) : (
                                    <Button className="bg-red-600 text-white hover:bg-red-600" onClick={() => setConfirm({ exam, action: 'publish' })}>
                                        📢 {k('publish')}
                                    </Button>
                                ))}
                        </span>
                    </div>
                    <TableCard className="rounded-2xl">
                        <DesignTable minWidth={560}>
                            <thead>
                                <tr className="bg-surface-2">
                                    <Th>{t('students::admin.fields.class_id')}</Th>
                                    <Th>{k('processed_students')}</Th>
                                    <Th>{k('passed')}</Th>
                                    <Th>{k('incomplete')}</Th>
                                    <Th />
                                </tr>
                            </thead>
                            <tbody>
                                {exam.classes.map((schoolClass) => (
                                    <tr key={schoolClass.id} className="border-t border-line">
                                        <Td className="font-bold">{schoolClass.name}</Td>
                                        <Td>{bnDigits(schoolClass.processed)}</Td>
                                        <Td>{bnDigits(schoolClass.passed)}</Td>
                                        <Td>{schoolClass.incomplete > 0 ? <Pill tone="warn">{bnDigits(schoolClass.incomplete)}</Pill> : '—'}</Td>
                                        <Td align="right">
                                            <span className="inline-flex gap-1.5">
                                                <Button variant="outline" size="sm" render={<Link to={`/exams/${exam.id}?tab=marks&class=${schoolClass.id}`} />}>
                                                    {k('enter_marks')}
                                                </Button>
                                                <Button variant="outline" size="sm" render={<Link to={`/exams/${exam.id}?tab=result&class=${schoolClass.id}`} />}>
                                                    {k('preview')}
                                                </Button>
                                            </span>
                                        </Td>
                                    </tr>
                                ))}
                            </tbody>
                        </DesignTable>
                    </TableCard>
                </section>
            ))}

            <ConfirmDialog
                open={confirm !== null}
                title={confirm?.action === 'publish' ? k('publish_title', { exam: confirm.exam.name }) : k('unpublish_title', { exam: confirm?.exam.name ?? '' })}
                description={confirm?.action === 'publish' ? k('publish_help') : k('unpublish_help')}
                confirmLabel={confirm?.action === 'publish' ? k('publish') : k('unpublish')}
                onClose={() => setConfirm(null)}
                onConfirm={() => {
                    if (confirm) {
                        void run(`/results/exams/${confirm.exam.id}/${confirm.action}`, confirm.action === 'publish' ? k('published_done') : k('unpublished_done'));
                    }
                    setConfirm(null);
                }}
            />
        </div>
    );
}
