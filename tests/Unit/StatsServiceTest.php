<?php

namespace Tests\Unit;

use App\Models\Avanto;
use App\Models\User;
use App\Services\StatsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private StatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatsService;
    }

    public function test_it_sums_duration_in_sql_without_loading_all_rows(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'duration_minutes' => 2,
            'duration_seconds' => 30,
        ]);

        Avanto::factory()->for($user)->create([
            'duration_minutes' => 1,
            'duration_seconds' => 15,
        ]);

        $stats = $this->service->getUserStats($user);

        $this->assertSame(2, $stats['total_visits']);
        $this->assertSame(225, $stats['total_duration']);
        $this->assertSame(113, $stats['average_duration']);
        $this->assertSame(150, $stats['longest_duration']);
    }

    public function test_it_calculates_temperature_mood_and_sauna_stats(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'location' => 'Seurasaari',
            'water_temperature' => 1.0,
            'duration_minutes' => 2,
            'duration_seconds' => 0,
            'swear_words' => 2,
            'feeling_before' => 3,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 15,
        ]);

        Avanto::factory()->for($user)->create([
            'location' => 'Seurasaari',
            'water_temperature' => 3.0,
            'duration_minutes' => 1,
            'duration_seconds' => 0,
            'swear_words' => 1,
            'feeling_before' => 4,
            'feeling_after' => 6,
            'sauna' => false,
        ]);

        $stats = $this->service->getUserStats($user);

        $this->assertSame(2.0, $stats['average_water_temperature']);
        $this->assertSame(1.0, $stats['coldest_water_temperature']);
        $this->assertSame(3, $stats['total_swear_words']);
        $this->assertSame(1, $stats['total_sauna_sessions']);
        $this->assertSame(15, $stats['total_sauna_duration']);
        $this->assertSame(3.5, $stats['average_mood_improvement']);
        $this->assertSame('Seurasaari', $stats['favorite_location']);
        $this->assertSame(['with_sauna' => 1, 'without_sauna' => 1], $stats['sauna_breakdown']);
    }

    public function test_it_builds_monthly_and_location_breakdowns(): void
    {
        Carbon::setTestNow('2026-03-15');

        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2026-01-10',
            'location' => 'Lauttasaari',
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2026-03-05',
            'location' => 'Seurasaari',
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2026-03-12',
            'location' => 'Seurasaari',
        ]);

        $stats = $this->service->getUserStats($user);

        $this->assertSame([
            ['month' => '2026-01', 'count' => 1],
            ['month' => '2026-03', 'count' => 2],
        ], $stats['visits_by_month']);

        $this->assertSame([
            ['location' => 'Seurasaari', 'visits' => 2],
            ['location' => 'Lauttasaari', 'visits' => 1],
        ], $stats['location_breakdown']);

        Carbon::setTestNow();
    }

    public function test_it_calculates_current_and_best_streaks(): void
    {
        Carbon::setTestNow('2026-03-15');

        $user = User::factory()->create();

        foreach (['2026-03-13', '2026-03-14', '2026-03-15'] as $date) {
            Avanto::factory()->for($user)->create(['date' => $date]);
        }

        Avanto::factory()->for($user)->create(['date' => '2026-02-01']);
        Avanto::factory()->for($user)->create(['date' => '2026-02-02']);

        $streaks = $this->service->calculateStreaks($user);

        $this->assertSame(3, $streaks['current']);
        $this->assertSame(3, $streaks['best']);

        Carbon::setTestNow();
    }

    public function test_it_unlocks_achievements_based_on_all_time_stats(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'duration_minutes' => 6,
            'duration_seconds' => 0,
            'sauna' => true,
        ]);

        $stats = $this->service->getUserStats($user);
        $achievements = collect($stats['achievements'])->keyBy('id');

        $this->assertFalse($achievements['ice_king']['unlocked']);
        $this->assertTrue($achievements['endurance']['unlocked']);
        $this->assertFalse($achievements['arctic_hero']['unlocked']);
    }

    public function test_it_resolves_preset_date_ranges(): void
    {
        Carbon::setTestNow('2026-06-15 12:00:00');

        [$monthStart, $monthEnd] = $this->service->resolveDateRange('month', null, null);
        $this->assertSame('2026-05-15', $monthStart->toDateString());
        $this->assertSame('2026-06-15', $monthEnd->toDateString());

        [$yearStart, $yearEnd] = $this->service->resolveDateRange('year', null, null);
        $this->assertSame('2025-06-15', $yearStart->toDateString());

        Carbon::setTestNow();
    }
}
