<?php

namespace Modules\Website;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesConfig;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Services\PublicSite;
use Modules\Core\Support\HomepageSection;
use Modules\Website\Services\HomeContent;

/**
 * Website: the public pages (home, about, academics, contact, exam routine), sitemap and robots. A consumer module: the
 * home page is composed from every module's ProvidesHomepageSections, and Website adds its own blocks the same way.
 */
class WebsiteModule implements Module, ProvidesConfig, ProvidesHomepageSections, ProvidesRoutes, ProvidesTranslations, ProvidesViews
{
    public function name(): string
    {
        return 'website';
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

    public function config(): array
    {
        return ['modules.website' => 'config/website.php'];
    }

    public function homepageSections(): array
    {
        $content = app(HomeContent::class);
        $identity = app(PublicSite::class)->identity();

        return [
            new HomepageSection('website::sections.hero', [], 0),
            new HomepageSection('website::sections.program', [], 20),
            new HomepageSection('website::sections.phonetics', [], 25),
            new HomepageSection('website::sections.divisions', [], 30),
            new HomepageSection('website::sections.why', [], 35),
            new HomepageSection('website::sections.principal', ['content' => $content], 40),
            new HomepageSection('website::sections.stats', ['stats' => $content->stats()], 55),
            new HomepageSection('website::sections.contact', ['map' => $content->map(), 'phones' => $identity['phones'], 'address' => $identity['address'], 'email' => $identity['email']], 90),
        ];
    }
}
