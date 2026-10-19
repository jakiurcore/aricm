import type { ReactNode } from 'react';

import { t } from '../i18n';

/** The design's loading state: shimmer bands (80px, 16px radius) in place of the content. */
export function LoadingState({ rows = 4, height = 80 }: { rows?: number; height?: number }) {
    return (
        <div className="grid gap-3" role="status" aria-busy="true" aria-label={t('core::admin.common.loading')}>
            {Array.from({ length: rows }, (_, index) => (
                <div key={index} className="adm-skeleton" style={{ height, borderRadius: 16 }} />
            ))}
        </div>
    );
}

/** The design's empty state: a dashed 20px frame with a glyph, a title and an optional line and action. */
export function EmptyState({ title, description, action, glyph = '📭' }: { title?: string; description?: ReactNode; action?: ReactNode; glyph?: string }) {
    return (
        <div className="grid justify-items-center gap-2 border-2 border-dashed border-input text-center" style={{ padding: '48px 20px', borderRadius: 20 }}>
            <span className="text-[32px]" aria-hidden="true">
                {glyph}
            </span>
            <strong className="text-lg">{title ?? t('core::admin.shell.states.empty_title')}</strong>
            {description && <span className="text-muted-foreground">{description}</span>}
            {action}
        </div>
    );
}

/** The design's error state: a danger-tinted panel with a retry pill. */
export function ErrorState({ title, description, onRetry }: { title?: string; description?: ReactNode; onRetry?: () => void }) {
    return (
        <div role="alert" className="grid justify-items-center gap-2 text-center" style={{ padding: '32px 20px', borderRadius: 20, background: 'var(--danger-bg)' }}>
            <span className="text-[32px]" aria-hidden="true">
                ⚠
            </span>
            <strong className="text-lg" style={{ color: 'var(--danger)' }}>
                {title ?? t('core::admin.shell.states.error_title')}
            </strong>
            <span>{description ?? t('core::admin.shell.states.error_body')}</span>
            {onRetry && (
                <button type="button" onClick={onRetry} className="cursor-pointer font-bold text-white" style={{ height: 44, padding: '0 18px', borderRadius: 999, border: 0, background: 'var(--danger)' }}>
                    {t('core::admin.shell.states.retry')}
                </button>
            )}
        </div>
    );
}
