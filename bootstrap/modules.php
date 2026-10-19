<?php

use Laramod\Admin\AdminModule;
use Modules\Academic\AcademicModule;
use Modules\Accounts\AccountsModule;
use Modules\Core\CoreModule;
use Modules\Exams\ExamsModule;
use Modules\Students\StudentsModule;

/*
| The single source of truth for which modules exist. The order is the wiring order.
| Dependency direction (enforced by the architecture test): Core ← Accounts ← Academic ← Students ← Exams ← Results ← Fees.
*/
return [
    CoreModule::class,
    AccountsModule::class,
    AcademicModule::class,
    StudentsModule::class,
    ExamsModule::class,
    AdminModule::class,
];
