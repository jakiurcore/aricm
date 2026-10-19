<?php

use Illuminate\Routing\Router;
use Modules\Gallery\Http\Controllers\Api\AlbumController;

/** @var Router $router */
$router->middleware(['auth', 'can:admin.access'])->prefix('gallery')->name('gallery.api.')->group(function (Router $router): void {
    $router->get('albums', [AlbumController::class, 'index'])->name('albums.index');
    $router->post('albums', [AlbumController::class, 'store'])->name('albums.store');
    $router->get('albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
    $router->put('albums/{album}', [AlbumController::class, 'update'])->name('albums.update');
    $router->delete('albums/{album}', [AlbumController::class, 'destroy'])->name('albums.destroy');
    $router->post('albums/{album}/photos', [AlbumController::class, 'upload'])->name('photos.upload');
    $router->put('albums/{album}/photos/order', [AlbumController::class, 'reorder'])->name('photos.order');
    $router->put('albums/{album}/cover/{photo}', [AlbumController::class, 'cover'])->name('albums.cover');
    $router->put('photos/{photo}', [AlbumController::class, 'updatePhoto'])->name('photos.update');
    $router->delete('photos/{photo}', [AlbumController::class, 'destroyPhoto'])->name('photos.destroy');
});
