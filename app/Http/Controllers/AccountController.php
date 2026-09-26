<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  AccountService  $accountService  Handles changes to the user's account.
     */
    public function __construct(
        protected AccountService $accountService
    ) {}

    /**
     * Display the current user's account details.
     *
     * Only whitelisted fields are sent, so the password, remember token and
     * any column added later never reach the frontend.
     *
     * @param  Request  $request  The current request, used for the authenticated user.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Account/Index', [
            'account' => $request->user()->only(['name', 'email', 'created_at']),
        ]);
    }

    /**
     * Update the current user's name.
     *
     * @param  UpdateAccountRequest  $request  The validated account form submission.
     */
    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $this->accountService->updateName($request->user(), $request->validated('name'));

        return redirect()
            ->route('account.show')
            ->with('status', __('Account updated.'));
    }

    /**
     * Change the current user's password and log out their other sessions.
     *
     * @param  UpdatePasswordRequest  $request  The validated password form submission.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->accountService->changePassword($request->user(), $request->validated('password'));

        return redirect()
            ->route('account.show')
            ->with('status', __('Password updated.'));
    }
}
