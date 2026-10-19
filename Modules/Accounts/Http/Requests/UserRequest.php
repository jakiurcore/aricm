<?php

namespace Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounts\Models\Role;
use Modules\Accounts\Models\User;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? $this->user()->can('update', $target)
            : $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [$target ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name'), $this->mayAssign(...)],
        ];
    }

    /**
     * Only a super admin can hand out the super_admin role.
     */
    protected function mayAssign(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($value === User::SUPER_ADMIN && ! $this->user()->isSuperAdmin()) {
            $fail(__('accounts::admin.users.cannot_assign_super_admin'));
        }
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        return Role::query()->whereIn('name', $this->input('roles', []))->pluck('name')->all();
    }
}
