<?php

use Illuminate\Routing\Router;
use Modules\Academic\Http\Controllers\Api\ClassController;
use Modules\Academic\Http\Controllers\Api\ClassSubjectController;
use Modules\Academic\Http\Controllers\Api\HolidayController;
use Modules\Academic\Http\Controllers\Api\OptionsController;
use Modules\Academic\Http\Controllers\Api\SectionController;
use Modules\Academic\Http\Controllers\Api\SessionController;
use Modules\Academic\Http\Controllers\Api\SubjectController;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('academic')->name('academic.api.')->group(function (Router $router): void {
    $router->get('options', OptionsController::class)->name('options');
    $router->get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
    $router->post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
    $router->put('holidays/weekly', [HolidayController::class, 'updateWeekly'])->name('holidays.weekly');
    $router->put('holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
    $router->delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
    $router->apiResource('sessions', SessionController::class)->except('show');
    $router->apiResource('classes', ClassController::class)->except('show');
    $router->apiResource('sections', SectionController::class)->except('show');
    $router->apiResource('subjects', SubjectController::class)->except('show');
    $router->get('classes/{class}/subjects', [ClassSubjectController::class, 'show'])->name('classes.subjects.show');
    $router->put('classes/{class}/subjects', [ClassSubjectController::class, 'update'])->name('classes.subjects.update');
});
