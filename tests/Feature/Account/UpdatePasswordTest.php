<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use RefreshDatabase;

    private const CURRENT_PASSWORD = 'OldPassw0rd!';

    private const NEW_PASSWORD = 'NewPassw0rd!';

    /**
     * @param  array<string, mixed>  $overrides
     *
     * @return array<string, mixed>
     */
    private function passwordData(array $overrides = []): array
    {
        return array_merge([
            'current_password' => self::CURRENT_PASSWORD,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ], $overrides);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'email' => 'john@example.com',
            'password' => self::CURRENT_PASSWORD,
        ]);
    }

    public function test_user_can_change_password_and_stays_logged_in(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->put('/account/password', $this->passwordData());

        $response->assertRedirect('/account');
        $response->assertSessionHas('status', 'Password updated.');
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));

        // The session's stored password hash is refreshed, so "auth.session" keeps it alive.
        $this->get('/account')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_only_the_new_password_works_for_login_after_the_change(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->put('/account/password', $this->passwordData());
        $this->post('/logout');

        $this->post('/login', ['email' => 'john@example.com', 'password' => self::CURRENT_PASSWORD]);
        $this->assertGuest();

        $this->post('/login', ['email' => 'john@example.com', 'password' => self::NEW_PASSWORD]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_other_devices_are_logged_out(): void
    {
        Event::fake([OtherDeviceLogout::class]);

        $user = $this->createUser();

        $this->actingAs($user)->put('/account/password', $this->passwordData());

        Event::assertDispatched(OtherDeviceLogout::class, fn (OtherDeviceLogout $event) => $event->user->is($user));
    }

    public function test_change_fails_with_wrong_current_password(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->from('/account')->put('/account/password', $this->passwordData([
            'current_password' => 'Wrong-Pass1',
        ]));

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('current_password');
        $response->assertSessionMissing('_old_input.current_password');
        $response->assertSessionMissing('_old_input.password');
        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $user->fresh()->password));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<string>}>
     */
    public static function invalidPasswordPayloads(): array
    {
        return [
            'all fields missing' => [
                ['current_password' => null, 'password' => null, 'password_confirmation' => null],
                ['current_password', 'password'],
            ],
            'confirmation mismatch' => [
                ['password_confirmation' => 'Different1!'],
                ['password'],
            ],
            'weak new password' => [
                ['password' => 'abc123', 'password_confirmation' => 'abc123'],
                ['password'],
            ],
            'same as current password' => [
                ['password' => self::CURRENT_PASSWORD, 'password_confirmation' => self::CURRENT_PASSWORD],
                ['password'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<string>  $expectedErrors
     */
    #[DataProvider('invalidPasswordPayloads')]
    public function test_change_fails_with_invalid_input(array $overrides, array $expectedErrors): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->from('/account')->put('/account/password', $this->passwordData($overrides));

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors($expectedErrors);
        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $user->fresh()->password));
    }

    public function test_password_change_is_rate_limited_per_user_with_a_form_error(): void
    {
        $user = $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->from('/account')->put('/account/password', $this->passwordData([
                'current_password' => 'Wrong-Pass1',
            ]));
        }

        $response = $this->actingAs($user)->from('/account')->put('/account/password', $this->passwordData());

        $response->assertRedirect('/account');
        $response->assertSessionHasErrors('current_password');
        $this->assertStringStartsWith(
            'Too many password change attempts.',
            session('errors')->first('current_password'),
        );
        $response->assertSessionMissing('_old_input.current_password');
        $response->assertSessionMissing('_old_input.password');
        $response->assertSessionMissing('_old_input.password_confirmation');
        $this->assertTrue(Hash::check(self::CURRENT_PASSWORD, $user->fresh()->password));

        // The limit is per user, not per IP.
        $otherUser = User::factory()->create(['password' => self::CURRENT_PASSWORD]);
        $this->flushSession();

        $this->actingAs($otherUser)->put('/account/password', $this->passwordData())
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $otherUser->fresh()->password));
    }

    public function test_guest_cannot_change_password(): void
    {
        $response = $this->put('/account/password', $this->passwordData());

        $response->assertRedirect('/login');
    }
}
