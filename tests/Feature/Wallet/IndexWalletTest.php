<?php

namespace Tests\Feature\Wallet;

use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class IndexWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_wallets(): void
    {
        $response = $this->get('/wallets');

        $response->assertRedirect('/login');
    }

    public function test_it_renders_the_wallets_index_component(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertOk();
        // The second argument turns off the "page file exists" check: the
        // React page is a separate task, so only the name is settled here.
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Wallets/Index', false));
    }

    public function test_it_sends_only_whitelisted_fields(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create([
            'name' => 'Tiền mặt',
            'type' => 'cash',
            'initial_balance' => 1000000,
            'description' => 'Ví chính',
        ]);

        $response = $this->actingAs($user)->get('/wallets');

        // The scoped closure fails on any key it does not assert, e.g. user_id
        // or updated_at.
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wallets', 1, fn (AssertableJson $wallet) => $wallet
                ->has('id')
                ->where('name', 'Tiền mặt')
                ->where('type', 'cash')
                ->where('currency_code', 'VND')
                ->where('initial_balance', '1000000.0000')
                ->where('current_balance', '1000000.0000')
                ->where('description', 'Ví chính')
                ->where('status', 'active')
                ->where('transactions_count', 0)
                ->has('created_at')));
    }

    public function test_it_computes_the_balance_and_counts_transactions(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create(['initial_balance' => 1000]);
        Transaction::factory()->income()->for($user)->for($wallet)->create(['amount' => 300]);
        Transaction::factory()->expense()->for($user)->for($wallet)->create(['amount' => 1500]);

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wallets.0.current_balance', '-200.0000')
            ->where('wallets.0.transactions_count', 2));
    }

    public function test_archived_wallets_are_hidden_by_default(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Tiền mặt']);
        Wallet::factory()->for($user)->currency('VND')->inactive()->create(['name' => 'Ngân hàng']);
        Wallet::factory()->for($user)->currency('VND')->archived()->create(['name' => 'Ví cũ']);

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wallets', 2)
            ->where('wallets.0.name', 'Tiền mặt')
            ->where('wallets.1.name', 'Ngân hàng'));
    }

    public function test_include_archived_shows_every_status(): void
    {
        $user = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create();
        Wallet::factory()->for($user)->currency('VND')->inactive()->create();
        Wallet::factory()->for($user)->currency('VND')->archived()->create();

        $response = $this->actingAs($user)->get('/wallets?include_archived=1');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wallets', 3)
            ->where('filters.include_archived', true));
    }

    public function test_filters_default_to_hiding_archived(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.include_archived', false));
    }

    public function test_wallets_are_listed_oldest_first_regardless_of_name(): void
    {
        $user = User::factory()->create();
        // Names are out of alphabetical order on purpose, so a name sort could
        // not produce the expected order by accident.
        $this->travelTo(Carbon::parse('2026-10-01 09:00:00'));
        $first = Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Zeta']);
        $this->travelTo(Carbon::parse('2026-10-02 09:00:00'));
        $second = Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Alpha']);
        $this->travelTo(Carbon::parse('2026-10-03 09:00:00'));
        $third = Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Mid']);

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wallets.0.id', $first->id)
            ->where('wallets.1.id', $second->id)
            ->where('wallets.2.id', $third->id));
    }

    public function test_wallets_created_in_the_same_second_are_ordered_by_id(): void
    {
        $user = User::factory()->create();
        $this->travelTo(Carbon::parse('2026-10-01 09:00:00'));
        $first = Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Zeta']);
        $second = Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Alpha']);

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wallets.0.id', $first->id)
            ->where('wallets.1.id', $second->id));
    }

    public function test_created_at_is_the_local_calendar_date(): void
    {
        $user = User::factory()->create();
        // 00:30 on 2026-10-02 in Vietnam is still 2026-10-01 in UTC.
        $this->travelTo(Carbon::parse('2026-10-02 00:30:00', 'Asia/Ho_Chi_Minh'));
        Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('wallets.0.created_at', '2026-10-02'));
    }

    public function test_it_only_lists_the_current_users_wallets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Wallet::factory()->for($user)->currency('VND')->create(['name' => 'Tiền mặt']);
        Wallet::factory()->for($other)->currency('VND')->create(['name' => 'Của người khác']);

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('wallets', 1)
            ->where('wallets.0.name', 'Tiền mặt'));
    }

    public function test_it_sends_every_currency_including_inactive_ones(): void
    {
        $user = User::factory()->create();
        Currency::query()->where('code', 'USD')->update(['is_active' => false]);
        // The factory's own currency would add an eleventh row to the seeded ten.
        Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->get('/wallets');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('currencies', 10)
            ->where('currencies.0.code', 'AUD')
            ->has('currencies.0', fn (AssertableJson $currency) => $currency
                ->has('code')
                ->has('name')
                ->has('symbol')
                ->has('decimal_places')
                ->has('is_active'))
            // Ordered by code, USD is ninth.
            ->where('currencies.8.code', 'USD')
            ->where('currencies.8.is_active', false));
    }

    public function test_an_invalid_include_archived_filter_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/wallets')->get('/wallets?include_archived=abc');

        $response->assertSessionHasErrors('include_archived');
    }
}
