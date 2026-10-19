import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import { HolidayList, WeeklyHolidays, type Holiday } from '@academic/js/components/Holidays';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { Panel, PanelTitle, ToggleRow } from '@core/js/components/Surface';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
import { can } from '@core/js/permissions';

type Settings = Record<string, number | boolean | string>;

const k = (key: string, replacements?: Record<string, string | number>) => t(`diary::admin.set.${key}`, replacements);
const message = (error: unknown) => (error instanceof ApiError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : t('core::admin.common.error'));

/** The design's "ডায়েরি সেটিংস": edit window and visibility, weekly days off, the holiday calendar, one save button. */
export function SettingsPage() {
    const manage = can('diary.manage');
    const [values, setValues] = useState<Settings | null>(null);
    const [failed, setFailed] = useState(false);
    const [weekly, setWeekly] = useState<string[]>([]);
    const [holidays, setHolidays] = useState<Holiday[]>([]);
    const year = new Date().getFullYear();

    const load = useCallback(async () => {
        setFailed(false);

        try {
            const [settings, calendar] = await Promise.all([api.get<{ data: Settings }>('/diary/settings'), api.get<{ data: Holiday[]; weekly: string[] }>('/academic/holidays', { year })]);
            setValues(settings.data);
            setWeekly(calendar.weekly);
            setHolidays(calendar.data);
        } catch {
            setFailed(true);
        }
    }, [year]);

    useEffect(() => {
        void load();
    }, [load]);

    if (failed) {
        return <ErrorState onRetry={() => void load()} />;
    }

    if (!values) {
        return <LoadingState />;
    }

    const set = (key: string, value: number | boolean | string) => setValues({ ...values, [key]: value });
    const save = async () => {
        try {
            setValues((await api.put<{ data: Settings }>('/diary/settings', values)).data);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(message(error));
        }
    };

    const editOptions = ['same', '2h', 'next9', 'none'].map((value) => ({ value, label: k(`edit_options.${value}`) }));

    if (values.edit_window === 'hours') {
        editOptions.unshift({ value: 'hours', label: k('edit_options.hours', { hours: bnDigits(Number(values.edit_window_hours)) }) });
    }

    const saveWeekly = async (days: string[]) => {
        setWeekly(days);

        try {
            await api.put('/academic/holidays/weekly', { days });
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(message(error));
        }
    };

    const addHoliday = async (draft: { date: string; title_bn: string }) => {
        try {
            await api.post('/academic/holidays', draft);
            await load();

            return true;
        } catch (error) {
            toast.error(message(error));

            return false;
        }
    };

    return (
        <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))' }}>
            <Panel className="gap-3.5">
                <PanelTitle>{k('visibility')}</PanelTitle>
                <Field label={k('edit_window')} htmlFor="set-window">
                    <NativeSelect id="set-window" disabled={!manage} value={String(values.edit_window)} onChange={(event) => set('edit_window', event.target.value)} options={editOptions} />
                </Field>
                <ToggleRow label={k('future')} hint={k('future_hint')} on={Boolean(values.student_sees_future)} disabled={!manage} onToggle={() => set('student_sees_future', !values.student_sees_future)} />
                <label className="grid gap-1.5 text-sm font-semibold">
                    {k('max_size')}
                    <strong className="text-lg text-green-700">{k('mb', { count: bnDigits(Number(values.max_attachment_mb)) })}</strong>
                    <input type="range" min={1} max={20} step={1} disabled={!manage} value={Number(values.max_attachment_mb)} onChange={(event) => set('max_attachment_mb', Number(event.target.value))} className="min-h-8 w-full accent-green-700" />
                </label>
            </Panel>

            <WeeklyHolidays weekly={weekly} onChange={(days) => void saveWeekly(days)} disabled={!manage} />

            <HolidayList holidays={holidays} manage={can('academic.manage')} onAdd={addHoliday} onRemove={(holiday) => void api.delete(`/academic/holidays/${holiday.id}`).then(load)} />

            <Panel className="gap-3.5">
                <PanelTitle>{k('others')}</PanelTitle>
                <ToggleRow label={t('diary::admin.settings.enabled')} on={Boolean(values.enabled)} disabled={!manage} onToggle={() => set('enabled', !values.enabled)} />
                <Field label={t('diary::admin.settings.backdate_days')} htmlFor="set-backdate">
                    <Input id="set-backdate" type="number" min={0} disabled={!manage} value={Number(values.backdate_days)} onChange={(event) => set('backdate_days', Number(event.target.value))} />
                </Field>
                <Field label={t('diary::admin.settings.max_attachments')} htmlFor="set-attachments">
                    <Input id="set-attachments" type="number" min={0} disabled={!manage} value={Number(values.max_attachments)} onChange={(event) => set('max_attachments', Number(event.target.value))} />
                </Field>
                <ToggleRow label={t('diary::admin.settings.sms_enabled')} on={Boolean(values.sms_enabled)} disabled={!manage} onToggle={() => set('sms_enabled', !values.sms_enabled)} />
                <ToggleRow label={t('diary::admin.settings.sms_teacher_reminder')} hint={t('diary::admin.settings.sms_hint')} on={Boolean(values.sms_teacher_reminder)} disabled={!manage} onToggle={() => set('sms_teacher_reminder', !values.sms_teacher_reminder)} />
            </Panel>

            {manage && (
                <div className="col-span-full flex justify-end">
                    <Button size="lg" className="px-6" onClick={() => void save()}>
                        {k('save')}
                    </Button>
                </div>
            )}
        </div>
    );
}
