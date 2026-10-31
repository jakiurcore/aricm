<?php

use Illuminate\Routing\Router;
use Modules\Website\Http\Controllers\Api\ContentController;
use Modules\Website\Http\Controllers\Api\ItemController;
use Modules\Website\Http\Controllers\Api\PriceRowController;
use Modules\Website\Http\Controllers\Api\SectionController;
use Modules\Website\Models\WebsiteItem;

/** @var Router $router */
$collections = implode('|', WebsiteItem::COLLECTIONS);

$router->middleware(['auth', 'can:admin.access'])->prefix('website')->name('website.api.')->group(function (Router $router) use ($collections): void {
    $router->get('content', [ContentController::class, 'show'])->name('content.show');
    $router->put('content', [ContentController::class, 'update'])->name('content.update');
    $router->post('content/image/{key}', [ContentController::class, 'uploadImage'])->name('content.image.store');
    $router->delete('content/image/{key}', [ContentController::class, 'resetImage'])->name('content.image.destroy');

    $router->get('items/{collection}', [ItemController::class, 'index'])->where('collection', $collections)->name('items.index');
    $router->post('items/{collection}', [ItemController::class, 'store'])->where('collection', $collections)->name('items.store');
    $router->post('items/{collection}/reorder', [ItemController::class, 'reorder'])->where('collection', $collections)->name('items.reorder');
    $router->put('items/{collection}/{item}', [ItemController::class, 'update'])->where('collection', $collections)->name('items.update');
    $router->delete('items/{collection}/{item}', [ItemController::class, 'destroy'])->where('collection', $collections)->name('items.destroy');

    $router->get('price-rows', [PriceRowController::class, 'index'])->name('price-rows.index');
    $router->post('price-rows', [PriceRowController::class, 'store'])->name('price-rows.store');
    $router->post('price-rows/reorder', [PriceRowController::class, 'reorder'])->name('price-rows.reorder');
    $router->put('price-rows/{priceRow}', [PriceRowController::class, 'update'])->name('price-rows.update');
    $router->delete('price-rows/{priceRow}', [PriceRowController::class, 'destroy'])->name('price-rows.destroy');

    $router->get('sections', [SectionController::class, 'show'])->name('sections.show');
    $router->put('sections', [SectionController::class, 'update'])->name('sections.update');
});
