<?php

namespace App\Services;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordsService
{
    private const DURATION_SQL = 'COALESCE(duration_minutes, 0) * 60 + COALESCE(duration_seconds, 0)';

    public function getPersonalRecords(User $user): array
    {
        return [
            'coldest_dip' => $this->recordFromAvanto(
                Avanto::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('water_temperature')
                    ->orderBy('water_temperature')
                    ->first(),
                'water_temperature'
            ),
            'longest_dip' => $this->durationRecord(
                Avanto::query()
                    ->where('user_id', $user->id)
                    ->orderByRaw(self::DURATION_SQL.' DESC')
                    ->first()
            ),
            'most_swear_words' => $this->recordFromAvanto(
                Avanto::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('swear_words')
                    ->orderByDesc('swear_words')
                    ->first(),
                'swear_words'
            ),
            'best_mood_swing' => $this->moodRecord(
                Avanto::query()
                    ->where('user_id', $user->id)
                    ->whereNotNull('feeling_before')
                    ->whereNotNull('feeling_after')
                    ->select('*', DB::raw('(feeling_after - feeling_before) as mood_delta'))
                    ->orderByDesc('mood_delta')
                    ->first()
            ),
        ];
    }

    private function recordFromAvanto(?Avanto $avanto, string $valueKey): ?array
    {
        if (! $avanto) {
            return null;
        }

        return [
            'avanto_id' => $avanto->avanto_id,
            'date' => $avanto->date->toDateString(),
            'location' => $avanto->location,
            'value' => $avanto->{$valueKey},
        ];
    }

    private function durationRecord(?Avanto $avanto): ?array
    {
        if (! $avanto) {
            return null;
        }

        return [
            'avanto_id' => $avanto->avanto_id,
            'date' => $avanto->date->toDateString(),
            'location' => $avanto->location,
            'value' => $avanto->total_duration,
        ];
    }

    private function moodRecord(?Avanto $avanto): ?array
    {
        if (! $avanto) {
            return null;
        }

        return [
            'avanto_id' => $avanto->avanto_id,
            'date' => $avanto->date->toDateString(),
            'location' => $avanto->location,
            'value' => (int) ($avanto->mood_delta ?? ($avanto->feeling_after - $avanto->feeling_before)),
            'feeling_before' => $avanto->feeling_before,
            'feeling_after' => $avanto->feeling_after,
        ];
    }
}
