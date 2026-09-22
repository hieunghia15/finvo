<?php

namespace Tests\Feature;

use App\Enums\EntityStatus;
use App\Enums\WalletType;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserOnboardingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Login
    // =========================================================================

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Login/Index'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Passw0rd!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Passw0rd!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Correct-Pass1'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Wrong-Pass1',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'Passw0rd!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'Passw0rd!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_when_email_is_missing(): void
    {
        $response = $this->from('/login')->post('/login', [
            'password' => 'Passw0rd!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_fails_when_password_is_missing(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        RateLimiter::clear('john@example.com|127.0.0.1');

        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Passw0rd!'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'john@example.com',
                'password' => 'Wrong-Pass1',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Passw0rd!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_request_lowercases_the_email_in_credentials(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'John@Example.COM',
            'password' => 'Passw0rd!',
        ]);
        $request->setContainer($this->app)->setRedirector($this->app['redirect']);

        $request->validateResolved();

        $this->assertSame('john@example.com', $request->credentials()['email']);
    }

    public function test_user_can_login_with_email_in_different_case(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Passw0rd!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'JOHN@Example.com',
            'password' => 'Passw0rd!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_route_is_rate_limited_per_ip_with_a_form_error(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'email' => "user{$i}@example.com",
                'password' => 'Wrong-Pass1',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'another@example.com',
            'password' => 'Wrong-Pass1',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertStringStartsWith('Too many login attempts.', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_login_regenerates_the_session(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('Passw0rd!'),
        ]);

        $this->get('/login');
        $originalSessionId = $this->app['session']->getId();

        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'Passw0rd!',
        ]);

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
        $this->assertAuthenticatedAs($user);
    }

    // =========================================================================
    // Register
    // =========================================================================

    /**
     * @param  array<string, mixed>  $overrides
     *
     * @return array<string, mixed>
     */
    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ], $overrides);
    }

    public function test_guest_can_view_register_page(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Register/Index'));
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', $this->registrationData([
            'name' => '  John Doe  ',
            'email' => '  John@Example.COM ',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'Account created. Please log in.');
        $this->assertGuest();

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('John Doe', $user->name);
        $this->assertNotSame('Secret123!', $user->password);
        $this->assertTrue(Hash::check('Secret123!', $user->password));
    }

    public function test_registration_creates_default_wallet_and_categories(): void
    {
        $this->post('/register', $this->registrationData())->assertRedirect('/login');

        $user = User::where('email', 'john@example.com')->firstOrFail();

        $wallet = $user->wallets()->sole();
        $this->assertSame(UserOnboardingService::DEFAULT_WALLET_NAME, $wallet->name);
        $this->assertSame(WalletType::Bank, $wallet->type);
        $this->assertSame('VND', $wallet->currency_code);
        $this->assertSame('0.0000', $wallet->initial_balance);
        $this->assertSame(EntityStatus::Active, $wallet->status);

        foreach (UserOnboardingService::DEFAULT_CATEGORIES as $type => $names) {
            $this->assertEqualsCanonicalizing(
                $names,
                $user->categories()->where('type', $type)->pluck('name')->all(),
            );
        }
    }

    public function test_registration_is_rolled_back_when_default_data_fails(): void
    {
        $this->mock(UserOnboardingService::class)
            ->shouldReceive('createDefaults')
            ->andThrow(new RuntimeException('Onboarding failed'));

        try {
            app(AuthService::class)->register([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'Secret123!',
            ]);
            $this->fail('Expected the onboarding failure to propagate.');
        } catch (RuntimeException $e) {
            $this->assertSame('Onboarding failed', $e->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fires_registered_event(): void
    {
        Event::fake([Registered::class]);

        $this->post('/register', $this->registrationData());

        Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user instanceof User
            && $event->user->email === 'john@example.com');
    }

    public function test_login_page_shows_status_after_registration(): void
    {
        $this->post('/register', $this->registrationData());

        $response = $this->get('/login');

        $response->assertInertia(fn ($page) => $page
            ->component('Login/Index')
            ->where('flash.status', 'Account created. Please log in.'));
    }

    public function test_registration_fails_when_fields_are_missing(): void
    {
        $response = $this->from('/register')->post('/register', []);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_with_taken_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->from('/register')->post('/register', $this->registrationData());

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_fails_with_taken_email_in_different_case(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->from('/register')->post('/register', $this->registrationData([
            'email' => 'JOHN@Example.com',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_register_service_reports_duplicate_email_from_concurrent_insert_as_validation_error(): void
    {
        // Simulates a request that passed the "unique" rule before a
        // concurrent request inserted the same email.
        User::factory()->create(['email' => 'john@example.com']);

        try {
            app(AuthService::class)->register([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'Secret123!',
            ]);
            $this->fail('Expected a ValidationException for the duplicate email.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['The email has already been taken.']], $e->errors());
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->from('/register')->post('/register', $this->registrationData([
            'password' => '12345',
            'password_confirmation' => '12345',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_with_mismatched_password_confirmation(): void
    {
        $response = $this->from('/register')->post('/register', $this->registrationData([
            'password_confirmation' => 'Different123!',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_fails_when_name_or_email_is_too_long(): void
    {
        $response = $this->from('/register')->post('/register', $this->registrationData([
            'name' => str_repeat('a', 256),
            'email' => str_repeat('a', 250).'@example.com',
        ]));

        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_authenticated_user_cannot_register(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/register', $this->registrationData([
            'email' => 'other@example.com',
        ]));

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('users', ['email' => 'other@example.com']);
    }

    public function test_registration_is_rate_limited_with_a_form_error(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/register')->post('/register', []);
        }

        $response = $this->from('/register')->post('/register', $this->registrationData());

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email' => 'Too many registration attempts. Please try again in 60 seconds.']);
        $response->assertSessionMissing('_old_input.password');
        $this->assertDatabaseCount('users', 0);
    }

    // =========================================================================
    // Authenticated user shared props
    // =========================================================================

    public function test_authenticated_user_is_shared_with_inertia_without_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('auth.user.id', $user->id)
            ->where('auth.user.email', $user->email)
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token'));
    }

    // =========================================================================
    // Logout
    // =========================================================================

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_logout(): void
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/login');
    }

    // =========================================================================
    // Protected routes
    // =========================================================================

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_authenticated_user_is_redirected_to_dashboard_when_accessing_guest_routes(): void
    {
        $user = User::factory()->create();

        $responseHome = $this->actingAs($user)->get('/');
        $responseHome->assertRedirect('/dashboard');

        $responseLogin = $this->actingAs($user)->get('/login');
        $responseLogin->assertRedirect('/dashboard');

        $responseRegister = $this->actingAs($user)->get('/register');
        $responseRegister->assertRedirect('/dashboard');
    }

    public function test_session_is_logged_out_after_the_password_changes_elsewhere(): void
    {
        $user = User::factory()->create();

        // Stores the current password hash in this session.
        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Another device changes the password.
        $user->forceFill(['password' => 'NewPassw0rd!'])->save();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
