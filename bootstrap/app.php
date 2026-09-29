<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SetLocale first: the shared props need the language already chosen.
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        // Laravel moves the throttle and auth middleware ahead of the web
        // group's own middleware; without this, a tripped rate limit answers
        // in the default language instead of the visitor's.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: SetLocale::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
