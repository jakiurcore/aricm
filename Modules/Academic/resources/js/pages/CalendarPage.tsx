import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { t } from '@core/js/i18n';
import { formatDate } from '@core/js/number';
import { can } from '@core/js/permissions';

interface Holiday {
    id: number;
    date: string;
    title_bn: string;
    title_en: string | null;
}

const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
const k = (key: string) => t(`academic::admin.calendar.${key}`);

export function CalendarPage() {
    const [year, setYear] = useState(new Date().getFullYear());
    const [holidays, setHolidays] = useState<Holiday[]>([]);
    const [weekly, setWeekly] = useState<string[]>([]);
    const [draft, setDraft] = useState({ date: '', title_bn: '' });
    const manage = can('academic.manage');

    const load = useCallback(async () => {
        const response = await api.get<{ data: Holiday[]; weekly: string[] }>('/academic/holidays', { year });
        setHolidays(response.data);
        setWeekly(response.weekly);
    }, [year]);

    useEffect(() => {
        void load();
    }, [load]);

    const saveWeekly = async (days: string[]) => {
        setWeekly(days);
        await api.put('/academic/holidays/weekly', { days });
        toast.success(t('core::admin.common.saved'));
    };

    const add = async () => {
        try {
            await api.post('/academic/holidays', draft);
            setDraft({ date: '', title_bn: '' });
            await load();
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    return (
        <div className="max-w-3xl space-y-6">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            <section className="space-y-3 rounded-xl border bg-card p-4">
                <h2 className="font-semibold">{k('weekly')}</h2>
                <p className="text-sm text-muted-foreground">{k('weekly_hint')}</p>
                <div className="flex flex-wrap gap-4">
                    {DAYS.map((day) => (
                        <label key={day} className="flex items-center gap-2 text-sm">
                            <Checkbox disabled={!manage} checked={weekly.includes(day)} onCheckedChange={(checked) => void saveWeekly(checked ? [...weekly, day] : weekly.filter((item) => item !== day))} />
                            {t(`academic::admin.calendar.days.${day}`)}
                        </label>
                    ))}
                </div>
            </section>

            <section className="space-y-3">
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold">{k('holidays')}</h2>
                    <Input className="w-28" type="number" value={year} onChange={(event) => setYear(Number(event.target.value))} aria-label={k('year')} />
                </div>
                {manage && (
                    <div className="grid gap-2 sm:grid-cols-[10rem_1fr_auto]">
                        <Input type="date" value={draft.date} onChange={(event) => setDraft({ ...draft, date: event.target.value })} />
                        <Input placeholder={k('holiday_title')} value={draft.title_bn} onChange={(event) => setDraft({ ...draft, title_bn: event.target.value })} />
                        <Button disabled={!draft.date || !draft.title_bn} onClick={() => void add()}>
                            <PlusIcon /> {k('add')}
                        </Button>
                    </div>
                )}
                <ul className="divide-y rounded-xl border bg-card">
                    {holidays.length === 0 && <li className="p-4 text-sm text-muted-foreground">{t('core::admin.common.empty')}</li>}
                    {holidays.map((holiday) => (
                        <li key={holiday.id} className="flex items-center justify-between p-3 text-sm">
                            <span>
                                <strong>{formatDate(holiday.date)}</strong> — {holiday.title_bn}
                            </span>
                            {manage && (
                                <Button variant="ghost" size="icon" aria-label={t('core::admin.common.delete')} onClick={() => void api.delete(`/academic/holidays/${holiday.id}`).then(load)}>
                                    <Trash2Icon />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
