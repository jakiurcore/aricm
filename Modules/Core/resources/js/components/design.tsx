import type { CSSProperties, ComponentProps, ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Small building blocks lifted straight from the Claude Design "Admin Panel" so pages can repeat its markup without
 * restating the same utility soup: the 20px panel, the table card, the status pill, the filter chip, the segmented control
 * and the 48x28 toggle. Everything reads the design tokens (var(--surface), var(--line)...), so dark mode is free.
 */

/** The page's heading for assistive tech: the topbar carries the visible title, the design has no second one. */
export function PageTitle({ children }: { children: ReactNode }) {
    return <h1 className="sr-only">{children}</h1>;
}

/** The white 20px card with the soft shadow that holds a form or a group of fields. */
export function Panel({ className, children, ...props }: ComponentProps<'div'>) {
    return (
        <div className={cn('grid gap-3 rounded-[20px] border border-line bg-surface p-[18px] shadow-sm', className)} {...props}>
            {children}
        </div>
    );
}

/** The scroll frame around a table: 20px radius, 1px line, sideways scroll on a phone. */
export function TableCard({ className, children, ...props }: ComponentProps<'div'>) {
    return (
        <div className={cn('overflow-x-auto rounded-[20px] border border-line bg-surface', className)} {...props}>
            {children}
        </div>
    );
}

/** A plain design table: 15px text, collapsed borders, an explicit min-width so a phone scrolls instead of squashing. */
export function DesignTable({ minWidth, className, children, ...props }: ComponentProps<'table'> & { minWidth?: number }) {
    return (
        <table className={cn('w-full border-collapse text-[15px]', className)} style={{ minWidth }} {...props}>
            {children}
        </table>
    );
}

export function Th({ className, align = 'left', ...props }: ComponentProps<'th'> & { align?: 'left' | 'right' | 'center' }) {
    return <th className={cn('px-2.5 py-[11px] font-bold whitespace-nowrap first:pl-4 last:pr-4', align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left', className)} {...props} />;
}

export function Td({ className, align = 'left', ...props }: ComponentProps<'td'> & { align?: 'left' | 'right' | 'center' }) {
    return <td className={cn('px-2.5 py-[9px] first:pl-4 last:pr-4', align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : 'text-left', className)} {...props} />;
}

/** Bangla (or Arabic-Indic) digits typed into a number field, as plain ASCII digits the API understands. */
export function toAsciiDigits(value: string): string {
    return value.replace(/[০-৯]/g, (digit) => String('০১২৩৪৫৬৭৮৯'.indexOf(digit)));
}

export type Tone = 'success' | 'warn' | 'info' | 'danger' | 'muted' | 'green' | 'navy' | 'yellow' | 'ink';

const TONES: Record<Tone, [string, string]> = {
    success: ['var(--success-bg)', 'var(--success)'],
    warn: ['var(--warn-bg)', 'var(--warn)'],
    info: ['var(--info-bg)', 'var(--info)'],
    danger: ['var(--danger-bg)', 'var(--danger)'],
    muted: ['var(--surface-2)', 'var(--ds-muted)'],
    green: ['var(--green-50)', 'var(--green-700)'],
    navy: ['var(--navy-50)', 'var(--navy-800)'],
    yellow: ['var(--yellow-50)', 'var(--warn)'],
    ink: ['var(--surface-2)', 'var(--ink)'],
};

/** The status pill of the design: fully round, bold, paired token colours. `md` is the 13px page-header size. */
export function Pill({ tone, size = 'sm', className, children }: { tone: Tone; size?: 'sm' | 'md'; className?: string; children: ReactNode }) {
    const [background, color] = TONES[tone];

    return (
        <span className={cn('inline-block rounded-full font-bold whitespace-nowrap', size === 'md' ? 'px-2.5 py-px text-[13px]' : 'px-2.5 py-0.5 text-xs', className)} style={{ background, color }}>
            {children}
        </span>
    );
}

/** A filter / class chip: a pill button with a 1px border; the active one is solid (green by default, navy for class pickers). */
export function Chip({ active, tone = 'green', size = 40, className, style: extra, children, ...props }: ComponentProps<'button'> & { active?: boolean; tone?: 'green' | 'navy'; size?: 34 | 38 | 40 | 42 }) {
    const solid = tone === 'navy' ? 'var(--navy-800)' : 'var(--green-700)';
    const style: CSSProperties = active ? { background: solid, borderColor: solid, color: '#fff' } : { background: 'var(--surface)', borderColor: 'var(--line-strong)', color: 'var(--ink)' };

    return (
        <button type="button" className={cn('flex-none cursor-pointer rounded-full border px-3.5 text-sm font-bold whitespace-nowrap', className)} style={{ minHeight: size, ...style, ...extra }} {...props}>
            {children}
        </button>
    );
}

/** The segmented control: a surface-2 track holding pills, the active one lifted (surface) or filled (green). */
export function Segmented<V extends string>({ value, options, onChange, filled = true, round = 'full', className, label }: { value: V; options: { value: V; label: ReactNode }[]; onChange: (value: V) => void; filled?: boolean; round?: 'full' | 'lg'; className?: string; label?: string }) {
    return (
        <div role="group" aria-label={label} className={cn('flex border border-line bg-surface-2 p-[3px]', round === 'full' ? 'rounded-full' : 'rounded-xl', className)}>
            {options.map((option) => {
                const active = option.value === value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={active}
                        onClick={() => onChange(option.value)}
                        className={cn('cursor-pointer border-0 font-bold whitespace-nowrap', round === 'full' ? 'h-[38px] rounded-full px-3.5 text-sm' : 'h-9 flex-1 rounded-[10px]')}
                        style={active ? (filled ? { background: 'var(--green-700)', color: '#fff' } : { background: 'var(--surface)', color: 'var(--green-700)' }) : { background: 'transparent', color: filled ? 'var(--ink)' : 'var(--ds-muted)' }}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

/** The design's switch: 48x28, 22px knob, green when on. */
export function Toggle({ checked, onChange, label, disabled }: { checked: boolean; onChange: (checked: boolean) => void; label: string; disabled?: boolean }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className="flex min-h-0 flex-none cursor-pointer items-center rounded-full border-0 p-[3px] disabled:cursor-not-allowed disabled:opacity-50"
            style={{ width: 48, height: 28, background: checked ? 'var(--green-700)' : 'var(--line-strong)', justifyContent: checked ? 'flex-end' : 'flex-start' }}
        >
            <span className="rounded-full bg-white shadow-sm" style={{ width: 22, height: 22 }} />
        </button>
    );
}

/** The dashed 20px empty frame with a big glyph, a title and a line (the design's "no student", "nothing to verify"). */
export function DashedEmpty({ glyph, title, description, className }: { glyph?: string; title: string; description?: ReactNode; className?: string }) {
    return (
        <div className={cn('grid justify-items-center gap-1.5 border-2 border-dashed border-input px-5 py-10 text-center', className)} style={{ borderRadius: 20 }}>
            {glyph && (
                <span className="text-[32px]" aria-hidden="true">
                    {glyph}
                </span>
            )}
            <strong className="text-[17px]">{title}</strong>
            {description && <span className="text-muted-foreground">{description}</span>}
        </div>
    );
}

/** A bordered 12px notice line (warn / success / danger / info), as in the design's inline messages. */
export function Notice({ tone, className, children }: { tone: Tone; className?: string; children: ReactNode }) {
    const [background, color] = TONES[tone];

    return (
        <span className={cn('block rounded-xl px-3 py-2 text-sm font-semibold', className)} style={{ background, color }}>
            {children}
        </span>
    );
}
