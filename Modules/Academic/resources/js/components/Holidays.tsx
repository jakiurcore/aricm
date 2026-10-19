import { useState } from 'react';

import { Input } from '@/components/ui/input';
import { ListCard } from '@core/js/components/Surface';
import { Panel, PanelTitle } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';

export interface Holiday {
    id: number;
    date: string;
    title_bn: string;
    title_en: string | null;
}

/** Saturday first, as the school week reads. */
export const WEEK_DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as const;

const k = (key: string) => t(`academic::admin.calendar.${key}`);

/** The design's "সাপ্তাহিক ছুটি": seven pills, the chosen days solid red with a tick. */
export function WeeklyHolidays({ weekly, onChange, disabled = false }: { weekly: string[]; onChange: (days: string[]) => void; disabled?: boolean }) {
    return (
        <Panel className="gap-3.5">
            <PanelTitle>{k('weekly')}</PanelTitle>
            <div className="flex flex-wrap gap-2">
                {WEEK_DAYS.map((day) => {
                    const on = weekly.includes(day);

                    return (
                        <button
                            key={day}
                            type="button"
                            disabled={disabled}
                            aria-pressed={on}
                            onClick={() => onChange(on ? weekly.filter((item) => item !== day) : [...weekly, day])}
                            className={`min-h-11 cursor-pointer rounded-full border px-3.5 font-bold disabled:cursor-not-allowed disabled:opacity-60 ${on ? 'border-red-600 bg-red-600 text-white' : 'border-line-strong bg-surface text-foreground'}`}
                        >
                            {on ? '✓ ' : ''}
                            {t(`academic::admin.calendar.days.${day}`)}
                        </button>
                    );
                })}
            </div>
            <span className="text-[13px] text-muted-foreground">{k('weekly_hint')}</span>
        </Panel>
    );
}

/** The design's "ছুটির ক্যালেন্ডার": date, name and a ✕ per row, then the add row. */
export function HolidayList({ holidays, onRemove, onAdd, manage = true, header }: { holidays: Holiday[]; onRemove: (holiday: Holiday) => void; onAdd: (draft: { date: string; title_bn: string }) => Promise<boolean>; manage?: boolean; header?: React.ReactNode }) {
    const [draft, setDraft] = useState({ date: '', title_bn: '' });

    return (
        <ListCard>
            <div className="flex items-center justify-between gap-2.5 bg-surface-2 px-[18px] py-3.5">
                <h3 className="m-0 text-[17px] leading-[1.65] font-bold">{k('calendar_title')}</h3>
                {header}
            </div>
            {holidays.length === 0 && <div className="border-t border-line px-[18px] py-4 text-sm text-muted-foreground">{t('core::admin.common.empty')}</div>}
            {holidays.map((holiday) => (
                <div key={holiday.id} className="flex items-center gap-2.5 border-t border-line px-[18px] py-2.5">
                    <span className="w-[120px] flex-none text-sm font-bold">{formatDate(holiday.date)}</span>
                    <span className="flex-1">{holiday.title_bn}</span>
                    {manage && (
                        <button type="button" aria-label={t('core::admin.common.delete')} onClick={() => onRemove(holiday)} className="size-9 cursor-pointer rounded-full border border-line bg-surface text-danger">
                            ✕
                        </button>
                    )}
                </div>
            ))}
            {manage && (
                <form
                    className="grid gap-2 border-t border-line px-[18px] py-3"
                    style={{ gridTemplateColumns: '150px minmax(0, 1fr) auto' }}
                    onSubmit={(event) => {
                        event.preventDefault();
                        void onAdd(draft).then((added) => added && setDraft({ date: '', title_bn: '' }));
                    }}
                >
                    <Input type="date" value={draft.date} onChange={(event) => setDraft({ ...draft, date: event.target.value })} aria-label={k('date')} />
                    <Input placeholder={k('holiday_name')} value={draft.title_bn} onChange={(event) => setDraft({ ...draft, title_bn: event.target.value })} aria-label={k('holiday_name')} />
                    <button type="submit" className="h-11 cursor-pointer rounded-xl border-0 bg-green-700 px-3.5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60" disabled={!draft.date || !draft.title_bn}>
                        {k('add_short')}
                    </button>
                </form>
            )}
        </ListCard>
    );
}
