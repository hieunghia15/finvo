<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(6));

        $this->configureRateLimiting();
    }

    /**
     * Configure the per-IP rate limiters for the guest auth forms. When a
     * limit is hit, the user is sent back to the form with a field error
     * instead of a bare 429 page, so Inertia shows it like any other error.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip())
            ->response($this->throttledResponse('auth.throttle')));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response($this->throttledResponse('Too many registration attempts. Please try again in :seconds seconds.')));
    }

    /**
     * Build a limiter response that redirects back with an "email" error.
     *
     * @return callable(Request, array<string, int|string>): RedirectResponse
     */
    protected function throttledResponse(string $message): callable
    {
        return function (Request $request, array $headers) use ($message) {
            $seconds = (int) ($headers['Retry-After'] ?? 60);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => __($message, [
                        'seconds' => $seconds,
                        'minutes' => (int) ceil($seconds / 60),
                    ]),
                ]);
        };
    }
}
