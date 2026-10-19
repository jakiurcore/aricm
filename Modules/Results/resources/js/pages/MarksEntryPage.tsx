import { Link, useParams } from 'react-router';

import { PageTitle } from '@core/js/components/design';
import { t } from '@core/js/i18n';

import { MarksGrid } from '../components/MarksGrid';

/** The standalone marks route (/results/marks/:examId/:classId): the same grid the exam page's "marks entry" tab shows. */
export function MarksEntryPage() {
    const params = useParams();

    return (
        <div className="grid gap-3.5">
            <PageTitle>{t('results::admin.marks.title')}</PageTitle>
            <Link to="/results" className="text-sm text-muted-foreground underline">
                ← {t('results::admin.overview.title')}
            </Link>
            <MarksGrid examId={Number(params.examId)} classId={Number(params.classId)} />
        </div>
    );
}
