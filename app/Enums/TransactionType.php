<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Shared by transactions and categories: a transaction's category must have the same type.
 */
enum TransactionType: string
{
    use HasOptions;

    case Income = 'income';
    case Expense = 'expense';

    /**
     * {@inheritDoc}
     */
    public function label(): string
    {
        return match ($this) {
            self::Income => self::translate('Income'),
            self::Expense => self::translate('Expense'),
        };
    }

    /**
     * Whether the transaction adds money to its wallet.
     *
     * @return bool True for {@see self::Income}.
     */
    public function isIncome(): bool
    {
        return $this === self::Income;
    }

    /**
     * Whether the transaction takes money out of its wallet.
     *
     * @return bool True for {@see self::Expense}.
     */
    public function isExpense(): bool
    {
        return $this === self::Expense;
    }
}
