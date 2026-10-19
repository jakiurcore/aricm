import { Pill, type Tone } from '@core/js/components/design';
import { t } from '@core/js/i18n';

import type { ExamSummary } from '../tabs';

/** The design's status pill: a published result wins over the exam's own status. */
export function ExamStatusPill({ exam, size = 'sm' }: { exam: Pick<ExamSummary, 'status' | 'result_published'>; size?: 'sm' | 'md' }) {
    const published = Boolean(exam.result_published);
    const tone: Tone = published ? 'success' : exam.status === 'ongoing' ? 'info' : exam.status === 'draft' ? 'muted' : 'warn';

    return (
        <Pill tone={tone} size={size}>
            {published ? t('exams::admin.status.published') : t(`exams::admin.status.${exam.status}`)}
        </Pill>
    );
}
