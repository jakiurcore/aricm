<?php

namespace Modules\Core\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Support\MenuItem;

/**
 * Implemented by modules that add items to the student portal navigation.
 */
interface ProvidesPortalMenu
{
    /**
     * @return list<MenuItem>
     */
    public function portalMenu(Authenticatable $student): array;
}
