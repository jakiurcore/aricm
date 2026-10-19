<?php

use Illuminate\Routing\Router;
use Modules\Website\Http\Controllers\PageController;
use Modules\Website\Http\Controllers\SeoController;

/** @var Router $router */
$router->get('/', [PageController::class, 'home'])->name('home');
$router->get('/about', [PageController::class, 'about'])->name('website.about');
$router->get('/academics', [PageController::class, 'academics'])->name('website.academics');
$router->get('/contact', [PageController::class, 'contact'])->name('website.contact');
$router->get('/exam-routine', [PageController::class, 'routine'])->name('website.routine');
$router->get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('website.sitemap');
$router->get('/robots.txt', [SeoController::class, 'robots'])->name('website.robots');
