<?php

namespace Tests\Unit\Models;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_balance_equals_initial_balance_without_transactions(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create(['initial_balance' => 1000]);

        $this->assertSame('1000.0000', $this->balanceOf($wallet));
    }

    public function test_current_balance_adds_income_and_subtracts_expense(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create(['initial_balance' => 1000]);
        $this->transact($wallet, 'income', 300);
        $this->transact($wallet, 'expense', 100);

        $this->assertSame('1200.0000', $this->balanceOf($wallet));
    }

    public function test_current_balance_goes_negative_when_expenses_exceed_it(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create(['initial_balance' => 100]);
        $this->transact($wallet, 'expense', 600);

        $this->assertSame('-500.0000', $this->balanceOf($wallet));
    }

    public function test_current_balance_ignores_transactions_of_other_wallets(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create(['initial_balance' => 1000]);
        $other = Wallet::factory()->for($user)->currency('VND')->create(['initial_balance' => 0]);
        $this->transact($other, 'income', 5000);

        $this->assertSame('1000.0000', $this->balanceOf($wallet));
        $this->assertSame('5000.0000', $this->balanceOf($other));
    }

    public function test_current_balance_counts_archived_wallets(): void
    {
        $wallet = Wallet::factory()->archived()->currency('VND')->create(['initial_balance' => 1000]);
        $this->transact($wallet, 'income', 250);

        $this->assertSame('1250.0000', $this->balanceOf($wallet));
    }

    public function test_current_balance_is_a_string_with_four_decimal_places(): void
    {
        $wallet = Wallet::factory()->currency('USD')->create(['initial_balance' => 10.5]);
        $this->transact($wallet, 'income', 0.25);

        $balance = $this->balanceOf($wallet);

        $this->assertMatchesRegularExpression('/^-?\d+\.\d{4}$/', $balance);
        $this->assertSame('10.7500', $balance);
    }

    public function test_scope_keeps_the_original_columns(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create(['name' => 'Cash', 'initial_balance' => 1000]);

        $loaded = Wallet::query()->withCurrentBalance()->findOrFail($wallet->id);

        $this->assertSame('Cash', $loaded->name);
        $this->assertSame('1000.0000', $loaded->initial_balance);
        $this->assertSame($wallet->user_id, $loaded->user_id);
        $this->assertSame('VND', $loaded->currency_code);
    }

    public function test_scope_combines_with_transactions_count(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create(['initial_balance' => 1000]);
        $this->transact($wallet, 'income', 300);
        $this->transact($wallet, 'expense', 100);

        $loaded = Wallet::query()->withCurrentBalance()->withCount('transactions')->findOrFail($wallet->id);

        $this->assertSame('1200.0000', $loaded->current_balance);
        $this->assertSame(2, $loaded->transactions_count);
    }

    /**
     * Load the wallet through the scope and return its current balance.
     *
     * @param  Wallet  $wallet  The wallet to look up.
     *
     * @return string The current balance as a decimal string.
     */
    protected function balanceOf(Wallet $wallet): string
    {
        return Wallet::query()->withCurrentBalance()->findOrFail($wallet->id)->current_balance;
    }

    /**
     * Create a transaction of the given type on the wallet, owned by the wallet's user.
     *
     * @param  Wallet  $wallet  The wallet to add the transaction to.
     * @param  string  $type  Either "income" or "expense".
     * @param  int|float  $amount  The transaction amount.
     */
    protected function transact(Wallet $wallet, string $type, int|float $amount): Transaction
    {
        return Transaction::factory()->{$type}()->create([
            'user_id' => $wallet->user_id,
            'wallet_id' => $wallet->id,
            'amount' => $amount,
        ]);
    }
}
