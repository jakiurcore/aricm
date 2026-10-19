<?php

namespace Modules\Teachers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('teachers.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_bn' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            // Optional login, created together with the teacher.
            'email' => [Rule::requiredIf(fn () => $this->filled('password')), 'nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', Password::min(8)],
        ];
    }
}
