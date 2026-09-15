<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Login
    // =========================================================================

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Login successful.',
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid credentials.',
            ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_fails_when_email_is_missing(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_when_password_is_missing(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_response_does_not_expose_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $userData = $response->json('data.user');
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    // =========================================================================
    // Current user
    // =========================================================================

    public function test_authenticated_user_can_get_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email']]])
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_unauthenticated_user_cannot_get_current_user(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    // =========================================================================
    // Logout
    // =========================================================================

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout successful.',
            ]);

        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // =========================================================================
    // Protected routes
    // =========================================================================

    public function test_protected_api_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_is_redirected_to_dashboard_when_accessing_guest_routes(): void
    {
        $user = User::factory()->create();

        $responseHome = $this->actingAs($user)->get('/');
        $responseHome->assertRedirect('/dashboard');

        $responseRegister = $this->actingAs($user)->get('/register');
        $responseRegister->assertRedirect('/dashboard');
    }
}
