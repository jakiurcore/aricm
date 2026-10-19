import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { t } from '@core/js/i18n';
import { can } from '@core/js/permissions';

interface Method {
    method: string;
    number: string;
    account_type: string;
    instruction_bn: string;
    instruction_en: string;
}

type Settings = Record<string, any>;

const k = (key: string) => t(`fees::admin.settings.${key}`);

export function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const readOnly = !can('fees.settings');

    useEffect(() => {
        void api.get<{ data: Settings }>('/fees/settings').then((response) => setValues({ ...response.data, late_fine_amount: String(response.data.late_fine_amount / 100) }));
    }, []);

    if (!values) {
        return <p className="text-muted-foreground">{t('core::admin.common.loading')}</p>;
    }

    const set = (name: string, value: unknown) => setValues({ ...values, [name]: value });
    const methods: Method[] = values.manual_methods ?? [];
    const setMethod = (index: number, patch: Partial<Method>) => set('manual_methods', methods.map((method, at) => (at === index ? { ...method, ...patch } : method)));

    const save = async () => {
        setErrors({});

        try {
            await api.put('/fees/settings', { ...values, manual_methods: methods.filter((method) => method.method) });
            toast.success(t('core::admin.common.saved'));
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
            }
            toast.error(t('core::admin.common.error'));
        }
    };

    const text = (name: string, type = 'text') => (
        <Field label={k(name)} htmlFor={`s-${name}`} error={errors[name]?.[0]} hint={t(`fees::admin.settings.${name}_hint`) === `fees::admin.settings.${name}_hint` ? undefined : k(`${name}_hint`)}>
            <Input id={`s-${name}`} type={type} disabled={readOnly} value={values[name] ?? ''} onChange={(event) => set(name, event.target.value)} />
        </Field>
    );

    const toggle = (name: string) => (
        <label className="flex items-center gap-3 text-sm">
            <Switch disabled={readOnly} checked={Boolean(values[name])} onCheckedChange={(checked) => set(name, checked)} />
            {k(name)}
        </label>
    );

    return (
        <div className="max-w-3xl space-y-6">
            <h1 className="text-2xl font-semibold tracking-tight">{k('title')}</h1>

            <Card>
                <CardHeader><CardTitle>{k('billing')}</CardTitle></CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    {text('due_day', 'number')}
                    {text('late_fine_amount')}
                    {text('late_fine_after_days', 'number')}
                    <Field label={k('late_fine_mode')} htmlFor="s-mode">
                        <NativeSelect id="s-mode" disabled={readOnly} value={values.late_fine_mode} onChange={(event) => set('late_fine_mode', event.target.value)} options={['once', 'per_month'].map((value) => ({ value, label: k(`mode_${value}`) }))} />
                    </Field>
                    <div className="sm:col-span-2">{toggle('auto_generate_monthly')}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>{k('rules')}</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    <Field label={k('admit_card_requires')} htmlFor="s-admit">
                        <NativeSelect id="s-admit" disabled={readOnly} value={values.admit_card_requires} onChange={(event) => set('admit_card_requires', event.target.value)} options={['exam_invoice', 'all_dues_to_exam_month', 'none'].map((value) => ({ value, label: k(`admit_${value}`) }))} />
                    </Field>
                    {toggle('result_requires_no_dues')}
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>{k('methods')}</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    {methods.map((method, index) => (
                        <div key={index} className="grid gap-2 rounded-lg border p-3 sm:grid-cols-3">
                            <NativeSelect disabled={readOnly} value={method.method} onChange={(event) => setMethod(index, { method: event.target.value })} options={['bkash', 'nagad', 'rocket', 'bank'].map((value) => ({ value, label: t(`fees::messages.payment_method.${value}`) }))} />
                            <Input disabled={readOnly} placeholder={k('number')} value={method.number ?? ''} onChange={(event) => setMethod(index, { number: event.target.value })} />
                            <NativeSelect disabled={readOnly} value={method.account_type ?? 'personal'} onChange={(event) => setMethod(index, { account_type: event.target.value })} options={['personal', 'merchant', 'agent', 'bank'].map((value) => ({ value, label: k(`account_${value}`) }))} />
                            <Textarea className="sm:col-span-3" rows={2} disabled={readOnly} placeholder={k('instruction_bn')} value={method.instruction_bn ?? ''} onChange={(event) => setMethod(index, { instruction_bn: event.target.value })} />
                            <Textarea className="sm:col-span-3" rows={2} disabled={readOnly} placeholder={k('instruction_en')} value={method.instruction_en ?? ''} onChange={(event) => setMethod(index, { instruction_en: event.target.value })} />
                            {!readOnly && <Button variant="ghost" size="sm" onClick={() => set('manual_methods', methods.filter((_, at) => at !== index))}>{t('core::admin.common.delete')}</Button>}
                        </div>
                    ))}
                    {!readOnly && <Button variant="outline" size="sm" onClick={() => set('manual_methods', [...methods, { method: 'bkash', number: '', account_type: 'personal', instruction_bn: '', instruction_en: '' }])}>+ {k('add_method')}</Button>}
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>{k('online')}</CardTitle></CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">{toggle('gateway_enabled')}</div>
                    <Field label={k('gateway_driver')} htmlFor="s-driver" hint={k('gateway_hint')}>
                        <NativeSelect id="s-driver" disabled={readOnly} value={values.gateway_driver} onChange={(event) => set('gateway_driver', event.target.value)} options={['manual', 'sslcommerz', 'bkash'].map((value) => ({ value, label: value }))} />
                    </Field>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>{k('notifications')}</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    {toggle('sms_enabled')}
                    <Field label={k('receipt_footer')} htmlFor="s-footer">
                        <Textarea id="s-footer" rows={2} disabled={readOnly} value={values.receipt_footer ?? ''} onChange={(event) => set('receipt_footer', event.target.value)} />
                    </Field>
                </CardContent>
            </Card>

            {!readOnly && <Button onClick={() => void save()}>{t('core::admin.common.save')}</Button>}
        </div>
    );
}
