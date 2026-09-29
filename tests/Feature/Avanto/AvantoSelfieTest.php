<?php

namespace Tests\Feature\Avanto;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvantoSelfieTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_delete_selfie(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $avanto = Avanto::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('selfie.jpg');

        $this->postJson("/api/v1/avanto/{$avanto->avanto_id}/selfie", [
            'selfie' => $file,
        ])->assertOk()
            ->assertJsonPath('data.selfie_url', fn ($url) => is_string($url) && $url !== '');

        $this->assertNotNull($avanto->fresh()->selfie_path);

        $this->deleteJson("/api/v1/avanto/{$avanto->avanto_id}/selfie")
            ->assertOk()
            ->assertJsonPath('data.selfie_url', null);

        $this->assertNull($avanto->fresh()->selfie_path);
    }

    public function test_user_cannot_upload_selfie_for_another_users_avanto(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $other = User::factory()->create();
        $avanto = Avanto::factory()->for($owner)->create();

        Sanctum::actingAs($other);

        $this->postJson("/api/v1/avanto/{$avanto->avanto_id}/selfie", [
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertNotFound();
    }
}
