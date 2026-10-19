<?php

use Illuminate\Routing\Router;
use Modules\Gallery\Http\Controllers\PublicGalleryController;

/** @var Router $router */
$router->get('/gallery', [PublicGalleryController::class, 'index'])->name('gallery.public.index');
$router->get('/gallery/{album}', [PublicGalleryController::class, 'show'])->whereNumber('album')->name('gallery.public.show');
