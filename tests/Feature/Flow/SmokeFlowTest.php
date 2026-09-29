<?php

namespace Tests\Feature\Flow;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_create_dashboard_stats_and_export_flow(): void
    {
        $user = User::factory()->create([
            'email' => 'dipper@example.com',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'dipper@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $create = $this->withToken($token)->postJson('/api/v1/avanto', [
            'date' => now()->toDateString(),
            'location' => 'Smoke Test Bay',
            'water_temperature' => 1.0,
            'duration_minutes' => 2,
            'duration_seconds' => 0,
            'feeling_before' => 3,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 10,
        ])->assertCreated();

        $avantoId = $create->json('data.avanto_id');

        $this->withToken($token)->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.monthly_snapshot.visits', 1)
            ->assertJsonPath('data.recent_avantos.0.location', 'Smoke Test Bay');

        $this->withToken($token)->getJson('/api/v1/stats')
            ->assertOk()
            ->assertJsonPath('data.total_visits', 1)
            ->assertJsonCount(1, 'data.mood_timeline');

        $this->withToken($token)->getJson('/api/v1/records')
            ->assertOk()
            ->assertJsonPath('data.longest_dip.avanto_id', $avantoId);

        $export = $this->withToken($token)->get('/api/v1/avanto/export');

        $export->assertOk();
        $this->assertStringContainsString('Smoke Test Bay', $export->streamedContent());
    }
}
