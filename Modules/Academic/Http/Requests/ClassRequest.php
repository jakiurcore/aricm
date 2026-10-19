<?php

namespace Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Academic\Enums\Division;
use Modules\Academic\Models\SchoolClass;

class ClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = $this->route('class');

        return $class instanceof SchoolClass
            ? $this->user()->can('update', $class)
            : $this->user()->can('create', SchoolClass::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name_bn' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'numeric_order' => ['required', 'integer', 'between:0,100'],
            'division' => ['nullable', Rule::enum(Division::class)],
            'is_active' => ['boolean'],
        ];
    }
}
