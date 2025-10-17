<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvantoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'avanto_id' => $this->avanto_id,
            'user_id' => $this->user_id,
            'date' => $this->date,
            'location' => $this->location,
            'water_temperature' => $this->water_temperature,
            'duration_minutes' => $this->duration_minutes,
            'duration_seconds' => $this->duration_seconds,
            'swear_words' => $this->swear_words,
            'feeling_before' => $this->feeling_before,
            'feeling_after' => $this->feeling_after,
            'sauna' => $this->sauna,
            'sauna_duration' => $this->sauna_duration,
        ];
    }
}
