<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laramod\Facades\Modules;
use Modules\Core\Contracts\ProvidesTicker;
use Modules\Core\Support\TickerItem;

/**
 * What the shared public layout needs: the institute identity, the navigation (only links whose route exists, so a
 * missing module simply drops its entry) and the ticker contributed by modules.
 */
class PublicSite
{
    /**
     * Route name => translation key, in menu order.
     *
     * @var array<string, string>
     */
    public const array NAV = [
        'home' => 'home', 'website.about' => 'about', 'website.academics' => 'academics', 'admission.public' => 'admission',
        'notices.public.index' => 'notices', 'website.routine' => 'routine', 'results.public' => 'results',
        'gallery.public.index' => 'gallery', 'website.contact' => 'contact',
    ];

    /**
     * @return list<array{label: string, url: string, route: string}>
     */
    public function nav(): array
    {
        $items = [];

        foreach (self::NAV as $route => $key) {
            if ($route === 'home') {
                $items[] = ['label' => __("core::nav.{$key}"), 'url' => Route::has('home') ? route('home') : url('/'), 'route' => 'home'];
            } elseif (Route::has($route)) {
                $items[] = ['label' => __("core::nav.{$key}"), 'url' => route($route), 'route' => $route];
            }
        }

        return $items;
    }

    /**
     * @return list<TickerItem>
     */
    public function ticker(): array
    {
        $items = [];

        foreach (Modules::providing(ProvidesTicker::class) as $module) {
            $items = [...$items, ...$module->tickerItems()];
        }

        return $items;
    }

    /**
     * @return array{name: string, tagline: string, address: string, phones: list<string>, email: string|null, logo: string, facebook: string|null, youtube: string|null, established: string}
     */
    public function identity(): array
    {
        $locale = app()->getLocale();
        $logo = setting('logo');

        return [
            'name' => (string) (setting("institute_name_{$locale}") ?: setting('institute_name_bn')),
            'tagline' => (string) (setting("tagline_{$locale}") ?: setting('tagline_bn')),
            'address' => (string) (setting("address_{$locale}") ?: setting('address_bn')),
            'phones' => array_values((array) setting('phones', [])),
            'email' => setting('email'),
            'logo' => $logo ? Storage::disk('public')->url($logo) : asset('images/logo.jpeg'),
            'facebook' => setting('social_facebook'),
            'youtube' => setting('social_youtube'),
            'established' => bn_digits((string) setting('established_year')),
        ];
    }
}
