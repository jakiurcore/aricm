<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Students\Http\Controllers\Api\IdCardController;
use Modules\Students\Http\Controllers\Api\StudentBulkController;
use Modules\Students\Http\Controllers\Api\StudentController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('students')->name('students.api.')->group(function (Router $router): void {
    // Fixed paths first, so they are not swallowed by the {student} wildcard.
    $router->get('export', [StudentBulkController::class, 'export'])->name('export');
    $router->get('template', [StudentBulkController::class, 'template'])->name('template');
    $router->get('lookup', [StudentController::class, 'lookup'])->name('lookup');
    $router->get('id-cards', [IdCardController::class, 'index'])->name('id-cards');
    $router->get('{student}/id-card', [IdCardController::class, 'show'])->whereNumber('student')->name('id-card');
    $router->post('import', [StudentBulkController::class, 'import'])->name('import');
    $router->post('promote', [StudentBulkController::class, 'promote'])->name('promote');

    $router->post('{student}/reset-password', [StudentController::class, 'resetPassword'])->whereNumber('student')->name('reset-password');
    $router->get('/', [StudentController::class, 'index'])->name('index');
    $router->post('/', [StudentController::class, 'store'])->name('store');
    $router->get('{student}', [StudentController::class, 'show'])->whereNumber('student')->name('show');
    $router->put('{student}', [StudentController::class, 'update'])->whereNumber('student')->name('update');
    $router->delete('{student}', [StudentController::class, 'destroy'])->whereNumber('student')->name('destroy');
});
