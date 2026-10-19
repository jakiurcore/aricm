<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Controllers\LoginController;

/** @var Router $router */
$router->middleware('guest')->group(function (Router $router): void {
    $router->get('/login', [LoginController::class, 'create'])->name('login');
    $router->post('/login', [LoginController::class, 'store'])->name('login.store');
});

$router->post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
