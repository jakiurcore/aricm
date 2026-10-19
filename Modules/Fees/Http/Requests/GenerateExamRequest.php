<?php

namespace Modules\Fees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateExamRequest extends FormRequest
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
            'exam_id' => ['required', 'integer', Rule::exists('exams', 'id')],
            'confirm' => ['boolean'],
        ];
    }
}
