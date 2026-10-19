import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { api, ApiError } from '@core/js/api';
import { ConfirmDialog } from '@core/js/components/ConfirmDialog';
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

    const load = useCallback(async () => {
        const response = await api.get<{ data: ExamOverview[] }>('/results/overview');
        setExams(response.data);
        setLoading(false);
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
        <div className="max-w-5xl space-y-4">
            <h1 className="text-2xl font-semibold tracking-tight">{t('results::admin.overview.title')}</h1>

            {loading && <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>}
            {!loading && exams.length === 0 && <p className="text-muted-foreground">{t('core::admin.common.empty')}</p>}

            {exams.map((exam) => (
                <Card key={exam.id}>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-2">
                        <CardTitle className="flex items-center gap-2">
                            {exam.name}
                            <Badge variant={exam.published ? 'default' : 'outline'}>{exam.published ? k('published') : k('unpublished')}</Badge>
                        </CardTitle>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm" onClick={() => void run(`/results/exams/${exam.id}/process`, k('processed'))} disabled={exam.published}>
                                {k('process_all')}
                            </Button>
                            {exam.published ? (
                                <Button variant="outline" size="sm" onClick={() => setConfirm({ exam, action: 'unpublish' })}>
                                    {k('unpublish')}
                                </Button>
                            ) : (
                                <Button size="sm" onClick={() => setConfirm({ exam, action: 'publish' })}>
                                    {k('publish')}
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2">{t('students::admin.fields.class_id')}</th>
                                        <th className="py-2">{k('processed_students')}</th>
                                        <th className="py-2">{k('passed')}</th>
                                        <th className="py-2">{k('incomplete')}</th>
                                        <th className="py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {exam.classes.map((schoolClass) => (
                                        <tr key={schoolClass.id} className="border-b last:border-0">
                                            <td className="py-2 font-medium">{schoolClass.name}</td>
                                            <td className="py-2">{bnDigits(schoolClass.processed)}</td>
                                            <td className="py-2">{bnDigits(schoolClass.passed)}</td>
                                            <td className="py-2">{schoolClass.incomplete > 0 ? <span className="text-amber-700">{bnDigits(schoolClass.incomplete)}</span> : '—'}</td>
                                            <td className="flex justify-end gap-2 py-2">
                                                <Button variant="outline" size="sm" render={<Link to={`/results/marks/${exam.id}/${schoolClass.id}`} />}>
                                                    {k('enter_marks')}
                                                </Button>
                                                <Button variant="outline" size="sm" render={<Link to={`/results/preview/${exam.id}/${schoolClass.id}`} />}>
                                                    {k('preview')}
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
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
