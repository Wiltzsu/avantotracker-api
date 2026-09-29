<?php

namespace Tests\Feature\Avanto;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvantoFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_filter_avantos_by_location_and_sauna(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'location' => 'Seurasaari',
            'date' => '2026-01-10',
            'sauna' => true,
        ]);

        Avanto::factory()->for($user)->create([
            'location' => 'Lauttasaari',
            'date' => '2026-02-10',
            'sauna' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/avanto?location=Seurasaari')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/avanto?sauna=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_filter_avantos_by_date_range(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create(['date' => '2026-01-10']);
        Avanto::factory()->for($user)->create(['date' => '2026-03-10']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/avanto?start_date=2026-02-01&end_date=2026-03-31')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
