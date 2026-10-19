<?php

namespace Modules\StudentPortal\Services;

use Illuminate\Support\Facades\Route;

/**
 * The portal's fixed navigation: the sidebar on a desktop, the bottom bar on a phone. An entry whose route is not
 * defined (its module is not installed) is dropped. A dot marks entries with something new or owing.
 */
class PortalNavigation
{
    /**
     * Key => [route, icon, route pattern that makes it the active entry].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const array ITEMS = [
        'home' => ['portal.dashboard', '⌂', 'portal.dashboard'],
        'diary' => ['portal.diary', '📓', 'portal.diary*'],
        'results' => ['portal.results', '🏅', 'portal.results*'],
        'routine' => ['portal.routine', '🗓', 'portal.routine'],
        'admit' => ['portal.admit', '🎫', 'portal.admit*'],
        'idcard' => ['portal.idcard', '🪪', 'portal.idcard*'],
        'fees' => ['portal.fees', '৳', 'portal.fees*'],
        'notices' => ['portal.notices', '📢', 'portal.notices*'],
        'profile' => ['portal.profile', '👤', 'portal.profile'],
    ];

    /**
     * @param  array<string, int|null>  $badges  Badge counts by route name.
     * @return list<array{key: string, route: string, icon: string, dot: bool, active: bool}>
     */
    public function sidebar(array $badges): array
    {
        return $this->build(['home', 'diary', 'results', 'routine', 'admit', 'idcard', 'fees', 'notices', 'profile'], $badges);
    }

    /**
     * @param  array<string, int|null>  $badges
     * @return list<array{key: string, route: string, icon: string, dot: bool, active: bool}>
     */
    public function bottom(array $badges): array
    {
        return $this->build(['home', 'diary', 'fees', 'results', 'routine', 'notices', 'profile'], $badges);
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, int|null>  $badges
     * @return list<array{key: string, route: string, icon: string, dot: bool, active: bool}>
     */
    protected function build(array $keys, array $badges): array
    {
        $items = [];

        foreach ($keys as $key) {
            [$route, $icon, $pattern] = self::ITEMS[$key];

            if (! Route::has($route)) {
                continue;
            }

            $items[] = ['key' => $key, 'route' => $route, 'icon' => $icon, 'dot' => ($badges[$route] ?? 0) > 0, 'active' => request()->routeIs($pattern)];
        }

        return $items;
    }
}
