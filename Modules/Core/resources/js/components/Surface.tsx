import type { ComponentProps, ReactNode } from 'react';

import { cn } from '@/lib/utils';

/*
 * The surfaces the Claude Design "Admin Panel" is built from. Pages compose these instead of repeating the numbers:
 *   Panel      a form/section card: 18px padding, 20px radius, 1px line border, 12px grid gap
 *   PanelTitle the card's h3 (17px bold)
 *   ListCard   a bordered 20px frame that clips a list of rows; ListCardHead is its grey title band
 *   ListRow    one row of such a list (12px 16px, top border between rows)
 *   IconButton the 40px square line button of a row (✎, 🗑, ✕)
 *   FilterBar  the search + selects strip above a table
 *   Dropzone   the dashed green upload area
 *   StickyBar  the bottom action bar of a long form
 */
export function Panel({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('grid gap-3 rounded-[20px] border border-line bg-surface p-[18px]', className)} {...props} />;
}

export function PanelTitle({ className, ...props }: ComponentProps<'h3'>) {
    return <h3 className={cn('m-0 text-[17px] leading-[1.65] font-bold', className)} {...props} />;
}

export function ListCard({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('overflow-hidden rounded-[20px] border border-line bg-surface', className)} {...props} />;
}

export function ListCardHead({ className, children, action, ...props }: Omit<ComponentProps<'div'>, 'children'> & { children: ReactNode; action?: ReactNode }) {
    return (
        <div className={cn('flex items-center justify-between gap-2.5 bg-surface-2 px-4 py-3', className)} {...props}>
            <strong>{children}</strong>
            {action}
        </div>
    );
}

export function ListRow({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('flex items-center gap-2.5 border-t border-line px-4 py-2.5 first:border-t-0', className)} {...props} />;
}

export function IconButton({ className, tone, ...props }: ComponentProps<'button'> & { tone?: 'danger' }) {
    return (
        <button
            type="button"
            className={cn(
                'inline-grid size-10 flex-none cursor-pointer place-items-center rounded-[10px] border border-line bg-surface text-base transition-colors outline-none hover:bg-surface-2 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:cursor-not-allowed disabled:opacity-50',
                tone === 'danger' && 'text-danger',
                className,
            )}
            {...props}
        />
    );
}

export function FilterBar({ className, style, ...props }: ComponentProps<'div'>) {
    return (
        <div
            className={cn('grid gap-2.5 rounded-2xl border border-line bg-surface p-3.5', className)}
            style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 160px), 1fr))', ...style }}
            {...props}
        />
    );
}

export function Dropzone({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('grid justify-items-center gap-2 rounded-[20px] border-2 border-dashed border-green-500 bg-green-50 text-center', className)} {...props} />;
}

export function StickyBar({ className, ...props }: ComponentProps<'div'>) {
    return <div className={cn('sticky bottom-0 z-10 flex justify-end gap-2.5 rounded-2xl border border-line bg-surface p-3 shadow-md', className)} {...props} />;
}

/** The design's switch row: a grey card with the label (and a hint) and a 52 x 30 pill track whose knob slides. */
export function ToggleRow({ label, hint, on, disabled, onToggle }: { label: string; hint?: string; on: boolean; disabled?: boolean; onToggle: () => void }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={on}
            disabled={disabled}
            onClick={onToggle}
            className="flex min-h-14 cursor-pointer items-center justify-between gap-3 rounded-[14px] border border-line bg-surface-2 px-3 py-2 text-left text-foreground disabled:cursor-default"
        >
            <span className="grid leading-[1.45]">
                <strong className="text-[15px]">{label}</strong>
                {hint && <span className="text-[13px] text-muted-foreground">{hint}</span>}
            </span>
            <span className={cn('flex h-[30px] w-[52px] flex-none items-center rounded-full p-[3px]', on ? 'justify-end bg-green-700' : 'justify-start bg-line-strong')}>
                <span className="size-6 rounded-full bg-white shadow-sm" />
            </span>
        </button>
    );
}
