<?php

namespace Tests\Feature\Avanto;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvantoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_avantos(): void
    {
        $user = User::factory()->create();
        Avanto::factory()->count(2)->for($user)->create();
        Avanto::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/avanto');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['avanto_id', 'user_id', 'date', 'location'],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_user_can_create_an_avanto(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/avanto', [
            'date' => '2025-09-29',
            'location' => 'Helsinki',
            'water_temperature' => 4.5,
            'duration_minutes' => 3,
            'duration_seconds' => 30,
            'feeling_before' => 4,
            'feeling_after' => 8,
            'sauna' => true,
            'sauna_duration' => 15,
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Avanto session created successfully')
            ->assertJsonPath('data.location', 'Helsinki')
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonStructure([
                'new_achievements' => [
                    ['id', 'title', 'description', 'unlocked'],
                ],
            ])
            ->assertJsonPath('new_achievements.0.id', 'first_dip');

        $this->assertDatabaseHas('new_avanto', [
            'user_id' => $user->id,
            'location' => 'Helsinki',
        ]);
    }

    public function test_create_avanto_ignores_user_id_tampering(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/avanto', [
            'user_id' => $otherUser->id,
            'date' => '2025-09-29',
            'location' => 'Helsinki',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->assertDatabaseMissing('new_avanto', [
            'user_id' => $otherUser->id,
            'location' => 'Helsinki',
        ]);
    }

    public function test_create_avanto_requires_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/avanto', [
            'location' => 'Helsinki',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_user_can_view_their_avanto(): void
    {
        $user = User::factory()->create();
        $avanto = Avanto::factory()->for($user)->create([
            'location' => 'Espoo',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/avanto/{$avanto->avanto_id}");

        $response->assertOk()
            ->assertJsonPath('data.avanto_id', $avanto->avanto_id)
            ->assertJsonPath('data.location', 'Espoo');
    }

    public function test_user_cannot_view_another_users_avanto(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $avanto = Avanto::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/avanto/{$avanto->avanto_id}");

        $response->assertNotFound();
    }

    public function test_user_can_update_their_avanto(): void
    {
        $user = User::factory()->create();
        $avanto = Avanto::factory()->for($user)->create([
            'location' => 'Old location',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/avanto/{$avanto->avanto_id}", [
            'location' => 'New location',
            'duration_minutes' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Avanto session updated successfully')
            ->assertJsonPath('data.location', 'New location');

        $this->assertDatabaseHas('new_avanto', [
            'avanto_id' => $avanto->avanto_id,
            'location' => 'New location',
            'duration_minutes' => 5,
        ]);
    }

    public function test_user_cannot_update_another_users_avanto(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $avanto = Avanto::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/avanto/{$avanto->avanto_id}", [
            'location' => 'Hacked',
        ]);

        $response->assertNotFound();
    }

    public function test_user_can_delete_their_avanto(): void
    {
        $user = User::factory()->create();
        $avanto = Avanto::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/avanto/{$avanto->avanto_id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Avanto session deleted successfully');

        $this->assertDatabaseMissing('new_avanto', [
            'avanto_id' => $avanto->avanto_id,
        ]);
    }

    public function test_user_cannot_delete_another_users_avanto(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $avanto = Avanto::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/avanto/{$avanto->avanto_id}");

        $response->assertNotFound();
    }
}
