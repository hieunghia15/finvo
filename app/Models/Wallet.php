<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Enums\TransactionType;
use App\Enums\WalletType;
use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The current balance is never stored; it is derived from
 * initial_balance and the wallet's transactions.
 *
 * @property-read string $current_balance Only present after the withCurrentBalance() scope.
 * @property-read int $transactions_count Only present after withCount('transactions').
 */
#[Fillable(['name', 'type', 'currency_code', 'initial_balance', 'description', 'status'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'other',
        'initial_balance' => 0,
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletType::class,
            'status' => EntityStatus::class,
            'initial_balance' => 'decimal:4',
        ];
    }

    /**
     * Add the current balance, derived from the initial balance and the
     * wallet's transactions, as a decimal string column.
     *
     * @param  Builder<Wallet>  $query  The wallet query to add the column to.
     */
    #[Scope]
    protected function withCurrentBalance(Builder $query): void
    {
        // selectRaw() alone would replace the default "select *".
        if ($query->getQuery()->columns === null) {
            $query->select($query->qualifyColumn('*'));
        }

        $query->selectRaw(
            'wallets.initial_balance + COALESCE((
                SELECT SUM(CASE WHEN transactions.type = ? THEN transactions.amount
                                WHEN transactions.type = ? THEN -transactions.amount END)
                FROM transactions
                WHERE transactions.wallet_id = wallets.id
            ), 0) AS current_balance',
            [TransactionType::Income->value, TransactionType::Expense->value],
        )->withCasts(['current_balance' => 'decimal:4']);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
