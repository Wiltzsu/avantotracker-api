<?php

namespace App\Services;

use App\Models\Avanto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function getUserStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Avanto::query()->where('user_id', $user->id);

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return [
            'total_visits' => (clone $query)->count(),
            'total_duration' => $this->getTotalDuration($query),
        ];
    }

    private function getTotalDuration(Builder $query): int
    {
        return (int) (clone $query)->sum(
            DB::raw('COALESCE(duration_minutes, 0) * 60 + COALESCE(duration_seconds, 0)')
        );
    }
}
