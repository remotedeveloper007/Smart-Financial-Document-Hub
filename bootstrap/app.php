<?php

use App\Http\Middleware\ResolveTeam;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: ['billing/webhooks/*']);

        $middleware->web(append: [
            ResolveTeam::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->appendToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            ResolveTeam::class,
        );

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            app(\App\Services\Diagnostics\FaultCaptureService::class)
                ->capture($exception, request());
        });

        $exceptions->shouldRenderJsonWhen(
            // Previous behavior only recognized API paths and redirected JSON requests sent to web routes.
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
