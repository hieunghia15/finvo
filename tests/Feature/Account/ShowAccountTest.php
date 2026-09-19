<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ShowAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_account(): void
    {
        $response = $this->get('/account');

        $response->assertRedirect('/login');
    }

    public function test_user_can_view_only_whitelisted_account_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.com',
        ]);

        $response = $this->actingAs($user)->get('/account');

        // The page file is added by the frontend task, so its existence is not checked yet.
        // The scoped closure fails on any key it does not assert, e.g. password or id.
        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Account/Index', false)
            ->has('account', fn (AssertableJson $account) => $account
                ->where('name', 'Nguyễn Văn A')
                ->where('email', 'nguyenvana@example.com')
                ->where('created_at', $user->created_at->toJSON())));
    }
}
