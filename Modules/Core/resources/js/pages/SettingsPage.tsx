import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

import { api, ApiError } from '../api';
import { Field } from '../components/Field';
import { ErrorState, LoadingState } from '../components/States';
import { Panel, PanelTitle, ToggleRow } from '../components/Surface';
import { t } from '../i18n';

type Settings = Record<string, any>;

const text = (key: string, replacements?: Record<string, string | number>) => t(`core::admin.settings.fields.${key}`, replacements);

const PHONE_SLOTS = 5;

export function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    const [failed, setFailed] = useState(false);

    const load = () => {
        setFailed(false);
        api.get<{ data: Settings }>('/core/settings')
            .then((response) => setValues(response.data))
            .catch(() => setFailed(true));
    };

    useEffect(load, []);

    if (failed) {
        return <ErrorState onRetry={load} />;
    }

    if (!values) {
        return <LoadingState />;
    }

    const set = (key: string, value: unknown) => setValues({ ...values, [key]: value });

    const input = (key: string, props: { type?: string; hint?: string; step?: string } = {}) => (
        <Field label={text(key)} htmlFor={key} hint={props.hint} error={errors[key]?.[0]}>
            <Input id={key} type={props.type} step={props.step} inputMode={props.type === 'number' ? 'decimal' : undefined} value={values[key] ?? ''} onChange={(event) => set(key, event.target.value)} />
        </Field>
    );

    const area = (key: string) => (
        <Field label={text(key)} htmlFor={key} error={errors[key]?.[0]}>
            <Textarea id={key} rows={4} className="min-h-0 py-2.5 font-normal"  value={values[key] ?? ''} onChange={(event) => set(key, event.target.value)} />
        </Field>
    );

    const upload = async (key: 'logo' | 'principal_signature', file: File | undefined) => {
        if (!file) {
            return;
        }

        const form = new FormData();
        form.append('file', file);

        try {
            const response = await api.post<{ data: Settings }>(`/core/settings/files/${key}`, form);
            setValues((current) => ({ ...current, [`${key}_url`]: response.data[`${key}_url`] }));
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            toast.error(error instanceof ApiError ? (error.errors.file?.[0] ?? error.message) : t('core::admin.common.error'));
        }
    };

    const fileField = (key: 'logo' | 'principal_signature') => (
        <div className="grid gap-1">
            <span className="text-sm font-semibold">{text(key)}</span>
            <div className="flex items-center gap-3.5">
                {values[`${key}_url`] ? (
                    <img src={values[`${key}_url`]} alt="" className={`size-[72px] border border-line bg-white object-contain ${key === 'logo' ? 'rounded-full' : 'rounded-xl'}`} />
                ) : (
                    <span className={`grid size-[72px] place-items-center border border-line bg-surface-2 text-muted-foreground ${key === 'logo' ? 'rounded-full' : 'rounded-xl'}`}>🖼</span>
                )}
                <Button type="button" variant="outline" onClick={() => document.getElementById(`file-${key}`)?.click()}>
                    {t(key === 'logo' ? 'core::admin.settings.change' : 'core::admin.settings.change_signature')}
                </Button>
                <input id={`file-${key}`} type="file" hidden accept="image/png,image/jpeg,image/webp" onChange={(event) => void upload(key, event.target.files?.[0])} />
            </div>
        </div>
    );

    const phones: string[] = values.phones ?? [];
    const setPhone = (index: number, value: string) => {
        const next = [...phones];
        next[index] = value;
        set('phones', next);
    };

    const save = async () => {
        setSaving(true);
        setErrors({});

        try {
            const payload: Settings = { ...values, phones: phones.filter(Boolean) };
            delete payload.logo_url;
            delete payload.principal_signature_url;

            await api.put('/core/settings', payload);
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 380px), 1fr))' }}>
            <Panel className="gap-2.5">
                <PanelTitle>{t('core::admin.settings.sections.identity')}</PanelTitle>
                {input('institute_name_bn')}
                {input('institute_name_en')}
                {input('institute_name_ar')}
                {input('institute_short_name_bn')}
                {input('institute_short_name_en')}
                {input('institute_program_bn')}
                {input('institute_program_en')}
                {input('established_year', { type: 'number' })}
                {input('tagline_bn')}
                {input('tagline_en')}
                {input('address_bn')}
                {input('address_en')}
                {input('email', { type: 'email' })}
                {Array.from({ length: PHONE_SLOTS }, (_, index) => (
                    <Field key={index} label={text('phone_n', { n: index + 1 })} htmlFor={`phone-${index + 1}`} hint={index === 0 ? text('phones_hint') : undefined} error={errors[`phones.${index}`]?.[0]}>
                        <Input id={`phone-${index + 1}`} type="tel" value={phones[index] ?? ''} onChange={(event) => setPhone(index, event.target.value)} />
                    </Field>
                ))}
                {input('office_hours_bn')}
                {input('office_hours_en')}
                {input('map_lat', { type: 'number', step: 'any', hint: text('map_hint') })}
                {input('map_lng', { type: 'number', step: 'any' })}
            </Panel>

            <div className="grid gap-4">
                <Panel className="gap-2.5">
                    <PanelTitle>{t('core::admin.settings.sections.social')}</PanelTitle>
                    {input('social_facebook', { type: 'url' })}
                    {input('social_youtube', { type: 'url' })}
                    {input('social_instagram', { type: 'url' })}
                    {input('social_whatsapp', { hint: text('social_whatsapp_hint') })}
                </Panel>

                <Panel className="gap-2.5">
                    <PanelTitle>{t('core::admin.settings.sections.rules')}</PanelTitle>
                    {area('admit_card_rules_bn')}
                    {area('admit_card_rules_en')}
                    <ToggleRow label={text('result_requires_fee_paid')} on={Boolean(values.result_requires_fee_paid)} onToggle={() => set('result_requires_fee_paid', !values.result_requires_fee_paid)} />
                    <ToggleRow label={text('teacher_marks_entry_enabled')} on={Boolean(values.teacher_marks_entry_enabled)} onToggle={() => set('teacher_marks_entry_enabled', !values.teacher_marks_entry_enabled)} />
                </Panel>
            </div>

            <Panel className="gap-2.5">
                <PanelTitle>{t('core::admin.settings.sections.logo_principal')}</PanelTitle>
                {fileField('logo')}
                {input('principal_name_bn')}
                {input('principal_name_en')}
                {input('principal_title_bn')}
                {input('principal_title_en')}
                {area('principal_message_bn')}
                {area('principal_message_en')}
                {fileField('principal_signature')}
                <Button size="lg" onClick={() => void save()} disabled={saving}>
                    {saving ? t('core::admin.common.saving') : t('core::admin.settings.save_all')}
                </Button>
            </Panel>
        </div>
    );
}
