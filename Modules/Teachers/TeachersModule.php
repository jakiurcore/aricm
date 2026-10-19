<?php

namespace Modules\Teachers;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;

/**
 * Teachers: the teacher register, who teaches what in which section (assignments) and the TeacherScope service the
 * rest of the system asks "may this user act on this class/section/subject?". Defines ProvidesTeacherMenu, which the
 * teacher panel renders. Depends on Core, Accounts and Academic.
 */
class TeachersModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesTranslations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'teachers';
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
        return ['teachers.manage', 'teacher_assignments.manage', 'marks.enter_assigned'];
    }

    public function defaultRolePermissions(): array
    {
        return ['teacher' => ['marks.enter_assigned']];
    }
}
