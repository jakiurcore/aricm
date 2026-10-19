<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Section;

class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $section instanceof Section
            ? $this->user()->can('update', $section)
            : $this->user()->can('create', Section::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $section = $this->route('section');
        $classId = $section?->class_id ?? $this->input('class_id');

        return [
            'class_id' => [$section ? 'prohibited' : 'required', 'integer', Rule::exists('classes', 'id')],
            'name_bn' => ['required', 'string', 'max:100', Rule::unique('sections', 'name_bn')->where('class_id', $classId)->ignore($section)],
            'name_en' => ['nullable', 'string', 'max:100'],
        ];
    }
}
