<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Controllers\LoginController;
use Modules\Accounts\Http\Controllers\StudentLoginController;
use Modules\Accounts\Http\Controllers\StudentPasswordController;
use Modules\Accounts\Http\Middleware\AuthenticateStudent;

/** @var Router $router */
$router->middleware('guest')->group(function (Router $router): void {
    $router->get('/login', [LoginController::class, 'create'])->name('login');
    $router->post('/login', [LoginController::class, 'store'])->name('login.store');
});

$router->post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Student sign in (guard: student). The portal itself lives in StudentPortal behind AuthenticateStudent.
$router->middleware('guest:student')->group(function (Router $router): void {
    $router->get('/student/login', [StudentLoginController::class, 'create'])->name('student.login');
    $router->post('/student/login', [StudentLoginController::class, 'store'])->name('student.login.store');
});

$router->middleware(AuthenticateStudent::class)->group(function (Router $router): void {
    $router->post('/student/logout', [StudentLoginController::class, 'destroy'])->name('student.logout');
    $router->get('/student/password', [StudentPasswordController::class, 'edit'])->name('student.password.edit');
    $router->put('/student/password', [StudentPasswordController::class, 'update'])->name('student.password.update');
});
