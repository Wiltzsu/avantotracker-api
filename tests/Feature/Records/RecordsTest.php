<?php

namespace Tests\Feature\Records;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_fetch_personal_records(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2026-01-01',
            'location' => 'Cold spot',
            'water_temperature' => 0.5,
            'duration_minutes' => 1,
            'duration_seconds' => 0,
            'swear_words' => 1,
            'feeling_before' => 2,
            'feeling_after' => 5,
        ]);

        Avanto::factory()->for($user)->create([
            'date' => '2026-02-01',
            'location' => 'Long spot',
            'water_temperature' => 2.0,
            'duration_minutes' => 6,
            'duration_seconds' => 0,
            'swear_words' => 5,
            'feeling_before' => 3,
            'feeling_after' => 9,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/records');

        $response->assertOk()
            ->assertJsonPath('data.coldest_dip.value', 0.5)
            ->assertJsonPath('data.longest_dip.value', 360)
            ->assertJsonPath('data.most_swear_words.value', 5)
            ->assertJsonPath('data.best_mood_swing.value', 6);
    }

    public function test_records_requires_authentication(): void
    {
        $this->getJson('/api/v1/records')->assertUnauthorized();
    }
}
