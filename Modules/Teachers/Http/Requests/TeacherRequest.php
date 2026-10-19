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
            'status' => ['sometimes', Rule::in(['active', 'leave', 'inactive'])],
            'joined_on' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Optional login, created together with the teacher: either with a chosen password, or "create_login" for a temporary one sent by SMS.
            'create_login' => ['sometimes', 'boolean'],
            'email' => [Rule::requiredIf(fn () => $this->filled('password') || $this->boolean('create_login')), 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('teacher')?->user_id)],
            'password' => ['nullable', Password::min(8)],
        ];
    }
}
