<?php

namespace Modules\Notices;

use Illuminate\Contracts\Auth\Authenticatable;
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
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Contracts\ProvidesTicker;
use Modules\Core\Support\HomepageSection;
use Modules\Core\Support\MenuItem;
use Modules\Core\Support\TickerItem;
use Modules\Notices\Models\Notice;
use Modules\Notices\Services\NoticeDirectory;

/**
 * Notices: CRUD with attachments, pinning, scheduled publishing, the public list/detail pages, the ticker and the
 * homepage "latest notices" block. Depends on Core only.
 */
class NoticesModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesHomepageSections, ProvidesMigrations, ProvidesPermissions, ProvidesPortalMenu, ProvidesRoutes, ProvidesTicker, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'notices';
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
        return ['notices.view', 'notices.manage'];
    }

    public function defaultRolePermissions(): array
    {
        return [];
    }

    public function homepageSections(): array
    {
        return [new HomepageSection('notices::home.latest', ['notices' => app(NoticeDirectory::class)->latest(6)], 50)];
    }

    public function tickerItems(): array
    {
        return app(NoticeDirectory::class)->ticker()->map(fn (Notice $notice): TickerItem => new TickerItem($notice->title(), route('notices.public.show', $notice)))->all();
    }

    public function portalMenu(Authenticatable $student): array
    {
        return [new MenuItem(__('notices::messages.title'), 'portal.notices', 'bell', 50)];
    }
}
