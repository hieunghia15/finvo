<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected const MAX_ATTEMPTS = 5;

    protected const DECAY_SECONDS = 60;

    /**
     * Attempt to authenticate the user and start a fresh session.
     *
     * @throws ValidationException
     */
    public function login(Request $request, array $credentials, bool $remember = false): void
    {
        $throttleKey = $this->throttleKey($request, (string) ($credentials['email'] ?? ''));

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        if (!Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
    }

    /**
     * Log the user out and invalidate the current session.
     */
    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Build the rate limiter key for a login attempt, scoped to the submitted
     * email and the requesting IP so one bad actor cannot lock out a
     * legitimate user attempting to log in from a different address.
     */
    protected function throttleKey(Request $request, string $email): string
    {
        return Str::transliterate(Str::lower($email).'|'.$request->ip());
    }
}
