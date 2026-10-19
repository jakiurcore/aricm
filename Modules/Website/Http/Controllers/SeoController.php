<?php

namespace Modules\Website\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Modules\Gallery\Models\Album;
use Modules\Notices\Models\Notice;

class SeoController
{
    /**
     * Every public page, plus each visible notice and published album.
     */
    public function sitemap(): Response
    {
        $urls = [];

        foreach (['home', 'website.about', 'website.academics', 'website.contact', 'website.routine', 'admission.public', 'notices.public.index', 'results.public', 'gallery.public.index'] as $name) {
            if (Route::has($name)) {
                $urls[] = ['loc' => route($name), 'lastmod' => null];
            }
        }

        foreach (Notice::query()->visible()->latest('id')->limit(500)->get() as $notice) {
            $urls[] = ['loc' => route('notices.public.show', $notice), 'lastmod' => $notice->updated_at];
        }

        foreach (Album::query()->where('is_published', true)->has('photos')->latest('id')->limit(200)->get() as $album) {
            $urls[] = ['loc' => route('gallery.public.show', $album), 'lastmod' => $album->updated_at];
        }

        return response(view('website::sitemap', ['urls' => $urls]), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /api\nDisallow: /student\nDisallow: /teacher\nDisallow: /portal\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
