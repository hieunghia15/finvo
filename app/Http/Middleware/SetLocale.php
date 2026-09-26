<?php

namespace App\Http\Middleware;

use App\Services\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  LocaleService  $localeService  Reads the language cookie.
     */
    public function __construct(
        protected LocaleService $localeService
    ) {}

    /**
     * Switch to the language remembered in the cookie. Without a valid cookie
     * the configured default stays, which is vi everywhere except the test
     * suite.
     *
     * Runs in the web group after EncryptCookies and before route middleware
     * and form requests, so throttle and validation messages are translated.
     *
     * @param  Request  $request  The current request.
     * @param  Closure(Request): Response  $next  The next middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($locale = $this->localeService->fromRequest($request)) {
            App::setLocale($locale->value);
        }

        return $next($request);
    }
}
