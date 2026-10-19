<?php

namespace Modules\Admission;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Admission\Services\AdmissionSettings;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Support\HomepageSection;

/**
 * Admission: the admission banner and page, the inquiry form (throttled, honeypot) and the inquiries list.
 * Depends on Core only.
 */
class AdmissionModule implements Module, ProvidesApiRoutes, ProvidesConfig, ProvidesDashboardScripts, ProvidesHomepageSections, ProvidesMigrations, ProvidesPermissions, ProvidesRoutes, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'admission';
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
        return ['modules.admission' => 'config/admission.php'];
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
        return ['admission.view', 'admission.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return [];
    }

    public function homepageSections(): array
    {
        return [new HomepageSection('admission::home.banner', ['settings' => app(AdmissionSettings::class)], 10)];
    }
}
