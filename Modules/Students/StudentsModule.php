<?php

namespace Modules\Students;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;

/**
 * Students: records, enrollment history, ID generation, CSV import/export, promotion and the student auth provider.
 * Downstream modules read it through Services\StudentDirectory and extend profiles via Contracts\ProvidesStudentSummary.
 * Depends on Core, Accounts and Academic.
 */
class StudentsModule implements Module, ProvidesApiRoutes, ProvidesConfig, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesTranslations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'students';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
    }

    public function translations(): string
    {
        return 'lang';
    }

    public function config(): array
    {
        return ['auth.providers.students' => 'config/auth_provider.php'];
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
        return ['students.view', 'students.manage', 'students.import', 'students.promote', 'students.reset_password', 'students.id_card'];
    }

    public function defaultRolePermissions(): array
    {
        return ['accountant' => ['students.view']];
    }
}
