<?php

namespace Modules\Gallery;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Contracts\ProvidesPermissions;
use Modules\Core\Support\HomepageSection;
use Modules\Gallery\Models\Photo;

/**
 * Gallery: albums, multi-upload with thumbnails, drag-to-sort, the public gallery with a lightbox and the homepage
 * preview. Depends on Core only.
 */
class GalleryModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesHomepageSections, ProvidesMigrations, ProvidesPermissions, ProvidesRoutes, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'gallery';
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
        return ['gallery.view', 'gallery.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return [];
    }

    public function homepageSections(): array
    {
        $photos = Photo::query()->whereHas('album', fn ($query) => $query->where('is_published', true))->orderByDesc('id')->limit(8)->get();

        return [new HomepageSection('gallery::home.preview', ['photos' => $photos], 70)];
    }
}
