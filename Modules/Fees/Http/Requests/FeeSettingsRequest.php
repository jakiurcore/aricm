<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Fees\Enums\PaymentMethod;

class FeeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fees.settings');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'due_day' => ['required', 'integer', 'between:1,28'],
            'late_fine_amount' => ['required', 'numeric', 'min:0', 'max:100000'],
            'late_fine_after_days' => ['required', 'integer', 'between:0,60'],
            'late_fine_mode' => ['required', Rule::in(['once', 'per_month'])],
            'auto_generate_monthly' => ['boolean'],
            'manual_methods' => ['array', 'max:6'],
            'manual_methods.*.method' => ['required', Rule::in(array_map(fn (PaymentMethod $method) => $method->value, PaymentMethod::manual()))],
            'manual_methods.*.number' => ['nullable', 'string', 'max:40'],
            'manual_methods.*.account_type' => ['nullable', Rule::in(['personal', 'merchant', 'agent', 'bank'])],
            'manual_methods.*.instruction_bn' => ['nullable', 'string', 'max:500'],
            'manual_methods.*.instruction_en' => ['nullable', 'string', 'max:500'],
            'gateway_enabled' => ['boolean'],
            'gateway_driver' => ['required', Rule::in(['manual', 'sslcommerz', 'bkash'])],
            'admit_card_requires' => ['required', Rule::in(['exam_invoice', 'all_dues_to_exam_month', 'none'])],
            'result_requires_no_dues' => ['boolean'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'sms_enabled' => ['boolean'],
        ];
    }
}
