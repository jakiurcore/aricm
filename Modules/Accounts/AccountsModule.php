<?php

namespace Modules\Accounts;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Accounts\Console\CreateAdminCommand;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Core\Contracts\ProvidesPermissions;

/**
 * Accounts: admin users, roles/permissions (spatie), the `login` route laramod-admin requires,
 * and the admin-access gate. Depends on Core only.
 */
class AccountsModule implements Module, ProvidesApiRoutes, ProvidesCommands, ProvidesConfig, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesRoutes, ProvidesSeeders, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'accounts';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
    }

    /**
     * Must run after every other module has declared its permissions: see DatabaseSeeder.
     */
    public function commands(): array
    {
        return [CreateAdminCommand::class];
    }

    public function seeders(): array
    {
        return [RolesAndPermissionsSeeder::class];
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function translations(): string
    {
        return 'lang';
    }

    public function config(): array
    {
        return [
            'modules.accounts' => 'config/accounts.php',
            'auth.guards.student' => 'config/student_guard.php',
        ];
    }

    public function viteEntries(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function dashboardScripts(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function permissions(): array
    {
        return ['admin.access', 'users.manage', 'roles.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return ['accountant' => ['admin.access']];
    }
}
