<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum WalletType: string
{
    use HasOptions;

    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'e_wallet';
    case CreditCard = 'credit_card';
    case Other = 'other';

    /**
     * {@inheritDoc}
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => self::translate('Cash'),
            self::Bank => self::translate('Bank'),
            self::EWallet => self::translate('E-wallet'),
            self::CreditCard => self::translate('Credit card'),
            self::Other => self::translate('Other'),
        };
    }
}
