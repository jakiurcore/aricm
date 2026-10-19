<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('class'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subjects' => ['present', 'array'],
            'subjects.*.subject_id' => ['required', 'integer', 'distinct', Rule::exists('subjects', 'id')],
            'subjects.*.is_optional' => ['boolean'],
        ];
    }
}
