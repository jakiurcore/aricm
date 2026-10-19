<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Teachers\Http\Controllers\Api\AssignmentController;
use Modules\Teachers\Http\Controllers\Api\TeacherController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('teachers')->name('teachers.api.')->group(function (Router $router): void {
    $router->get('/', [TeacherController::class, 'index'])->name('index');
    $router->post('/', [TeacherController::class, 'store'])->name('store');
    $router->put('assignments/copy', [AssignmentController::class, 'copy'])->name('assignments.copy');
    $router->get('assignments/subjects', [AssignmentController::class, 'subjects'])->name('assignments.subjects');
    $router->get('assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    $router->post('assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    $router->delete('assignments/{assignment}', [AssignmentController::class, 'destroy'])->whereNumber('assignment')->name('assignments.destroy');
    $router->put('{teacher}', [TeacherController::class, 'update'])->whereNumber('teacher')->name('update');
    $router->delete('{teacher}', [TeacherController::class, 'destroy'])->whereNumber('teacher')->name('destroy');
    $router->post('{teacher}/login', [TeacherController::class, 'createLogin'])->whereNumber('teacher')->name('login');
    $router->put('{teacher}/password', [TeacherController::class, 'resetPassword'])->whereNumber('teacher')->name('password');
});
