<?php

namespace App\Services;

use App\Http\Resources\AvantoResource;
use App\Models\User;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(private StatsService $statsService)
    {
    }

    public function getDashboard(User $user): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $monthStats = $this->statsService->getUserStats($user, $monthStart, $monthEnd);
        $streaks = $this->statsService->calculateStreaks($user);
        $lastDip = $this->statsService->getLastDipDate($user);

        $recentAvantos = $user->avantos()
            ->orderByDesc('date')
            ->orderByDesc('avanto_id')
            ->limit(5)
            ->get();

        return [
            'monthly_snapshot' => [
                'label' => Carbon::now()->format('Y-m'),
                'visits' => $monthStats['total_visits'],
                'total_duration' => $monthStats['total_duration'],
                'average_duration' => $monthStats['average_duration'],
                'sauna_sessions' => $monthStats['total_sauna_sessions'],
                'average_water_temperature' => $monthStats['average_water_temperature'],
            ],
            'days_since_last_dip' => $this->statsService->daysSinceLastDip($user),
            'last_dip_date' => $lastDip?->toDateString(),
            'current_streak_days' => $streaks['current'],
            'best_streak_days' => $streaks['best'],
            'recent_avantos' => AvantoResource::collection($recentAvantos)->resolve(),
            'highlights' => [
                'coldest_water_temperature' => $monthStats['coldest_water_temperature'],
                'longest_duration' => $monthStats['longest_duration'],
                'total_swear_words' => $monthStats['total_swear_words'],
                'favorite_location' => $monthStats['favorite_location'],
                'average_mood_improvement' => $monthStats['average_mood_improvement'],
            ],
        ];
    }
}
