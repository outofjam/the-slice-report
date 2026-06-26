<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Register
    // -----------------------------------------------------------------------

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Pizza',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_register_returns_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Jane Pizza',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_register_requires_name(): void
    {
        $this->postJson('/api/v1/register', [
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/register', [
            'name' => 'Jane Pizza',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Jane Pizza',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_minimum_password_length(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Jane Pizza',
            'email' => 'jane@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // -----------------------------------------------------------------------
    // Login
    // -----------------------------------------------------------------------

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertUnprocessable();
    }

    public function test_login_requires_email_field(): void
    {
        $this->postJson('/api/v1/login', [
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_password_field(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    // -----------------------------------------------------------------------
    // Logout
    // -----------------------------------------------------------------------

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertNoContent();
    }

    public function test_logout_requires_auth(): void
    {
        $this->postJson('/api/v1/logout')
            ->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // Current user
    // -----------------------------------------------------------------------

    public function test_user_endpoint_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_endpoint_requires_auth(): void
    {
        $this->getJson('/api/v1/user')
            ->assertUnauthorized();
    }
}
