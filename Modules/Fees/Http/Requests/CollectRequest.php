<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Fees\Enums\PaymentMethod;

class CollectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fees.collect');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'invoice_ids' => ['array', 'max:50'],
            'invoice_ids.*' => ['integer'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000'],
            'method' => ['required', Rule::in(array_diff(PaymentMethod::values(), ['gateway']))],
            'transaction_id' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
