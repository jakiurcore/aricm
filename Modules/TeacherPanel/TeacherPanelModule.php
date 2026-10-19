<?php

namespace Modules\TeacherPanel;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;

/**
 * TeacherPanel: the mobile-first pages a signed-in teacher uses, served at /teacher. A consumer module: the diary and
 * marks pages call the Diary and Results JSON endpoints, and the navigation is assembled from ProvidesTeacherMenu.
 */
class TeacherPanelModule implements Module, ProvidesRoutes, ProvidesTranslations, ProvidesViews
{
    public function name(): string
    {
        return 'teacher-panel';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function translations(): string
    {
        return 'lang';
    }
}
