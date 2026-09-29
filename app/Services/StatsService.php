<?php

namespace App\Services;

use App\Models\Avanto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    private const DURATION_SQL = 'COALESCE(duration_minutes, 0) * 60 + COALESCE(duration_seconds, 0)';

    public function resolveDateRange(?string $range, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $now = Carbon::now();

        return match ($range) {
            'month' => [$now->copy()->subMonth()->startOfDay(), $now->copy()->endOfDay()],
            '6months' => [$now->copy()->subMonths(6)->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->subYear()->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                $startDate?->copy()->startOfDay(),
                $endDate?->copy()->endOfDay(),
            ],
            default => [
                $startDate?->copy()->startOfDay(),
                $endDate?->copy()->endOfDay(),
            ],
        };
    }

    public function getUserStats(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = $this->applyDateRange($this->baseQuery($user), $startDate, $endDate);
        $totalVisits = (clone $query)->count();
        $totalDuration = $this->sumDuration($query);

        $streaks = $this->calculateStreaks($user);

        return [
            'total_visits' => $totalVisits,
            'total_duration' => $totalDuration,
            'average_duration' => $totalVisits > 0 ? (int) round($totalDuration / $totalVisits) : 0,
            'longest_duration' => $this->longestDuration($query),
            'average_water_temperature' => $this->averageWaterTemperature($query),
            'coldest_water_temperature' => $this->coldestWaterTemperature($query),
            'total_swear_words' => (int) (clone $query)->sum('swear_words'),
            'total_sauna_sessions' => (int) (clone $query)->where('sauna', true)->count(),
            'total_sauna_duration' => (int) (clone $query)->where('sauna', true)->sum('sauna_duration'),
            'average_mood_improvement' => $this->averageMoodImprovement($query),
            'favorite_location' => $this->favoriteLocation($query),
            'this_week_visits' => $this->countVisitsBetween(
                $user,
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ),
            'this_month_visits' => $this->countVisitsBetween(
                $user,
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
            'current_streak_days' => $streaks['current'],
            'best_streak_days' => $streaks['best'],
            'visits_by_month' => $this->visitsByMonth($query),
            'location_breakdown' => $this->locationBreakdown($query),
            'sauna_breakdown' => $this->saunaBreakdown($query),
            'achievements' => $this->buildAchievements($user, $totalVisits, $totalDuration, $streaks),
            'period' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
            ],
        ];
    }

    public function calculateStreaks(User $user): array
    {
        $dates = $this->baseQuery($user)
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return ['current' => 0, 'best' => 0];
        }

        return [
            'current' => $this->currentStreakDays($dates),
            'best' => $this->bestStreakDays($dates),
        ];
    }

    public function getLastDipDate(User $user): ?Carbon
    {
        $date = $this->baseQuery($user)->max('date');

        return $date ? Carbon::parse($date) : null;
    }

    public function daysSinceLastDip(User $user): ?int
    {
        $lastDip = $this->getLastDipDate($user);

        if (! $lastDip) {
            return null;
        }

        return (int) $lastDip->startOfDay()->diffInDays(Carbon::now()->startOfDay());
    }

    private function baseQuery(User $user): Builder
    {
        return Avanto::query()->where('user_id', $user->id);
    }

    private function applyDateRange(Builder $query, ?Carbon $startDate, ?Carbon $endDate): Builder
    {
        if ($startDate) {
            $query->where('date', '>=', $startDate->toDateString());
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate->toDateString());
        }

        return $query;
    }

    private function sumDuration(Builder $query): int
    {
        return (int) (clone $query)->sum(DB::raw(self::DURATION_SQL));
    }

    private function longestDuration(Builder $query): int
    {
        return (int) ((clone $query)->max(DB::raw(self::DURATION_SQL)) ?? 0);
    }

    private function averageWaterTemperature(Builder $query): ?float
    {
        $average = (clone $query)->whereNotNull('water_temperature')->avg('water_temperature');

        return $average === null ? null : round((float) $average, 1);
    }

    private function coldestWaterTemperature(Builder $query): ?float
    {
        $coldest = (clone $query)->whereNotNull('water_temperature')->min('water_temperature');

        return $coldest === null ? null : round((float) $coldest, 1);
    }

    private function averageMoodImprovement(Builder $query): ?float
    {
        $average = (clone $query)
            ->whereNotNull('feeling_before')
            ->whereNotNull('feeling_after')
            ->selectRaw('AVG(feeling_after - feeling_before) as mood_delta')
            ->value('mood_delta');

        return $average === null ? null : round((float) $average, 1);
    }

    private function favoriteLocation(Builder $query): ?string
    {
        $row = (clone $query)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->select('location', DB::raw('COUNT(*) as visits'))
            ->groupBy('location')
            ->orderByDesc('visits')
            ->first();

        return $row?->location;
    }

    private function countVisitsBetween(User $user, Carbon $start, Carbon $end): int
    {
        return $this->applyDateRange($this->baseQuery($user), $start, $end)->count();
    }

    private function visitsByMonth(Builder $query): array
    {
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', date)"
            : "DATE_FORMAT(date, '%Y-%m')";

        return (clone $query)
            ->selectRaw("{$monthExpression} as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    private function locationBreakdown(Builder $query): array
    {
        return (clone $query)
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->select('location', DB::raw('COUNT(*) as visits'))
            ->groupBy('location')
            ->orderByDesc('visits')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'location' => $row->location,
                'visits' => (int) $row->visits,
            ])
            ->values()
            ->all();
    }

    private function saunaBreakdown(Builder $query): array
    {
        $withSauna = (int) (clone $query)->where('sauna', true)->count();
        $withoutSauna = (int) (clone $query)->where(function (Builder $builder) {
            $builder->where('sauna', false)->orWhereNull('sauna');
        })->count();

        return [
            'with_sauna' => $withSauna,
            'without_sauna' => $withoutSauna,
        ];
    }

    private function buildAchievements(User $user, int $totalVisits, int $totalDuration, array $streaks): array
    {
        $longestEver = $this->longestDuration($this->baseQuery($user));
        $allTimeVisits = $this->baseQuery($user)->count();

        return [
            [
                'id' => 'ice_king',
                'title' => 'Jääkuningas',
                'description' => '50 avantokertaa',
                'unlocked' => $allTimeVisits >= 50,
            ],
            [
                'id' => 'endurance',
                'title' => 'Kestävyysjuoksija',
                'description' => 'Yli 5 minuutin uinti',
                'unlocked' => $longestEver >= 300,
            ],
            [
                'id' => 'arctic_hero',
                'title' => 'Arktinen sankari',
                'description' => '100 avantokertaa',
                'unlocked' => $allTimeVisits >= 100,
            ],
            [
                'id' => 'week_warrior',
                'title' => 'Viikon voittaja',
                'description' => '7 päivän putki',
                'unlocked' => $streaks['best'] >= 7,
            ],
            [
                'id' => 'sauna_regular',
                'title' => 'Saunamies',
                'description' => '20 saunakertaa',
                'unlocked' => (int) $this->baseQuery($user)->where('sauna', true)->count() >= 20,
            ],
            [
                'id' => 'cold_heart',
                'title' => 'Kylmä sydän',
                'description' => '10 tuntia avannossa',
                'unlocked' => $this->sumDuration($this->baseQuery($user)) >= 36000,
            ],
        ];
    }

    private function currentStreakDays(Collection $datesDescending): int
    {
        $dateSet = $datesDescending->flip();
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        if ($dateSet->has($today)) {
            $cursor = Carbon::today();
        } elseif ($dateSet->has($yesterday)) {
            $cursor = Carbon::yesterday();
        } else {
            return 0;
        }

        $streak = 0;

        while ($dateSet->has($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function bestStreakDays(Collection $datesDescending): int
    {
        $sorted = $datesDescending
            ->sort()
            ->values();

        $best = 1;
        $current = 1;

        for ($i = 1; $i < $sorted->count(); $i++) {
            $previous = Carbon::parse($sorted[$i - 1]);
            $currentDate = Carbon::parse($sorted[$i]);

            if ($previous->copy()->addDay()->toDateString() === $currentDate->toDateString()) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 1;
            }
        }

        return $sorted->isEmpty() ? 0 : $best;
    }
}
