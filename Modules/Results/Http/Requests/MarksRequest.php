<?php

namespace Modules\Results\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('results.enter_marks');
    }

    /**
     * Shape only: the service checks every id against the exam, class and roster.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.student_id' => ['required', 'integer'],
            'entries.*.exam_subject_id' => ['required', 'integer'],
            'entries.*.component_id' => ['nullable', 'integer'],
            'entries.*.obtained' => ['nullable', 'numeric'],
            'entries.*.is_absent' => ['boolean'],
        ];
    }
}
