<?php

namespace Tests\Feature\Flow;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_create_dashboard_stats_and_export_flow(): void
    {
        Carbon::setTestNow('2026-09-30 12:00:00');

        $user = User::factory()->create([
            'email' => 'dipper@example.com',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'dipper@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $create = $this->withToken($token)->postJson('/api/v1/avanto', [
            'date' => '2026-09-30',
            'location' => 'Smoke Test Bay',
            'water_temperature' => 1.0,
            'duration_minutes' => 2,
            'duration_seconds' => 0,
            'feeling_before' => 3,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 10,
        ])->assertCreated()
            ->assertJsonPath('new_achievements.0.id', 'first_dip');

        $avantoId = $create->json('data.avanto_id');

        $this->assertDatabaseHas('new_avanto', [
            'avanto_id' => $avantoId,
            'location' => 'Smoke Test Bay',
        ]);

        $dashboard = $this->withToken($token)->getJson('/api/v1/dashboard');

        $dashboard->assertOk()
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

        Carbon::setTestNow();
    }
}
