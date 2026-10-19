import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

import { AcademicTabs } from '../components/AcademicTabs';
import { HolidayList, WeeklyHolidays, type Holiday } from '../components/Holidays';

/** The weekly days off and the holiday list: what the diary's "next school day" is worked out from. */
export function CalendarPage() {
    const [year, setYear] = useState(new Date().getFullYear());
    const [holidays, setHolidays] = useState<Holiday[]>([]);
    const [weekly, setWeekly] = useState<string[]>([]);
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

    const add = async (draft: { date: string; title_bn: string }) => {
        try {
            await api.post('/academic/holidays', draft);
            await load();

            return true;
        } catch (error) {
            toast.error(error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

            return false;
        }
    };

    return (
        <div className="grid gap-3.5">
            <AcademicTabs />
            <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))' }}>
                <WeeklyHolidays weekly={weekly} onChange={(days) => void saveWeekly(days)} disabled={!manage} />
                <HolidayList
                    holidays={holidays}
                    manage={manage}
                    onAdd={add}
                    onRemove={(holiday) => void api.delete(`/academic/holidays/${holiday.id}`).then(load)}
                    header={<Input className="h-9 w-24" type="number" value={year} onChange={(event) => setYear(Number(event.target.value))} aria-label={t('academic::admin.calendar.year')} />}
                />
            </div>
        </div>
    );
}
