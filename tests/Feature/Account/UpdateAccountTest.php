<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)->patch('/account', ['name' => 'Nguyễn Văn A']);

        $response->assertRedirect('/account');
        $response->assertSessionHas('status', 'Account updated.');
        $this->assertSame('Nguyễn Văn A', $user->fresh()->name);
    }

    public function test_name_whitespace_is_trimmed_and_collapsed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/account', ['name' => "  Nguyễn   Văn \t A  "]);

        $this->assertSame('Nguyễn Văn A', $user->fresh()->name);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidNamePayloads(): array
    {
        return [
            'missing' => [[]],
            'empty' => [['name' => '']],
            'whitespace only' => [['name' => '   ']],
            'too long' => [['name' => str_repeat('a', 256)]],
            'not a string' => [['name' => ['Nguyễn Văn A']]],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidNamePayloads')]
    public function test_update_fails_with_invalid_name(array $payload): void
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)->from('/account')->patch('/account', $payload);

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('name');
        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_email_in_payload_is_ignored(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        $response = $this->actingAs($user)->patch('/account', [
            'name' => 'Nguyễn Văn A',
            'email' => 'changed@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Nguyễn Văn A', $user->fresh()->name);
        $this->assertSame('original@example.com', $user->fresh()->email);
    }

    public function test_guest_cannot_update_account(): void
    {
        $response = $this->patch('/account', ['name' => 'Nguyễn Văn A']);

        $response->assertRedirect('/login');
    }
}
