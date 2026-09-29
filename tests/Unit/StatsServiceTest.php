<?php

namespace Tests\Unit;

use App\Models\Avanto;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_duration_in_sql_without_loading_all_rows(): void
    {
        $user = User::factory()->create();
        $service = new StatsService;

        Avanto::factory()->for($user)->create([
            'duration_minutes' => 2,
            'duration_seconds' => 30,
        ]);

        Avanto::factory()->for($user)->create([
            'duration_minutes' => 1,
            'duration_seconds' => 15,
        ]);

        $stats = $service->getUserStats($user);

        $this->assertSame(2, $stats['total_visits']);
        $this->assertSame(225, $stats['total_duration']);
    }
}
