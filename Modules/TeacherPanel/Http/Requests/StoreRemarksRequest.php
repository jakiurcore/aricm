<?php

namespace Modules\TeacherPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Diary\Enums\RemarkKind;

class StoreRemarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('diary.write');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'student_ids' => ['required', 'array', 'min:1', 'max:100'],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
            'kind' => ['required', Rule::enum(RemarkKind::class)],
            'text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
