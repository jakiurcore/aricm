<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\Settings;
use Modules\Fees\Support\Money;

/**
 * Typed access to the fee settings. They live in the Core settings table under a "fees_" prefix; defaults come from
 * config('modules.fees.defaults').
 */
class FeeSettings
{
    public function __construct(protected Settings $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings->get("fees_{$key}", config("modules.fees.defaults.{$key}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = [];

        foreach (array_keys(config('modules.fees.defaults')) as $key) {
            $values[$key] = $this->get($key);
        }

        // Shared with the Core settings page: hide published results from students who owe fees.
        $values['result_requires_no_dues'] = (bool) $this->settings->get('result_requires_fee_paid', false);

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        $prefixed = [];

        foreach ($values as $key => $value) {
            if ($key === 'result_requires_no_dues') {
                $this->settings->set('result_requires_fee_paid', (bool) $value);

                continue;
            }

            if (array_key_exists($key, config('modules.fees.defaults'))) {
                $prefixed["fees_{$key}"] = $value;
            }
        }

        $this->settings->setMany($prefixed);
    }

    public function dueDay(): int
    {
        return max(1, min(28, (int) $this->get('due_day')));
    }

    public function lateFine(): Money
    {
        return Money::poisha((int) $this->get('late_fine_amount'));
    }

    public function lateFineAfterDays(): int
    {
        return max(0, (int) $this->get('late_fine_after_days'));
    }

    public function lateFineMode(): string
    {
        return $this->get('late_fine_mode') === 'per_month' ? 'per_month' : 'once';
    }

    public function autoGenerate(): bool
    {
        return (bool) $this->get('auto_generate_monthly');
    }

    public function smsEnabled(): bool
    {
        return (bool) $this->get('sms_enabled');
    }

    public function admitCardRule(): string
    {
        $rule = (string) $this->get('admit_card_requires');

        return in_array($rule, ['exam_invoice', 'all_dues_to_exam_month', 'none'], true) ? $rule : 'exam_invoice';
    }

    public function resultRequiresNoDues(): bool
    {
        return (bool) $this->settings->get('result_requires_fee_paid', false);
    }

    public function gatewayEnabled(): bool
    {
        return (bool) $this->get('gateway_enabled') && $this->get('gateway_driver') !== 'manual';
    }
}
