<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Controllers\Api\RoleController;
use Modules\Accounts\Http\Controllers\Api\UserController;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('accounts')->name('accounts.api.')->group(function (Router $router): void {
    $router->apiResource('users', UserController::class);
    $router->get('permissions', [RoleController::class, 'permissions'])->name('permissions');
    $router->apiResource('roles', RoleController::class)->except('show');
});
