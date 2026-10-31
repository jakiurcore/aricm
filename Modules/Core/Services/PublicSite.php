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
     * The header menu: the admin's `nav_items` (location header) when any are saved, else the built-in menu.
     *
     * @return list<array{label: string, url: string, route: string}>
     */
    public function nav(): array
    {
        return $this->menu('header');
    }

    /**
     * The footer links: the admin's `nav_items` (location footer) when any are saved, else the built-in menu without Home and the last links (as the footer always showed).
     *
     * @return list<array{label: string, url: string, route: string}>
     */
    public function footer_nav(): array
    {
        return $this->menu('footer');
    }

    /**
     * @return list<array{label: string, url: string, route: string}>
     */
    protected function menu(string $location): array
    {
        $saved = array_values(array_filter((array) setting('nav_items', []), fn (mixed $item): bool => is_array($item)));

        if ($saved === []) {
            return $location === 'footer' ? array_slice($this->defaultNav(), 1, 6) : $this->defaultNav();
        }

        $locale = app()->getLocale();
        $items = [];

        foreach (collect($saved)->where('location', $location)->sortBy(fn (array $item): int => (int) ($item['sort_order'] ?? 0)) as $item) {
            if (($item['visible'] ?? true) === false) {
                continue;
            }

            $label = (string) (($item['label_'.$locale] ?? null) ?: ($item['label_bn'] ?? ''));
            $route = (string) ($item['route'] ?? '');

            if ($route !== '' && Route::has($route)) {
                $items[] = ['label' => $label, 'url' => $route === 'home' ? route('home') : route($route), 'route' => $route];
            } elseif ($route === '' && filled($item['url'] ?? null)) {
                $items[] = ['label' => $label, 'url' => (string) $item['url'], 'route' => ''];
            }
        }

        return $items;
    }

    /**
     * @return list<array{label: string, url: string, route: string}>
     */
    protected function defaultNav(): array
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
     * `whatsapp` is a wa.me link built from the social_whatsapp setting (null when unset), `office_hours` and
     * `principal_title` are localized.
     *
     * @return array{name: string, short_name: string, tagline: string, address: string, phones: list<string>, email: string|null, logo: string, facebook: string|null, youtube: string|null, instagram: string|null, whatsapp: string|null, office_hours: string, principal_title: string, established: string}
     */
    public function identity(): array
    {
        $locale = app()->getLocale();
        $logo = setting('logo');

        return [
            'name' => (string) (setting("institute_name_{$locale}") ?: setting('institute_name_bn')),
            'short_name' => (string) (setting("institute_short_name_{$locale}") ?: setting('institute_short_name_bn') ?: setting("institute_name_{$locale}") ?: setting('institute_name_bn')),
            'tagline' => (string) (setting("tagline_{$locale}") ?: setting('tagline_bn')),
            'address' => (string) (setting("address_{$locale}") ?: setting('address_bn')),
            'phones' => array_values((array) setting('phones', [])),
            'email' => setting('email'),
            'logo' => $logo ? Storage::disk('public')->url($logo) : asset('images/logo.jpeg'),
            'facebook' => setting('social_facebook'),
            'youtube' => setting('social_youtube'),
            'instagram' => setting('social_instagram') ?: null,
            'whatsapp' => $this->whatsappUrl(setting('social_whatsapp')),
            'office_hours' => (string) (setting("office_hours_{$locale}") ?: setting('office_hours_bn')),
            'principal_title' => (string) (setting("principal_title_{$locale}") ?: setting('principal_title_bn')),
            'established' => bn_digits((string) setting('established_year')),
        ];
    }

    /**
     * A full wa.me link from a link or a number with country code (digits are kept, nothing is prefixed).
     */
    protected function whatsappUrl(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : 'https://wa.me/'.$digits;
    }
}
