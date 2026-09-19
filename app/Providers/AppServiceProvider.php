<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters: per IP for the guest auth forms, per user
     * for the password change form. When a limit is hit, the user is sent
     * back to the form with a field error instead of a bare 429 page, so
     * Inertia shows it like any other error.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip())
            ->response($this->throttledResponse('auth.throttle')));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response($this->throttledResponse('Too many registration attempts. Please try again in :seconds seconds.')));

        // Stops a hijacked session from brute-forcing the current password.
        RateLimiter::for('password-update', fn (Request $request) => Limit::perMinute(5)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()))
            ->response($this->throttledResponse(
                'Too many password change attempts. Please try again in :seconds seconds.',
                'current_password',
            )));
    }

    /**
     * Build a limiter response that redirects back with an error on the given field.
     *
     * @param  string  $message  Translation key or message; may use :seconds and :minutes.
     * @param  string  $field  The form field the error is attached to.
     *
     * @return callable(Request, array<string, int|string>): RedirectResponse
     */
    protected function throttledResponse(string $message, string $field = 'email'): callable
    {
        return function (Request $request, array $headers) use ($message, $field) {
            $seconds = (int) ($headers['Retry-After'] ?? 60);

            return back()
                ->withInput($request->except('current_password', 'password', 'password_confirmation'))
                ->withErrors([
                    $field => __($message, [
                        'seconds' => $seconds,
                        'minutes' => (int) ceil($seconds / 60),
                    ]),
                ]);
        };
    }
}
