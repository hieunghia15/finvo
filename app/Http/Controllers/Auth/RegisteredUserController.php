<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  AuthService  $authService  Handles account creation.
     */
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
     *
     * @param  RegisterRequest  $request  The validated registration form submission.
     *
     * @throws ValidationException When a concurrent request registered the same email.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $this->authService->register($request->userData());

        return redirect()
            ->route('login')
            ->with('status', __('Account created. Please log in.'));
    }
}
