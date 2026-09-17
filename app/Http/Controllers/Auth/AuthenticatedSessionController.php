<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
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
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect()->route('login');
    }
}
