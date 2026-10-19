<?php

namespace Modules\Exams;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Support\MenuItem;

/**
 * Exams: exams (free-text names), their classes, per-class subject setup with marks and components, routines, and
 * admit card release/eligibility. Defines Contracts\ProvidesAdmitCardEligibility (Fees implements it).
 * Depends on Core, Accounts, Academic, Students.
 */
class ExamsModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesPortalMenu, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
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

    public function views(): string
    {
        return 'resources/views';
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

    public function portalMenu(Authenticatable $student): array
    {
        return [
            new MenuItem(__('exams::messages.portal.routine'), 'portal.routine', 'calendar', 10, true),
            new MenuItem(__('exams::messages.portal.admit'), 'portal.admit', 'card', 30),
        ];
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
