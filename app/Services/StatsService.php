<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;

class StatsService
{
    public function getUserStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $user->avantos();

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '>=', $endDate);
        }

        return [
            'total_visits' => $query->count(),
            'total_duration' => $this->getTotalDuration($query),
        ];
    }

    private function getTotalDuration($query): int
    {
        return $query->get()->sum(function ($avanto) {
            return ($avanto->duration_minutes * 60) + ($avanto->duration_seconds ?? 0);
        });
    }
}
