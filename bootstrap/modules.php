<?php

use Laramod\Admin\AdminModule;
use Modules\Accounts\AccountsModule;
use Modules\Core\CoreModule;

/*
| The single source of truth for which modules exist. The order is the wiring order.
| Dependency direction (enforced by the architecture test): Core ← Accounts ← Academic ← Students ← Exams ← Fees / Results.
*/
return [
    CoreModule::class,
    AccountsModule::class,
    AdminModule::class,
];
