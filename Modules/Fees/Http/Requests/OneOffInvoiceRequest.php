<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OneOffInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fees.generate');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.fee_head_id' => ['required', 'integer', Rule::exists('fee_heads', 'id')],
            'items.*.amount' => ['nullable', 'numeric', 'gt:0', 'max:10000000'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
