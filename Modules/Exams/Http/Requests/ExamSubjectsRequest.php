<?php

namespace Modules\Exams\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Services\AcademicDirectory;

class ExamSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subjects' => ['present', 'array'],
            'subjects.*.subject_id' => ['required', 'integer', 'distinct', Rule::in(app(AcademicDirectory::class)->subjectsOf($this->route('class')->id)->pluck('id')->all())],
            'subjects.*.full_marks' => ['required_without:subjects.*.components', 'nullable', 'integer', 'between:1,1000'],
            'subjects.*.pass_marks' => ['required', 'integer', 'min:0'],
            'subjects.*.include_in_gpa' => ['boolean'],
            'subjects.*.is_optional' => ['boolean'],
            'subjects.*.components' => ['nullable', 'array', 'max:6'],
            'subjects.*.components.*.id' => ['nullable', 'integer'],
            'subjects.*.components.*.name_bn' => ['required', 'string', 'max:100'],
            'subjects.*.components.*.name_en' => ['nullable', 'string', 'max:100'],
            'subjects.*.components.*.full_marks' => ['required', 'integer', 'between:1,1000'],
            'subjects.*.components.*.pass_marks' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Pass marks may never exceed full marks, for the subject or for any of its components.
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('subjects', []) as $index => $subject) {
                $components = $subject['components'] ?? [];
                $full = $components !== [] ? array_sum(array_column($components, 'full_marks')) : (int) ($subject['full_marks'] ?? 0);

                if ((int) ($subject['pass_marks'] ?? 0) > $full) {
                    $validator->errors()->add("subjects.{$index}.pass_marks", __('exams::messages.pass_above_full'));
                }

                foreach ($components as $position => $component) {
                    if ((int) ($component['pass_marks'] ?? 0) > (int) ($component['full_marks'] ?? 0)) {
                        $validator->errors()->add("subjects.{$index}.components.{$position}.pass_marks", __('exams::messages.pass_above_full'));
                    }
                }
            }
        }];
    }
}
