<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AccountService
{
    /**
     * Change the user's display name.
     *
     * @param  User  $user  The account to update.
     * @param  string  $name  The validated, normalized name.
     */
    public function updateName(User $user, string $name): void
    {
        $user->update(['name' => $name]);
    }

    /**
     * Change the user's password and log out their other sessions.
     *
     * The current password must already be verified by the caller. The user
     * must be the one logged in on the current guard, because
     * logoutOtherDevices() acts on that user.
     *
     * @param  User  $user  The logged-in account to update.
     * @param  string  $newPassword  The new plain-text password; the model cast hashes it.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => $newPassword]);

        // Must run after the update: it checks the given password against the
        // stored hash. It also re-issues this device's "remember me" cookie,
        // which embeds the password hash.
        Auth::logoutOtherDevices($newPassword);
    }
}
