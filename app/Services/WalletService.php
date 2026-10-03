<?php

namespace App\Services;

use App\Enums\EntityStatus;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class WalletService
{
    /**
     * Read the validated list filters into the shape the index screen uses.
     *
     * The result is also sent back to the frontend so the filter control
     * reflects the current query. Normalizing an already normalized array
     * returns it unchanged.
     *
     * When include_archived is on, the list shows every status rather than
     * only the archived ones: the question it answers is "where did my
     * wallet go?".
     *
     * @param  array{include_archived?: bool|int|string|null}  $filters  The validated list filters.
     *
     * @return array{include_archived: bool} Whether archived wallets join the list.
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'include_archived' => (bool) ($filters['include_archived'] ?? false),
        ];
    }

    /**
     * List a user's wallets for the index screen.
     *
     * Only whitelisted fields are returned, so user_id and updated_at never
     * reach the frontend. The current balance is derived in SQL and may be
     * negative.
     *
     * @param  User  $user  The owner whose wallets are listed.
     * @param  array{include_archived?: bool|int|string|null}  $filters  The validated list filters; normalized here.
     *
     * @return Collection<int, array<string, mixed>> Wallets with their balance and transaction count, oldest first.
     */
    public function listFor(User $user, array $filters): Collection
    {
        ['include_archived' => $includeArchived] = $this->normalizeFilters($filters);

        return $user->wallets()
            ->withCurrentBalance()
            ->withCount('transactions')
            ->when(!$includeArchived, fn ($query) => $query->whereNot('status', EntityStatus::Archived))
            // The id breaks ties between wallets created in the same second.
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Wallet $wallet) => $this->present($wallet));
    }

    /**
     * Create a wallet for a user.
     *
     * Goes through the relationship so user_id comes from the session and
     * never from request input. The status is left to the model default, so
     * $data must not carry one.
     *
     * @param  User  $user  The owner of the new wallet.
     * @param  array{name: string, type: string, currency_code: string, initial_balance: numeric-string|int|float, description?: string|null}  $data  Validated attributes; the model casts turn the type into a WalletType.
     *
     * @return Wallet The created wallet.
     */
    public function create(User $user, array $data): Wallet
    {
        return $user->wallets()->create($data);
    }

    /**
     * Update a wallet's details.
     *
     * The caller must have already rejected an edit to an archived wallet and
     * a currency or initial balance change on a wallet that has transactions.
     * The status is changed through updateStatus(), so $data must not carry one.
     *
     * @param  Wallet  $wallet  The wallet to update.
     * @param  array{name: string, type: string, currency_code: string, initial_balance: numeric-string|int|float, description?: string|null}  $data  Validated attributes; the model casts turn the type into a WalletType.
     *
     * @return Wallet The updated wallet.
     */
    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->update($data);

        return $wallet;
    }

    /**
     * Pick the fields of a listed wallet that the frontend receives.
     *
     * @param  Wallet  $wallet  A wallet loaded with withCurrentBalance() and withCount('transactions').
     *
     * @return array<string, mixed> The whitelisted fields, with created_at as a local Y-m-d date.
     */
    protected function present(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'type' => $wallet->type,
            'currency_code' => $wallet->currency_code,
            'initial_balance' => $wallet->initial_balance,
            'current_balance' => $wallet->current_balance,
            'description' => $wallet->description,
            'status' => $wallet->status,
            'transactions_count' => $wallet->transactions_count,
            // The app timezone is Asia/Ho_Chi_Minh, so this is the local calendar date.
            'created_at' => $wallet->created_at->format('Y-m-d'),
        ];
    }

    /**
     * List every currency for the wallet form and for formatting amounts.
     *
     * Inactive currencies are included: an existing wallet may still use
     * one, and the frontend looks it up by code. The frontend filters the
     * active ones for the currency select.
     *
     * @return EloquentCollection<int, Currency> Every currency ordered by code, with only the columns the frontend needs.
     */
    public function currencies(): EloquentCollection
    {
        return Currency::query()
            ->orderBy('code')
            ->get(['code', 'name', 'symbol', 'decimal_places', 'is_active']);
    }
}
