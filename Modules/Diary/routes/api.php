<?php

use Illuminate\Routing\Router;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Modules\Diary\Http\Controllers\Api\MonitorController;
use Modules\Diary\Http\Controllers\Api\PhraseController;

/** @var Router $router */
$router->middleware(['auth', EnsureAdminAccess::class])->prefix('diary')->name('diary.api.')->group(function (Router $router): void {
    $router->get('compliance', [MonitorController::class, 'compliance'])->name('compliance');
    $router->get('compliance/teachers', [MonitorController::class, 'teachers'])->name('compliance.teachers');
    $router->get('entries', [MonitorController::class, 'entries'])->name('entries.index');
    $router->get('entries/{entry}/revisions', [MonitorController::class, 'revisions'])->whereNumber('entry')->name('entries.revisions');
    $router->get('entries/{entry}', [MonitorController::class, 'show'])->whereNumber('entry')->name('entries.show');
    $router->put('entries/{entry}', [MonitorController::class, 'update'])->whereNumber('entry')->name('entries.update');
    $router->post('remind', [MonitorController::class, 'remind'])->name('remind');
    $router->delete('entries/{entry}', [MonitorController::class, 'destroy'])->whereNumber('entry')->name('entries.destroy');
    $router->get('settings', [MonitorController::class, 'settings'])->name('settings.show');
    $router->put('settings', [MonitorController::class, 'updateSettings'])->name('settings.update');

    $router->get('phrases', [PhraseController::class, 'index'])->name('phrases.index');
    $router->post('phrases', [PhraseController::class, 'store'])->name('phrases.store');
    $router->put('phrases/{phrase}', [PhraseController::class, 'update'])->whereNumber('phrase')->name('phrases.update');
    $router->delete('phrases/{phrase}', [PhraseController::class, 'destroy'])->whereNumber('phrase')->name('phrases.destroy');
});
