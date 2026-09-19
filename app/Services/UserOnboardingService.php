<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletType;
use App\Models\User;

class UserOnboardingService
{
    /**
     * Default categories every new account starts with.
     *
     * @var array<string, list<string>>
     */
    public const DEFAULT_CATEGORIES = [
        'income' => ['Lương', 'Thưởng', 'Thu nhập khác'],
        'expense' => ['Ăn uống', 'Di chuyển', 'Mua sắm', 'Nhà ở', 'Hóa đơn', 'Giải trí', 'Sức khỏe', 'Chi phí khác'],
    ];

    public const DEFAULT_WALLET_NAME = 'Ngân hàng';

    /**
     * Create the default wallet and categories for a user.
     *
     * Idempotent: existing rows with the same unique keys are left untouched.
     * Callers that need atomicity with user creation must wrap this call
     * in a database transaction.
     */
    public function createDefaults(User $user): void
    {
        $user->wallets()->firstOrCreate(
            ['name' => self::DEFAULT_WALLET_NAME],
            [
                'type' => WalletType::Bank,
                'currency_code' => 'VND',
                'initial_balance' => 0,
            ],
        );

        foreach (self::DEFAULT_CATEGORIES as $type => $names) {
            foreach ($names as $name) {
                $user->categories()->firstOrCreate([
                    'name' => $name,
                    'type' => TransactionType::from($type),
                ]);
            }
        }
    }
}
