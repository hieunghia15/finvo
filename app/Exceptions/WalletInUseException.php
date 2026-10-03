<?php

namespace App\Exceptions;

use App\Models\Wallet;
use RuntimeException;

/**
 * Thrown when a wallet that still has transactions is deleted.
 *
 * A plain RuntimeException rather than an HttpException: turning this into a
 * response is the controller's job, so the service stays usable from a
 * command or a job that has no request to redirect.
 */
class WalletInUseException extends RuntimeException
{
    /**
     * @param  Wallet  $wallet  The wallet that could not be deleted.
     */
    public function __construct(public readonly Wallet $wallet)
    {
        parent::__construct(__('This wallet still has transactions and cannot be deleted.'));
    }
}
