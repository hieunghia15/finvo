<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  AuthService  $authService  Handles login, logout and session lifecycle.
     */
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Display the login page.
     */
    public function create(): Response
    {
        return Inertia::render('Login/Index');
    }

    /**
     * Authenticate the user and start a session.
     *
     * @param  LoginRequest  $request  The validated login form submission.
     *
     * @throws ValidationException When the credentials are invalid or the user is throttled.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            $request,
            $request->credentials(),
            $request->boolean('remember'),
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Log the user out and invalidate the session.
     *
     * @param  Request  $request  The current request whose session is invalidated.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect()->route('login');
    }
}
