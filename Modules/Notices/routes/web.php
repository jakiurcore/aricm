<?php

use Illuminate\Routing\Router;
use Modules\Notices\Http\Controllers\PublicNoticeController;

/** @var Router $router */
$router->get('/notices', [PublicNoticeController::class, 'index'])->name('notices.public.index');
$router->get('/notices/{notice}', [PublicNoticeController::class, 'show'])->whereNumber('notice')->name('notices.public.show');
