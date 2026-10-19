<?php

namespace Modules\Diary\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveEntryRequest extends FormRequest
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
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'lesson' => ['nullable', 'string', 'max:5000'],
            'homework' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:5000'],
            'homework_due_date' => ['nullable', 'date_format:Y-m-d'],
            'publish' => ['sometimes', 'boolean'],
        ];
    }
}
