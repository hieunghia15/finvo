<?php

namespace Tests\Feature\Wallet;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unused_wallet_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')->delete("/wallets/{$wallet->id}");

        $response->assertRedirect('/wallets');
        $response->assertSessionHas('status', 'Wallet deleted.');
        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_a_wallet_with_transactions_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();
        Transaction::factory()->income()->for($user)->for($wallet)->create();

        $response = $this->actingAs($user)->from('/wallets')->delete("/wallets/{$wallet->id}");

        // Not a field error: it comes back as a flashed message instead.
        $response->assertRedirect('/wallets');
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('error', 'This wallet still has transactions and cannot be deleted.');
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
    }

    public function test_an_archived_wallet_without_transactions_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->archived()->create();

        $response = $this->actingAs($user)->from('/wallets')->delete("/wallets/{$wallet->id}");

        $response->assertSessionHas('status', 'Wallet deleted.');
        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
    }

    public function test_the_only_active_wallet_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')->delete("/wallets/{$wallet->id}");

        $response->assertSessionHas('status', 'Wallet deleted.');
        $this->assertSame(0, Wallet::where('user_id', $user->id)->count());
    }

    public function test_the_redirect_keeps_the_active_filters(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->archived()->create();

        $response = $this->actingAs($user)
            ->from('/wallets?include_archived=1')
            ->delete("/wallets/{$wallet->id}");

        $response->assertRedirect('/wallets?include_archived=1');
    }

    public function test_another_users_wallet_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $wallet = Wallet::factory()->for($other)->currency('VND')->create();

        $response = $this->actingAs($user)->delete("/wallets/{$wallet->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
    }

    public function test_guest_cannot_delete_a_wallet(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create();

        $response = $this->delete("/wallets/{$wallet->id}");

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
    }
}
