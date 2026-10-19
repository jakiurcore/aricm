<?php

namespace Modules\Exams;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;

/**
 * Exams: exams (free-text names), their classes, per-class subject setup with marks and components, routines, and
 * admit card release/eligibility. Defines Contracts\ProvidesAdmitCardEligibility (Fees implements it).
 * Depends on Core, Accounts, Academic, Students.
 */
class ExamsModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesTranslations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'exams';
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
        return ['exams.view', 'exams.manage', 'exams.admit_card'];
    }

    public function defaultRolePermissions(): array
    {
        return ['accountant' => ['exams.view']];
    }
}
