<?php

use Illuminate\Routing\Router;
use Modules\Fees\Http\Controllers\GatewayCallbackController;

/** @var Router $router */
$router->match(['get', 'post'], '/fees/gateway/{driver}/callback/{outcome?}', [GatewayCallbackController::class, 'callback'])->middleware('throttle:60,1')->name('fees.gateway.callback');
$router->post('/fees/gateway/{driver}/ipn', [GatewayCallbackController::class, 'ipn'])->middleware('throttle:60,1')->name('fees.gateway.ipn');
