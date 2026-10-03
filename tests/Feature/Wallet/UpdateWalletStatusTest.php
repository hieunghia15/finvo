<?php

namespace Tests\Feature\Wallet;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWalletStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_wallet_can_be_archived(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'archived']);

        $response->assertRedirect('/wallets');
        $response->assertSessionHas('status', 'Wallet status updated.');
        $this->assertSame('archived', $wallet->fresh()->status->value);
    }

    public function test_an_archived_wallet_can_be_restored(): void
    {
        // The details of an archived wallet are locked, but its status is
        // not: that is the whole reason this endpoint is separate.
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->archived()->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'active']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('active', $wallet->fresh()->status->value);
    }

    public function test_an_archived_wallet_can_move_straight_to_inactive(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->archived()->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'inactive']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('inactive', $wallet->fresh()->status->value);
    }

    public function test_a_used_wallet_can_still_change_status(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();
        Transaction::factory()->income()->for($user)->for($wallet)->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'archived']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('archived', $wallet->fresh()->status->value);
    }

    public function test_the_only_active_wallet_can_be_archived(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'archived']);

        $response->assertSessionHasNoErrors();
        $this->assertSame('archived', $wallet->fresh()->status->value);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'deleted']);

        $response->assertSessionHasErrors('status');
        $this->assertSame('active', $wallet->fresh()->status->value);
    }

    public function test_a_missing_status_is_rejected(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->currency('VND')->create();

        $response = $this->actingAs($user)->from('/wallets')
            ->patch("/wallets/{$wallet->id}/status", []);

        $response->assertSessionHasErrors('status');
    }

    public function test_another_users_wallet_is_not_found(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $wallet = Wallet::factory()->for($other)->currency('VND')->create();

        $response = $this->actingAs($user)
            ->patch("/wallets/{$wallet->id}/status", ['status' => 'archived']);

        $response->assertNotFound();
        $this->assertSame('active', $wallet->fresh()->status->value);
    }

    public function test_guest_cannot_change_a_status(): void
    {
        $wallet = Wallet::factory()->currency('VND')->create();

        $response = $this->patch("/wallets/{$wallet->id}/status", ['status' => 'archived']);

        $response->assertRedirect('/login');
    }
}
