<?php

namespace Tests\Feature\Stats;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_stats_for_their_avantos(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2025-01-15',
            'duration_minutes' => 2,
            'duration_seconds' => 30,
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2025-06-15',
            'duration_minutes' => 3,
            'duration_seconds' => 0,
        ]);

        Avanto::factory()->create([
            'date' => '2025-06-15',
            'duration_minutes' => 10,
            'duration_seconds' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/stats');

        $response->assertOk()
            ->assertJsonPath('data.total_visits', 2)
            ->assertJsonPath('data.total_duration', 330);
    }

    public function test_stats_can_filter_by_start_date(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2025-01-15',
            'duration_minutes' => 2,
            'duration_seconds' => 0,
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2025-06-15',
            'duration_minutes' => 3,
            'duration_seconds' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/stats?start_date=2025-06-01');

        $response->assertOk()
            ->assertJsonPath('data.total_visits', 1)
            ->assertJsonPath('data.total_duration', 180);
    }

    public function test_stats_can_filter_by_end_date(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2025-01-15',
            'duration_minutes' => 2,
            'duration_seconds' => 0,
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2025-06-15',
            'duration_minutes' => 3,
            'duration_seconds' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/stats?end_date=2025-02-01');

        $response->assertOk()
            ->assertJsonPath('data.total_visits', 1)
            ->assertJsonPath('data.total_duration', 120);
    }

    public function test_stats_rejects_end_date_before_start_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/stats?start_date=2025-06-01&end_date=2025-01-01');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_stats_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/stats');

        $response->assertUnauthorized();
    }
}
