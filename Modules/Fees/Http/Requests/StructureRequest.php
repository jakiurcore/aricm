<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Students\Enums\ResidencyType;

class StructureRequest extends FormRequest
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
            'session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'rows' => ['present', 'array', 'max:2000'],
            'rows.*.class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'rows.*.fee_head_id' => ['required', 'integer', Rule::exists('fee_heads', 'id')],
            'rows.*.residency_type' => ['nullable', Rule::enum(ResidencyType::class)],
            'rows.*.amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ];
    }
}
