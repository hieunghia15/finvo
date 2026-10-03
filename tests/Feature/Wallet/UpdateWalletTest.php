<?php

namespace Tests\Feature\Wallet;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWalletTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A full form for the wallet, overridden per test.
     *
     * @param  array<string, mixed>  $overrides  Fields to replace.
     *
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'currency_code' => 'VND',
            'initial_balance' => 1000,
            'description' => null,
        ], $overrides);
    }

    /**
     * Create a VND wallet with an initial balance of 1000.
     *
     * @param  User  $user  The owner.
     * @param  array<string, mixed>  $attributes  Attributes to override.
     */
    protected function walletFor(User $user, array $attributes = []): Wallet
    {
        return Wallet::factory()->for($user)->currency('VND')->create(array_merge([
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'initial_balance' => 1000,
            'description' => null,
        ], $attributes));
    }

    /**
     * Give the wallet one transaction.
     *
     * @param  Wallet  $wallet  The wallet to use.
     */
    protected function addTransaction(Wallet $wallet): void
    {
        Transaction::factory()->income()->for($wallet->user)->for($wallet)->create();
    }

    public function test_user_can_change_name_type_and_description(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Techcombank',
            'type' => 'bank',
            'description' => 'Lương',
        ]));

        $response->assertRedirect('/wallets');
        $response->assertSessionHas('status', 'Wallet updated.');
        $wallet->refresh();
        $this->assertSame('Techcombank', $wallet->name);
        $this->assertSame('bank', $wallet->type->value);
        $this->assertSame('Lương', $wallet->description);
    }

    public function test_an_unused_wallet_can_change_currency_and_initial_balance(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'currency_code' => 'USD',
            'initial_balance' => '25.50',
        ]));

        $response->assertSessionHasNoErrors();
        $wallet->refresh();
        $this->assertSame('USD', $wallet->currency_code);
        $this->assertSame('25.5000', $wallet->initial_balance);
    }

    public function test_the_currency_cannot_change_once_the_wallet_has_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'currency_code' => 'USD',
        ]));

        $response->assertSessionHasErrors([
            'currency_code' => 'This wallet already has transactions, so its currency can no longer be changed.',
        ]);
        $this->assertSame('VND', $wallet->fresh()->currency_code);
    }

    public function test_the_initial_balance_cannot_change_once_the_wallet_has_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'initial_balance' => 2000,
        ]));

        $response->assertSessionHasErrors([
            'initial_balance' => 'This wallet already has transactions, so its initial balance can no longer be changed.',
        ]);
        $this->assertSame('1000.0000', $wallet->fresh()->initial_balance);
    }

    public function test_a_used_wallet_can_still_be_renamed_when_currency_and_balance_stay_put(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Ví chính',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('Ví chính', $wallet->fresh()->name);
    }

    public function test_the_balance_is_compared_by_value_not_by_how_it_is_written(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user, ['initial_balance' => '1000.0000']);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'initial_balance' => '1000',
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_a_lowercase_currency_code_is_not_treated_as_a_change(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'currency_code' => 'vnd',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('VND', $wallet->fresh()->currency_code);
    }

    public function test_a_wallet_may_keep_a_currency_that_was_deactivated(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('USD')->create(['name' => 'Dollar', 'initial_balance' => 10]);
        Currency::query()->where('code', 'USD')->update(['is_active' => false]);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Dollar',
            'currency_code' => 'USD',
            'initial_balance' => 10,
        ]));

        $response->assertSessionHasNoErrors();
    }

    public function test_the_currency_cannot_change_to_a_deactivated_one(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        Currency::query()->where('code', 'USD')->update(['is_active' => false]);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'currency_code' => 'USD',
        ]));

        $response->assertSessionHasErrors(['currency_code' => 'The selected currency is not available.']);
        $this->assertSame('VND', $wallet->fresh()->currency_code);
    }

    public function test_an_archived_wallet_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user, ['status' => 'archived']);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Đổi tên',
        ]));

        $response->assertSessionHasErrors([
            'status' => 'An archived wallet must be restored before it can be edited.',
        ]);
        $this->assertSame('Tiền mặt', $wallet->fresh()->name);
    }

    public function test_an_inactive_wallet_can_be_edited(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user, ['status' => 'inactive']);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Đổi tên',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame('Đổi tên', $wallet->fresh()->name);
    }

    public function test_submitting_without_changes_succeeds(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'Wallet updated.');
    }

    public function test_the_name_cannot_collide_with_another_wallet_of_the_user(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->walletFor($user, ['name' => 'Techcombank']);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'techcombank',
        ]));

        $response->assertSessionHasErrors('name');
        $this->assertSame('Tiền mặt', $wallet->fresh()->name);
    }

    public function test_the_stored_balance_prop_is_rejected_for_a_zero_decimal_currency(): void
    {
        // The index sends "1000.0000"; the form must trim it to the currency's
        // decimal_places before sending it back.
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'initial_balance' => '1000.0000',
        ]));

        $response->assertSessionHasErrors('initial_balance');
    }

    public function test_an_unknown_currency_with_an_exponent_balance_is_a_validation_error_not_a_crash(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);
        $this->addTransaction($wallet);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'currency_code' => 'XXX',
            'initial_balance' => '1e3',
        ]));

        $response->assertRedirect('/wallets');
        $response->assertSessionHasErrors('currency_code');
    }

    public function test_the_balance_precision_follows_the_new_currency(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('USD')->create(['name' => 'Dollar', 'initial_balance' => '10.50']);

        $response = $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Dollar',
            'currency_code' => 'VND',
            'initial_balance' => '10.50',
        ]));

        $response->assertSessionHasErrors('initial_balance');
        $this->assertSame('USD', $wallet->fresh()->currency_code);
    }

    public function test_a_status_in_the_payload_is_ignored(): void
    {
        $user = User::factory()->create();
        $wallet = $this->walletFor($user);

        $this->actingAs($user)->from('/wallets')->patch("/wallets/{$wallet->id}", $this->payload([
            'status' => 'archived',
        ]));

        $this->assertSame('active', $wallet->fresh()->status->value);
    }

    public function test_another_users_wallet_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $wallet = $this->walletFor($other);

        $response = $this->actingAs($user)->patch("/wallets/{$wallet->id}", $this->payload([
            'name' => 'Chiếm quyền',
        ]));

        $response->assertNotFound();
        $this->assertSame('Tiền mặt', $wallet->fresh()->name);
    }

    public function test_guest_cannot_update_a_wallet(): void
    {
        $wallet = $this->walletFor(User::factory()->create());

        $response = $this->patch("/wallets/{$wallet->id}", $this->payload());

        $response->assertRedirect('/login');
    }
}
