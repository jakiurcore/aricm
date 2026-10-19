import { Link, useParams } from 'react-router';

import { PageTitle } from '@core/js/components/design';
import { t } from '@core/js/i18n';

import { ResultPanel } from '../components/ResultPanel';

/** The standalone preview route (/results/preview/:examId/:classId): the exam page's "result" tab on its own. */
export function PreviewPage() {
    const { examId, classId } = useParams();

    return (
        <div className="grid gap-3.5">
            <PageTitle>{t('results::admin.preview.title')}</PageTitle>
            <Link to="/results" className="text-sm text-muted-foreground underline">
                ← {t('results::admin.overview.title')}
            </Link>
            <ResultPanel examId={Number(examId)} classId={Number(classId)} />
        </div>
    );
}
