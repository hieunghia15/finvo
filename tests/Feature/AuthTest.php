<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
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
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_when_email_is_missing(): void
    {
        $response = $this->from('/login')->post('/login', [
            'password' => 'password',
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
            'password' => bcrypt('password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'john@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_regenerates_the_session(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->get('/login');
        $originalSessionId = $this->app['session']->getId();

        $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $this->assertNotSame($originalSessionId, $this->app['session']->getId());
        $this->assertAuthenticatedAs($user);
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
}
