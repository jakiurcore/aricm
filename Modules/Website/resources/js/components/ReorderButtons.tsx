import { ChevronDownIcon, ChevronUpIcon } from 'lucide-react';

import { t } from '@core/js/i18n';

/** The up and down arrows that put a row one place earlier or later. */
export function ReorderButtons({ index, count, onMove }: { index: number; count: number; onMove: (step: -1 | 1) => void }) {
    const button = 'inline-grid h-5 w-9 cursor-pointer place-items-center rounded-md border border-line bg-surface text-muted-foreground hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-40';

    return (
        <span className="grid flex-none gap-0.5">
            <button type="button" className={button} aria-label={t('website::admin.ui.move_up')} disabled={index === 0} onClick={() => onMove(-1)}>
                <ChevronUpIcon className="size-4" />
            </button>
            <button type="button" className={button} aria-label={t('website::admin.ui.move_down')} disabled={index === count - 1} onClick={() => onMove(1)}>
                <ChevronDownIcon className="size-4" />
            </button>
        </span>
    );
}
