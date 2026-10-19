import { XIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError } from '@core/js/api';
import { PageTitle, Panel } from '@core/js/components/design';
import { Field } from '@core/js/components/Field';
import { NativeSelect } from '@core/js/components/NativeSelect';
import { ErrorState, LoadingState } from '@core/js/components/States';
import { t } from '@core/js/i18n';
import { bnDigits } from '@core/js/number';
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
const COLORS: Record<string, string> = { bkash: '#E2136E', nagad: '#EC1C24', rocket: '#8C3494', bank: '#16307A' };
const heading = 'm-0 text-[17px] font-bold';

/** A design toggle row: label and hint on the left, the 48x28 switch on the right, the whole row clickable. */
function ToggleRow({ label, hint, checked, disabled, onChange }: { label: string; hint?: string; checked: boolean; disabled?: boolean; onChange: (checked: boolean) => void }) {
    const flip = () => !disabled && onChange(!checked);

    return (
        <div
            role="switch"
            aria-checked={checked}
            aria-label={label}
            aria-disabled={disabled}
            tabIndex={disabled ? -1 : 0}
            onClick={flip}
            onKeyDown={(event) => {
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    flip();
                }
            }}
            className="flex min-h-14 w-full cursor-pointer items-center justify-between gap-3 rounded-[14px] border border-line bg-surface-2 px-3 py-2 text-left text-ink outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 aria-disabled:cursor-not-allowed aria-disabled:opacity-60"
        >
            <span className="grid leading-[1.45]">
                <strong className="text-[15px]">{label}</strong>
                {hint && <span className="text-[13px] text-muted-foreground">{hint}</span>}
            </span>
            <span aria-hidden="true" className="flex flex-none items-center rounded-full p-[3px]" style={{ width: 48, height: 28, background: checked ? 'var(--green-700)' : 'var(--line-strong)', justifyContent: checked ? 'flex-end' : 'flex-start' }}>
                <span className="rounded-full bg-white shadow-sm" style={{ width: 22, height: 22 }} />
            </span>
        </div>
    );
}

/** Fee settings as in the design: billing and late fee, mobile-banking numbers, receipt, and one save pill underneath. */
export function SettingsPage() {
    const [values, setValues] = useState<Settings | null>(null);
    const [failed, setFailed] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const readOnly = !can('fees.settings');

    useEffect(() => {
        api.get<{ data: Settings }>('/fees/settings')
            .then((response) => setValues({ ...response.data, late_fine_amount: String(response.data.late_fine_amount / 100) }))
            .catch(() => setFailed(true));
    }, []);

    if (failed) {
        return <ErrorState />;
    }

    if (!values) {
        return <LoadingState rows={2} height={220} />;
    }

    const set = (name: string, value: unknown) => setValues({ ...values, [name]: value });
    const methods: Method[] = values.manual_methods ?? [];
    const setMethod = (index: number, patch: Partial<Method>) => set('manual_methods', methods.map((method, at) => (at === index ? { ...method, ...patch } : method)));
    const blocksAdmit = values.admit_card_requires !== 'none';

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

    return (
        <div className="grid items-start gap-4" style={{ gridTemplateColumns: 'repeat(auto-fit, minmax(min(100%, 400px), 1fr))' }}>
            <PageTitle>{k('title')}</PageTitle>

            <Panel>
                <h3 className={heading}>{k('billing')}</h3>
                <div className="grid grid-cols-3 gap-2.5">
                    <Field label={k('due_day')} htmlFor="s-due" error={errors.due_day?.[0]}>
                        <NativeSelect id="s-due" disabled={readOnly} value={values.due_day ?? ''} onChange={(event) => set('due_day', event.target.value)} options={Array.from({ length: 28 }, (_, index) => ({ value: index + 1, label: `${bnDigits(index + 1)} ${k("day_suffix")}`.trim() }))} />
                    </Field>
                    <Field label={k('late_fine_amount')} htmlFor="s-fine" error={errors.late_fine_amount?.[0]}>
                        <Input id="s-fine" type="number" min={0} disabled={readOnly} style={{ fontFamily: 'Poppins, sans-serif' }} value={values.late_fine_amount ?? ''} onChange={(event) => set('late_fine_amount', event.target.value)} />
                    </Field>
                    <Field label={k('late_fine_after_days')} htmlFor="s-after" error={errors.late_fine_after_days?.[0]}>
                        <Input id="s-after" type="number" min={0} disabled={readOnly} style={{ fontFamily: 'Poppins, sans-serif' }} value={values.late_fine_after_days ?? ''} onChange={(event) => set('late_fine_after_days', event.target.value)} />
                    </Field>
                </div>
                <Field label={k('late_fine_mode')} htmlFor="s-mode">
                    <NativeSelect id="s-mode" disabled={readOnly} value={values.late_fine_mode} onChange={(event) => set('late_fine_mode', event.target.value)} options={['once', 'per_month'].map((value) => ({ value, label: k(`mode_${value}`) }))} />
                </Field>
                <ToggleRow label={k('auto_generate_monthly')} hint={k('auto_generate_hint')} checked={Boolean(values.auto_generate_monthly)} disabled={readOnly} onChange={(checked) => set('auto_generate_monthly', checked)} />
                <ToggleRow label={k('block_admit')} hint={k('block_admit_hint')} checked={blocksAdmit} disabled={readOnly} onChange={(checked) => set('admit_card_requires', checked ? 'exam_invoice' : 'none')} />
                {blocksAdmit && (
                    <Field label={k('admit_card_requires')} htmlFor="s-admit">
                        <NativeSelect id="s-admit" disabled={readOnly} value={values.admit_card_requires} onChange={(event) => set('admit_card_requires', event.target.value)} options={['exam_invoice', 'all_dues_to_exam_month'].map((value) => ({ value, label: k(`admit_${value}`) }))} />
                    </Field>
                )}
                <ToggleRow label={k('result_requires_no_dues')} hint={k('block_result_hint')} checked={Boolean(values.result_requires_no_dues)} disabled={readOnly} onChange={(checked) => set('result_requires_no_dues', checked)} />
                <ToggleRow label={k('sms_enabled')} checked={Boolean(values.sms_enabled)} disabled={readOnly} onChange={(checked) => set('sms_enabled', checked)} />
            </Panel>

            <Panel>
                <h3 className={heading}>{k('methods')}</h3>
                {methods.map((method, index) => (
                    <div key={index} className="grid gap-2 rounded-[14px] border border-line p-3" style={{ borderLeft: `6px solid ${COLORS[method.method] ?? 'var(--line-strong)'}` }}>
                        <div className="flex items-center gap-2">
                            <NativeSelect className="flex-1 font-bold" aria-label={k('method')} disabled={readOnly} value={method.method} onChange={(event) => setMethod(index, { method: event.target.value })} options={['bkash', 'nagad', 'rocket', 'bank'].map((value) => ({ value, label: t(`fees::admin.payment_method.${value}`) }))} />
                            {!readOnly && (
                                <Button variant="outline" size="icon" className="size-11 shrink-0 text-danger" aria-label={t('core::admin.common.delete')} onClick={() => set('manual_methods', methods.filter((_, at) => at !== index))}>
                                    <XIcon />
                                </Button>
                            )}
                        </div>
                        <div className="grid gap-2" style={{ gridTemplateColumns: 'minmax(0, 1.4fr) minmax(0, 1fr)' }}>
                            <Input aria-label={k('number')} disabled={readOnly} style={{ fontFamily: 'Poppins, sans-serif' }} value={method.number ?? ''} onChange={(event) => setMethod(index, { number: event.target.value })} />
                            <NativeSelect aria-label={k('account_type')} disabled={readOnly} value={method.account_type ?? 'personal'} onChange={(event) => setMethod(index, { account_type: event.target.value })} options={['personal', 'merchant', 'agent', 'bank'].map((value) => ({ value, label: k(`account_${value}`) }))} />
                        </div>
                        <Textarea rows={2} className="text-sm leading-[1.55]" aria-label={k('instruction_bn')} placeholder={k('instruction_bn')} disabled={readOnly} value={method.instruction_bn ?? ''} onChange={(event) => setMethod(index, { instruction_bn: event.target.value })} />
                        <Textarea rows={2} className="text-sm leading-[1.55]" aria-label={k('instruction_en')} placeholder={k('instruction_en')} disabled={readOnly} value={method.instruction_en ?? ''} onChange={(event) => setMethod(index, { instruction_en: event.target.value })} />
                    </div>
                ))}
                {!readOnly && (
                    <Button variant="outline" className="justify-self-start border-dashed border-green-500 bg-green-50 text-green-700 hover:bg-green-50" onClick={() => set('manual_methods', [...methods, { method: 'bkash', number: '', account_type: 'personal', instruction_bn: '', instruction_en: '' }])}>
                        + {k('add_method')}
                    </Button>
                )}
            </Panel>

            <Panel>
                <h3 className={heading}>{k('receipt')}</h3>
                <Field label={k('receipt_footer')} htmlFor="s-footer">
                    <Textarea id="s-footer" rows={3} disabled={readOnly} className="leading-[1.6]" value={values.receipt_footer ?? ''} onChange={(event) => set('receipt_footer', event.target.value)} />
                </Field>
            </Panel>

            <Panel>
                <h3 className={heading}>{k('online')}</h3>
                <ToggleRow label={k('gateway_enabled')} checked={Boolean(values.gateway_enabled)} disabled={readOnly} onChange={(checked) => set('gateway_enabled', checked)} />
                <Field label={k('gateway_driver')} htmlFor="s-driver" hint={k('gateway_hint')}>
                    <NativeSelect id="s-driver" disabled={readOnly} value={values.gateway_driver} onChange={(event) => set('gateway_driver', event.target.value)} options={['manual', 'sslcommerz', 'bkash'].map((value) => ({ value, label: value }))} />
                </Field>
            </Panel>

            {!readOnly && (
                <div className="flex justify-end" style={{ gridColumn: '1 / -1' }}>
                    <Button size="lg" onClick={() => void save()}>
                        {k('save')}
                    </Button>
                </div>
            )}
        </div>
    );
}
