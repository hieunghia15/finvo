<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Display the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('Register/Index');
    }

    /**
     * Create a new user account and send them to the login page.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $this->authService->register($request->userData());

        return redirect()
            ->route('login')
            ->with('status', 'Account created. Please log in.');
    }
}
