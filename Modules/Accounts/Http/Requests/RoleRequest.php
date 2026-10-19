<?php

namespace Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounts\Models\Role;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            ? $this->user()->can('update', $role)
            : $this->user()->can('create', Role::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [
                $role ? ($role->isSystem() ? 'prohibited' : 'sometimes') : 'required',
                'string', 'max:60', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')->ignore($role),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }
}
