<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Exams\Http\Controllers\Api\AdmitCardController;
use Modules\Exams\Http\Controllers\Api\ExamController;
use Modules\Exams\Http\Controllers\Api\ExamSetupController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('exams')->name('exams.api.')->group(function (Router $router): void {
    $router->get('/', [ExamController::class, 'index'])->name('index');
    $router->post('/', [ExamController::class, 'store'])->name('store');
    $router->get('{exam}', [ExamController::class, 'show'])->name('show');
    $router->put('{exam}', [ExamController::class, 'update'])->name('update');
    $router->delete('{exam}', [ExamController::class, 'destroy'])->name('destroy');

    $router->get('{exam}/classes/{class}', [ExamSetupController::class, 'show'])->name('setup.show');
    $router->put('{exam}/classes/{class}/subjects', [ExamSetupController::class, 'updateSubjects'])->name('setup.subjects');
    $router->post('{exam}/classes/{class}/subjects/seed', [ExamSetupController::class, 'seedSubjects'])->name('setup.seed');
    $router->post('{exam}/classes/{class}/subjects/copy/{source}', [ExamSetupController::class, 'copySubjects'])->name('setup.copy');
    $router->put('{exam}/classes/{class}/routine', [ExamSetupController::class, 'updateRoutine'])->name('setup.routine');

    $router->post('{exam}/admit-cards/release', [AdmitCardController::class, 'release'])->name('admit.release');
    $router->get('{exam}/admit-cards/roster', [AdmitCardController::class, 'roster'])->name('admit.roster');
});
