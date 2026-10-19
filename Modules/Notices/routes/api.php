<?php

use Illuminate\Routing\Router;
use Modules\Notices\Http\Controllers\Api\NoticeController;

/** @var Router $router */
// Notices depend on Core only, so the admin gate is expressed with permission names, not Accounts classes.
$router->middleware(['auth', 'can:admin.access'])->prefix('notices')->name('notices.api.')->group(function (Router $router): void {
    $router->get('/', [NoticeController::class, 'index'])->name('index');
    $router->post('/', [NoticeController::class, 'store'])->name('store');
    $router->put('{notice}', [NoticeController::class, 'update'])->name('update');
    $router->delete('{notice}', [NoticeController::class, 'destroy'])->name('destroy');
});
