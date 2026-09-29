<?php

namespace Tests\Feature\Dashboard;

use App\Models\Avanto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_dashboard_summary(): void
    {
        Carbon::setTestNow('2026-03-15');

        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2026-03-14',
            'location' => 'Seurasaari',
            'water_temperature' => 1.5,
            'duration_minutes' => 3,
            'duration_seconds' => 0,
            'swear_words' => 2,
            'feeling_before' => 3,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 10,
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2026-02-01',
            'location' => 'Other',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.monthly_snapshot.visits', 1)
            ->assertJsonPath('data.monthly_snapshot.total_duration', 180)
            ->assertJsonPath('data.days_since_last_dip', 1)
            ->assertJsonPath('data.last_dip_date', '2026-03-14')
            ->assertJsonPath('data.current_streak_days', 1)
            ->assertJsonPath('data.highlights.favorite_location', 'Seurasaari')
            ->assertJsonCount(2, 'data.recent_avantos');

        Carbon::setTestNow();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
