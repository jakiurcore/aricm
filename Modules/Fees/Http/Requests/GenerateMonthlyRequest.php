<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMonthlyRequest extends FormRequest
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
            'month' => ['required', 'date_format:Y-m'],
            'class_ids' => ['nullable', 'array'],
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')],
            'confirm' => ['boolean'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
