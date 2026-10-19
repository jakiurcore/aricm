<?php

namespace Modules\StudentPortal\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Laramod\Facades\Modules;
use Modules\Core\Contracts\ProvidesPortalMenu;
use Modules\Core\Support\MenuItem;

/**
 * The portal navigation, assembled from every module's ProvidesPortalMenu (Exams, Fees, Results, Notices, Diary…).
 * An item whose route is not defined is dropped, so a module that is not installed simply does not appear.
 */
class PortalMenu
{
    /**
     * @return list<MenuItem>
     */
    public function items(Authenticatable $student): array
    {
        $items = [];

        foreach (Modules::providing(ProvidesPortalMenu::class) as $module) {
            $items = [...$items, ...$module->portalMenu($student)];
        }

        $items = array_values(array_filter($items, fn (MenuItem $item): bool => Route::has($item->route)));
        usort($items, fn (MenuItem $a, MenuItem $b): int => $a->sortOrder <=> $b->sortOrder);

        return $items;
    }

    /**
     * The first few flagged for the phone's bottom bar; everything else sits behind "More".
     *
     * @param  list<MenuItem>  $items
     * @return array{bottom: list<MenuItem>, more: list<MenuItem>}
     */
    public function split(array $items, int $limit = 3): array
    {
        $bottom = array_slice(array_values(array_filter($items, fn (MenuItem $item): bool => $item->inBottomNav)), 0, $limit);

        return ['bottom' => $bottom, 'more' => array_values(array_filter($items, fn (MenuItem $item): bool => ! in_array($item, $bottom, true)))];
    }
}
