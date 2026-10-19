<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fees.manage_structure');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rows' => ['present', 'array', 'max:100'],
            'rows.*.class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'rows.*.amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'rows.*.due_date' => ['nullable', 'date'],
        ];
    }
}
