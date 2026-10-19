<?php

namespace Modules\TeacherPanel\Services;

use Illuminate\Support\Facades\Route;
use Laramod\Facades\Modules;
use Modules\Accounts\Models\User;
use Modules\Core\Support\MenuItem;
use Modules\Teachers\Contracts\ProvidesTeacherMenu;

/**
 * The panel navigation, assembled from every module's ProvidesTeacherMenu. Items whose route does not exist are dropped.
 */
class TeacherMenu
{
    /**
     * @return list<MenuItem>
     */
    public function items(User $teacher): array
    {
        $items = [];

        foreach (Modules::providing(ProvidesTeacherMenu::class) as $module) {
            $items = [...$items, ...$module->teacherMenu($teacher)];
        }

        $items = array_values(array_filter($items, fn (MenuItem $item): bool => Route::has($item->route)));
        usort($items, fn (MenuItem $a, MenuItem $b): int => $a->sortOrder <=> $b->sortOrder);

        return $items;
    }

    /**
     * Up to three flagged items for the phone's bottom bar (home takes the first slot); the rest sit behind "More".
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
