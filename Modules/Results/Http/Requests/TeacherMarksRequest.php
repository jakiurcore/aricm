<?php

namespace Modules\Results\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeacherMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('marks.enter_assigned');
    }

    /**
     * Shape only (and only on save): the service checks every id against the teacher's scope.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        if (! $this->isMethod('PUT')) {
            return [];
        }

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
