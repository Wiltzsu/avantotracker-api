<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear(Str::transliterate('login@example.com|127.0.0.1'));
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ])
            ->assertJsonPath('token_type', 'Bearer');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_requires_valid_input(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['token', 'token_type']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'login@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_login_revokes_existing_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);

        $oldToken = $user->createToken('auth_token')->plainTextToken;

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertOk();

        $this->withToken($oldToken)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out successfully');
    }

    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }
}
