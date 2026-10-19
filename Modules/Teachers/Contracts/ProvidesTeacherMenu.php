<?php

namespace Modules\Teachers\Contracts;

use Modules\Accounts\Models\User;
use Modules\Core\Support\MenuItem;

/**
 * Implemented by modules that add pages to the teacher panel (Diary, Results…). The panel drops items whose route
 * does not exist and sorts by MenuItem::$sortOrder.
 */
interface ProvidesTeacherMenu
{
    /**
     * @return list<MenuItem>
     */
    public function teacherMenu(User $teacher): array;
}
