import { CheckIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import { t } from '@core/js/i18n';

/** The very same SVG files the public site inlines (Modules/Core/resources/icons). */
const sources = import.meta.glob('@core/icons/*.svg', { query: '?raw', import: 'default', eager: true }) as Record<string, string>;

const icons = Object.entries(sources)
    .map(([path, svg]) => ({ key: path.split('/').pop()!.replace('.svg', ''), svg }))
    .sort((a, b) => a.key.localeCompare(b.key));

const known = new Set(icons.map((icon) => icon.key));

/** A static preview of a stored icon value: the SVG for a key, the raw text for a legacy emoji. */
export function IconPreview({ value }: { value: string }) {
    const svg = icons.find((icon) => icon.key === value)?.svg;

    return svg ? <span className="ico-static size-6 [&>svg]:size-6" dangerouslySetInnerHTML={{ __html: svg }} /> : <span>{value}</span>;
}

interface IconPickerProps {
    id?: string;
    value: string;
    onChange: (value: string) => void;
}

/** A compact grid of icon keys with previews. A legacy emoji value is shown as a selected "custom" state. */
export function IconPicker({ id, value, onChange }: IconPickerProps) {
    const custom = value !== '' && !known.has(value);
    const label = (key: string) => key;

    return (
        <div id={id} role="group" aria-label={t('website::admin.ui.icon_pick')} className="grid gap-2">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(44px,1fr))] gap-1.5">
                <button type="button" aria-pressed={value === ''} title={t('website::admin.ui.icon_none')} onClick={() => onChange('')} className={cn('grid h-11 place-items-center rounded-[10px] border text-[12px] text-muted-foreground', value === '' ? 'border-green-700 bg-green-50' : 'border-line bg-surface')}>
                    {t('website::admin.ui.icon_none')}
                </button>
                {icons.map((icon) => (
                    <button key={icon.key} type="button" aria-pressed={value === icon.key} aria-label={label(icon.key)} title={label(icon.key)} onClick={() => onChange(icon.key)} className={cn('grid h-11 place-items-center rounded-[10px] border', value === icon.key ? 'border-green-700 bg-green-50 text-green-700' : 'border-line bg-surface text-ink')}>
                        <span className="ico-static size-6 [&>svg]:size-6" dangerouslySetInnerHTML={{ __html: icon.svg }} />
                    </button>
                ))}
            </div>
            {custom && (
                <p className="m-0 flex items-center gap-2 text-[13px] text-muted-foreground">
                    <span className="inline-flex size-8 items-center justify-center rounded-[10px] border border-green-700 bg-green-50 text-lg text-ink">{value}</span>
                    <CheckIcon className="size-4 text-green-700" />
                    {t('website::admin.ui.icon_custom')}
                </p>
            )}
        </div>
    );
}
