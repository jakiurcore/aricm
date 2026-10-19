import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';

import { api, ApiError } from '../api';
import { Field } from '../components/Field';
import { t } from '../i18n';

type Settings = Record<string, any>;

const text = (key: string) => t(`core::admin.settings.fields.${key}`);

export function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        void api.get<{ data: Settings }>('/core/settings').then((response) => setValues(response.data));
    }, []);

    if (!values) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const set = (key: string, value: unknown) => setValues({ ...values, [key]: value });

    const input = (key: string, props: { type?: string } = {}) => (
        <Field label={text(key)} htmlFor={key} error={errors[key]?.[0]}>
            <Input id={key} type={props.type} value={values[key] ?? ''} onChange={(event) => set(key, event.target.value)} />
        </Field>
    );

    const area = (key: string) => (
        <Field label={text(key)} htmlFor={key} error={errors[key]?.[0]}>
            <Textarea id={key} rows={4} value={values[key] ?? ''} onChange={(event) => set(key, event.target.value)} />
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
        <Field label={text(key)} htmlFor={key}>
            <div className="flex items-center gap-3">
                {values[`${key}_url`] && <img src={values[`${key}_url`]} alt="" className="h-14 w-14 rounded-md border object-contain" />}
                <Input id={key} type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => void upload(key, event.target.files?.[0])} />
            </div>
        </Field>
    );

    const phones: string[] = values.phones ?? [];

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
        <div className="max-w-3xl space-y-6">
            <h1 className="text-2xl font-semibold tracking-tight">{t('core::admin.settings.title')}</h1>

            <Card>
                <CardHeader>
                    <CardTitle>{t('core::admin.settings.sections.identity')}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {input('institute_name_bn')}
                    {input('institute_name_en')}
                    {input('institute_name_ar')}
                    {input('established_year', { type: 'number' })}
                    {input('tagline_bn')}
                    {input('tagline_en')}
                    {fileField('logo')}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('core::admin.settings.sections.contact')}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {input('address_bn')}
                    {input('address_en')}
                    {input('email', { type: 'email' })}
                    <Field label={text('phones')} htmlFor="phones" hint={text('phones_hint')}>
                        <Input id="phones" value={phones.join(', ')} onChange={(event) => set('phones', event.target.value.split(',').map((phone) => phone.trim()))} />
                    </Field>
                    {input('social_facebook')}
                    {input('social_youtube')}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('core::admin.settings.sections.principal')}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {input('principal_name_bn')}
                    {input('principal_name_en')}
                    <div className="sm:col-span-2">{area('principal_message_bn')}</div>
                    <div className="sm:col-span-2">{area('principal_message_en')}</div>
                    {fileField('principal_signature')}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>{t('core::admin.settings.sections.rules')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {area('admit_card_rules_bn')}
                    {area('admit_card_rules_en')}
                    <label className="flex items-center gap-3 text-sm">
                        <Switch checked={Boolean(values.result_requires_fee_paid)} onCheckedChange={(checked) => set('result_requires_fee_paid', checked)} />
                        {text('result_requires_fee_paid')}
                    </label>
                </CardContent>
            </Card>

            <Button onClick={() => void save()} disabled={saving}>
                {saving ? t('core::admin.common.saving') : t('core::admin.common.save')}
            </Button>
        </div>
    );
}
