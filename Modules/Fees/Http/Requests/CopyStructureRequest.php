<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyStructureRequest extends FormRequest
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
            'from_session_id' => ['required', 'integer', Rule::exists('academic_sessions', 'id')],
            'to_session_id' => ['required', 'integer', 'different:from_session_id', Rule::exists('academic_sessions', 'id')],
        ];
    }
}
