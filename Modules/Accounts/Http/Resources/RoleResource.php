<?php

namespace Modules\Accounts\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounts\Models\Role;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $key = "accounts::admin.roles.names.{$this->name}";

        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => trans()->has($key) ? __($key) : $this->name,
            'is_system' => $this->isSystem(),
            'permissions' => $this->permissions->pluck('name')->sort()->values(),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
