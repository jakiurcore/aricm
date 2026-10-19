<?php

use Illuminate\Routing\Router;
use Modules\Core\Http\Controllers\LocaleController;
use Modules\Core\Http\Controllers\VerificationController;

/** @var Router $router */
$router->get('/locale/{locale}', LocaleController::class)->name('core.locale');
$router->get('/verify/{type}/{hash}', [VerificationController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('core.verify');
