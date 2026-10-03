<?php

namespace App\Http\Requests\Concerns;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Resolves the {wallet} route parameter for requests that act on one.
 *
 * The lookup goes through the current user's relationship, so another user's
 * row raises a ModelNotFoundException and surfaces as a 404 rather than a 403.
 *
 * @phpstan-require-extends FormRequest
 */
trait ResolvesWallet
{
    protected ?Wallet $resolvedWallet = null;

    /**
     * The wallet this request acts on, owned by the current user.
     *
     * Memoized because both rules() and the controller read it within a
     * single request.
     *
     * @throws ModelNotFoundException<Wallet> When it is missing or owned by someone else.
     *
     * @return Wallet The resolved wallet.
     */
    public function wallet(): Wallet
    {
        return $this->resolvedWallet ??= $this->user()
            ->wallets()
            ->findOrFail($this->route('wallet'));
    }
}
