<?php

use Illuminate\Routing\Router;
use Modules\Core\Http\Controllers\Api\SettingsController;
use Modules\Core\Http\Controllers\Api\TranslationController;

/** @var Router $router */
$router->get('/core/translations', TranslationController::class)->name('core.api.translations');

// Core sits upstream of Accounts, so it gates on the permission name rather than importing Accounts' middleware.
$router->middleware(['auth', 'can:admin.access', 'can:settings.manage'])->prefix('core')->name('core.api.')->group(function (Router $router): void {
    $router->get('settings', [SettingsController::class, 'show'])->name('settings.show');
    $router->put('settings', [SettingsController::class, 'update'])->name('settings.update');
    $router->post('settings/files/{key}', [SettingsController::class, 'upload'])->name('settings.upload');
});
