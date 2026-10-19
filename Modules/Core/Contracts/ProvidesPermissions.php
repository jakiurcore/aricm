<?php

namespace Modules\Core\Contracts;

/**
 * Implemented by modules that declare permissions. Accounts seeds them into the spatie roles.
 */
interface ProvidesPermissions
{
    /**
     * Every permission name the module checks, e.g. "diary.write".
     *
     * @return list<string>
     */
    public function permissions(): array;

    /**
     * The permissions each role gets by default, keyed by role name.
     * super_admin and admin always receive every permission, so list the other roles only.
     *
     * @return array<string, list<string>>
     */
    public function defaultRolePermissions(): array;
}
