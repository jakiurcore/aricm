<?php

namespace Modules\Academic;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;

/**
 * Academic: sessions, classes, sections, subjects and the class↔subject mapping.
 * Other modules read it through Services\AcademicDirectory. Depends on Core and Accounts.
 */
class AcademicModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesTranslations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'academic';
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
        return ['academic.view', 'academic.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return ['accountant' => ['academic.view']];
    }
}
