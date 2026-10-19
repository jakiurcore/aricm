<?php

namespace Modules\StudentPortal;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;

/**
 * StudentPortal: the signed-in student's pages (guard "student"), served at /portal. A consumer module: it reads
 * everything through other modules' public services and contracts, and builds its navigation from ProvidesPortalMenu.
 */
class StudentPortalModule implements Module, ProvidesRoutes, ProvidesTranslations, ProvidesViews
{
    public function name(): string
    {
        return 'student-portal';
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
