<?php

namespace Tests\Feature\Wallet;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreWalletTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid payload, overridden per test.
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
            'initial_balance' => 1000000,
            'description' => 'Ví chính',
        ], $overrides);
    }

    public function test_user_can_create_a_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload());

        $response->assertRedirect('/wallets');
        $response->assertSessionHas('status', 'Wallet created.');
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'currency_code' => 'VND',
            'initial_balance' => '1000000.0000',
            'description' => 'Ví chính',
            'status' => 'active',
        ]);
    }

    public function test_a_status_in_the_payload_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['status' => 'archived']));

        $this->assertDatabaseHas('wallets', ['name' => 'Tiền mặt', 'status' => 'active']);
    }

    public function test_a_user_id_in_the_payload_is_ignored(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['user_id' => $other->id]));

        $this->assertDatabaseHas('wallets', ['name' => 'Tiền mặt', 'user_id' => $user->id]);
        $this->assertSame(0, Wallet::where('user_id', $other->id)->count());
    }

    public function test_name_whitespace_is_trimmed_and_collapsed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload([
            'name' => "  Tiền   mặt \t chính  ",
        ]));

        $this->assertDatabaseHas('wallets', ['name' => 'Tiền mặt chính']);
    }

    public function test_the_same_name_is_rejected(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Tiền mặt']);

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload());

        $response->assertSessionHasErrors(['name' => 'You already have a wallet with this name.']);
        $this->assertSame(1, Wallet::where('user_id', $user->id)->count());
    }

    public function test_it_collides_with_an_archived_wallet(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->archived()->create(['name' => 'Tiền mặt']);

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload());

        $response->assertSessionHasErrors('name');
    }

    public function test_the_name_comparison_ignores_case_and_diacritics(): void
    {
        // utf8mb4_unicode_ci: "tien mat" and "Tiền Mặt" are the same string to MySQL.
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Tiền Mặt']);

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['name' => 'tien mat']));

        $response->assertSessionHasErrors('name');
    }

    public function test_another_users_wallet_does_not_collide(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Wallet::factory()->for($other)->currency('VND')->create(['name' => 'Tiền mặt']);

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload());

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'name' => 'Tiền mặt']);
    }

    public function test_an_inactive_currency_is_rejected_as_not_available(): void
    {
        $user = User::factory()->create();
        Currency::query()->where('code', 'USD')->update(['is_active' => false]);

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload([
            'currency_code' => 'USD',
            'initial_balance' => 10,
        ]));

        $response->assertSessionHasErrors(['currency_code' => 'The selected currency is not available.']);
        $this->assertSame(0, Wallet::where('user_id', $user->id)->count());
    }

    public function test_a_lowercase_currency_code_is_stored_upper_cased(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload([
            'currency_code' => 'usd',
            'initial_balance' => '10.50',
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'currency_code' => 'USD']);
    }

    public function test_a_zero_initial_balance_is_allowed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['initial_balance' => 0]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wallets', ['name' => 'Tiền mặt', 'initial_balance' => '0.0000']);
    }

    public function test_the_initial_balance_precision_follows_the_currency(): void
    {
        $user = User::factory()->create();

        $vnd = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['initial_balance' => 10.5]));
        // Validation errors are flashed for one request only, so assert before the next.
        $vnd->assertSessionHasErrors('initial_balance');
        $this->assertSame(0, Wallet::where('user_id', $user->id)->count());

        $usd = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload([
            'name' => 'Dollar',
            'currency_code' => 'USD',
            'initial_balance' => 10.50,
        ]));
        $usd->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id, 'currency_code' => 'USD']);
    }

    public function test_a_blank_description_is_stored_as_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload(['description' => '']));

        $this->assertDatabaseHas('wallets', ['name' => 'Tiền mặt', 'description' => null]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing name' => [['name' => null], 'name'],
            'empty name' => [['name' => ''], 'name'],
            'whitespace only name' => [['name' => '   '], 'name'],
            'name too long' => [['name' => str_repeat('a', 101)], 'name'],
            'name not a string' => [['name' => ['Tiền mặt']], 'name'],
            'missing type' => [['type' => null], 'type'],
            'unknown type' => [['type' => 'savings'], 'type'],
            'missing currency' => [['currency_code' => null], 'currency_code'],
            'unknown currency' => [['currency_code' => 'XXX'], 'currency_code'],
            'currency not a string' => [['currency_code' => ['VND']], 'currency_code'],
            'missing initial balance' => [['initial_balance' => null], 'initial_balance'],
            'negative initial balance' => [['initial_balance' => -1], 'initial_balance'],
            'initial balance too large' => [['initial_balance' => 1000000000000], 'initial_balance'],
            'initial balance not numeric' => [['initial_balance' => 'abc'], 'initial_balance'],
            'initial balance in exponent form' => [['initial_balance' => '1e3'], 'initial_balance'],
            'initial balance an array' => [['initial_balance' => ['1']], 'initial_balance'],
            'description too long' => [['description' => str_repeat('a', 256)], 'description'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_payloads(array $overrides, string $field): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload($overrides));

        $response->assertRedirect('/wallets');
        $response->assertSessionHasErrors($field);
        $this->assertSame(0, Wallet::where('user_id', $user->id)->count());
    }

    public function test_the_accepted_maximum_balance_is_stored(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->post('/wallets', $this->payload([
            'initial_balance' => 999999999999,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wallets', ['initial_balance' => '999999999999.0000']);
    }

    public function test_guest_cannot_create_a_wallet(): void
    {
        $response = $this->post('/wallets', $this->payload());

        $response->assertRedirect('/login');
    }

    public function test_the_name_collision_message_is_translated(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Tiền mặt']);

        $response = $this->actingAs($user)
            ->withCookie(LocaleService::COOKIE, 'vi')
            ->from('/wallets')
            ->post('/wallets', $this->payload());

        $response->assertSessionHasErrors(['name' => 'Bạn đã có ví với tên này.']);
    }

    public function test_the_precision_message_is_translated_with_the_attribute_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withCookie(LocaleService::COOKIE, 'vi')
            ->from('/wallets')
            ->post('/wallets', $this->payload(['initial_balance' => '10.5']));

        $response->assertSessionHasErrors([
            'initial_balance' => 'Số dư ban đầu phải là số nguyên với VND.',
        ]);
    }
}
