<?php

namespace Modules\Core;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Database\Seeders\SettingsSeeder;

/**
 * Core: settings, locale handling, Bangla helpers, shared layouts/JS and the cross-module contracts
 * (see Contracts/). Every other module may depend on it; it depends on no module.
 */
class CoreModule implements Module, Ordered, ProvidesApiRoutes, ProvidesConfig, ProvidesDashboardScripts, ProvidesMigrations, ProvidesPermissions, ProvidesRoutes, ProvidesSeeders, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'core';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function order(): int
    {
        return 0;
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

    public function seeders(): array
    {
        return [SettingsSeeder::class];
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
        return ['modules.core' => 'config/core.php'];
    }

    public function viteEntries(): array
    {
        return ['resources/js/app.ts', 'resources/js/dashboard.tsx'];
    }

    public function dashboardScripts(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function permissions(): array
    {
        return ['settings.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return [];
    }
}
