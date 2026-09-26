<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;

class UserOnboardingService
{
    /**
     * Default categories every new account starts with, as translation keys.
     * They are stored in the language active at registration and never
     * translated again: from then on they are the user's own data.
     *
     * Keep in sync with EXTRA_KEYS in scripts/lang-check.mjs, which cannot
     * see keys inside constants.
     *
     * @var array<string, list<string>>
     */
    public const DEFAULT_CATEGORIES = [
        'income' => ['Salary', 'Bonus', 'Other income'],
        'expense' => ['Food & Drinks', 'Transportation', 'Shopping', 'Housing', 'Bills', 'Entertainment', 'Health', 'Other expenses'],
    ];

    /** Translation key of the default wallet's name; see DEFAULT_CATEGORIES. */
    public const DEFAULT_WALLET_NAME = 'Bank';

    /**
     * Create the default wallet and categories for a user, named in the
     * current locale.
     *
     * Idempotent: existing rows with the same unique keys are left untouched.
     * Callers that need atomicity with user creation must wrap this call
     * in a database transaction.
     */
    public function createDefaults(User $user): void
    {
        $user->wallets()->firstOrCreate(
            ['name' => __(self::DEFAULT_WALLET_NAME)],
            [
                'type' => WalletType::Bank,
                'currency_code' => 'VND',
                'initial_balance' => 0,
            ],
        );

        foreach (self::DEFAULT_CATEGORIES as $type => $names) {
            foreach ($names as $name) {
                $user->categories()->firstOrCreate([
                    'name' => __($name),
                    'type' => TransactionType::from($type),
                ]);
            }
        }
    }
}
