<?php

namespace Tests\Feature\Avanto;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvantoExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_their_avantos_as_csv(): void
    {
        $user = User::factory()->create();

        Avanto::factory()->for($user)->create([
            'date' => '2026-01-10',
            'location' => 'Seurasaari',
            'water_temperature' => 1.5,
            'duration_minutes' => 2,
            'duration_seconds' => 30,
            'swear_words' => 1,
            'feeling_before' => 3,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 10,
        ]);

        Sanctum::actingAs($user);

        $response = $this->get('/api/v1/avanto/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('date,location,water_temperature', $content);
        $this->assertStringContainsString('Seurasaari', $content);
        $this->assertStringContainsString('2026-01-10', $content);
    }

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/v1/avanto/export')->assertUnauthorized();
    }
}
