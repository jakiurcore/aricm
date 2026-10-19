<?php

namespace Modules\Accounts\Database\Seeders;

use Illuminate\Database\Seeder;
use Laramod\Facades\Modules;
use Modules\Accounts\Models\Role;
use Modules\Core\Contracts\ProvidesPermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Collects permissions from every module that declares them and grants them to the roles.
     * Safe to run on every deploy: it only adds, so edits made in the admin are kept.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [];
        $roleGrants = [];

        foreach (Modules::providing(ProvidesPermissions::class) as $module) {
            $permissions = [...$permissions, ...$module->permissions()];

            foreach ($module->defaultRolePermissions() as $role => $names) {
                $roleGrants[$role] = [...($roleGrants[$role] ?? []), ...$names];
            }
        }

        $permissions = array_values(array_unique($permissions));

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Role::SYSTEM as $name) {
            Role::findOrCreate($name, 'web');
        }

        foreach (['super_admin', 'admin'] as $name) {
            Role::findByName($name, 'web')->givePermissionTo($permissions);
        }

        foreach ($roleGrants as $role => $names) {
            Role::findOrCreate($role, 'web')->givePermissionTo(array_unique($names));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
