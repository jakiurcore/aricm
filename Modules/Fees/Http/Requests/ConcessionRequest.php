<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Enums\ConcessionReason;

class ConcessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fees.concession');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'fee_head_id' => ['nullable', 'integer', Rule::exists('fee_heads', 'id')],
            'kind' => ['required', Rule::enum(ConcessionKind::class)],
            'value' => ['required', 'numeric', 'gt:0', $this->input('kind') === 'percent' ? 'max:100' : 'max:10000000'],
            'reason_type' => ['required', Rule::enum(ConcessionReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'valid_from_month' => ['required', 'date_format:Y-m'],
            'valid_to_month' => ['nullable', 'date_format:Y-m', 'after_or_equal:valid_from_month'],
        ];
    }
}
