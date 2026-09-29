<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegisterRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_is_rate_limited(): void
    {
        RateLimiter::clear('127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'Password1',
                'password_confirmation' => 'Password1',
            ])->assertCreated();
        }

        $response = $this->postJson('/api/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ]);

        $response->assertStatus(429);
    }
}
