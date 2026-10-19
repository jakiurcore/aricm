<?php

use Laramod\Admin\AdminModule;
use Modules\Academic\AcademicModule;
use Modules\Accounts\AccountsModule;
use Modules\Admission\AdmissionModule;
use Modules\Core\CoreModule;
use Modules\Exams\ExamsModule;
use Modules\Fees\FeesModule;
use Modules\Gallery\GalleryModule;
use Modules\Notices\NoticesModule;
use Modules\Results\ResultsModule;
use Modules\Students\StudentsModule;

/*
| The single source of truth for which modules exist. The order is the wiring order.
| Dependency direction (enforced by the architecture test): see tests/Feature/ArchitectureTest.php.
*/
return [
    CoreModule::class,
    AccountsModule::class,
    AcademicModule::class,
    StudentsModule::class,
    ExamsModule::class,
    ResultsModule::class,
    FeesModule::class,
    NoticesModule::class,
    GalleryModule::class,
    AdmissionModule::class,
    AdminModule::class,
];
