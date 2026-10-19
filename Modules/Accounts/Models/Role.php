<?php

namespace Modules\Accounts\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Roles the application relies on. They cannot be renamed or deleted from the admin.
     *
     * @var list<string>
     */
    public const array SYSTEM = ['super_admin', 'admin', 'teacher', 'accountant'];

    public function isSystem(): bool
    {
        return in_array($this->name, self::SYSTEM, true);
    }
}
