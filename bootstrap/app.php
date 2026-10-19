<?php

use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Modules\Accounts\Http\Middleware\EnsureAdminAccess;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The admin gate must run before route-model binding, so a teacher gets a 403 rather than a 404 that
        // would reveal whether a record exists.
        $middleware->appendToPriorityList(after: AuthenticatesRequests::class, append: EnsureAdminAccess::class);

        // Payment providers post to these URLs and cannot send a CSRF token. Nothing they send is trusted: every
        // payment is confirmed with the provider's own server before it is approved.
        $middleware->validateCsrfTokens(except: ['fees/gateway/*']);

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Bangla-aware 403/404 pages. The views live in the Core module.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || ! in_array($e->getStatusCode(), [403, 404], true)) {
                return null;
            }

            return response()->view("core::errors.{$e->getStatusCode()}", [], $e->getStatusCode());
        });
    })->create();
