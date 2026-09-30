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
            'mood_timeline' => $this->moodTimeline($query),
            'period' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate?->toDateString(),
            ],
        ];
    }

    public function achievementsForUser(User $user): array
    {
        $streaks = $this->calculateStreaks($user);

        return $this->buildAchievements($user, 0, 0, $streaks);
    }

    /**
     * @return list<string>
     */
    public function unlockedAchievementIds(User $user): array
    {
        return collect($this->achievementsForUser($user))
            ->filter(fn (array $achievement) => $achievement['unlocked'])
            ->pluck('id')
            ->all();
    }

    /**
     * @param  list<string>  $previouslyUnlockedIds
     * @return list<array{id: string, title: string, description: string, unlocked: true}>
     */
    public function newlyUnlockedAchievements(User $user, array $previouslyUnlockedIds): array
    {
        $previous = collect($previouslyUnlockedIds);

        return collect($this->achievementsForUser($user))
            ->filter(fn (array $achievement) => $achievement['unlocked'] && ! $previous->contains($achievement['id']))
            ->map(fn (array $achievement) => [
                'id' => $achievement['id'],
                'title' => $achievement['title'],
                'description' => $achievement['description'],
                'unlocked' => true,
            ])
            ->values()
            ->all();
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
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
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

    private function moodTimeline(Builder $query): array
    {
        return (clone $query)
            ->whereNotNull('feeling_before')
            ->whereNotNull('feeling_after')
            ->orderBy('date')
            ->get(['avanto_id', 'date', 'feeling_before', 'feeling_after'])
            ->map(fn (Avanto $avanto) => [
                'avanto_id' => $avanto->avanto_id,
                'date' => $avanto->date->toDateString(),
                'feeling_before' => $avanto->feeling_before,
                'feeling_after' => $avanto->feeling_after,
                'mood_delta' => $avanto->feeling_after - $avanto->feeling_before,
            ])
            ->values()
            ->all();
    }

    private function buildAchievements(User $user, int $totalVisits, int $totalDuration, array $streaks): array
    {
        $metrics = $this->achievementMetrics($user, $streaks);

        return [
            [
                'id' => 'first_dip',
                'title' => 'Ensimmäinen askel',
                'description' => 'Ensimmäinen avantosi on kirjattu',
                'unlocked' => $metrics['all_time_visits'] >= 1,
            ],
            [
                'id' => 'regular',
                'title' => 'Vakiokävijä',
                'description' => '10 avantokertaa yhteensä',
                'unlocked' => $metrics['all_time_visits'] >= 10,
            ],
            [
                'id' => 'winter_starter',
                'title' => 'Talven aloittaja',
                'description' => 'Avanto marraskuusta maaliskuuhun',
                'unlocked' => $metrics['winter_month_dips'] >= 1,
            ],
            [
                'id' => 'ice_king',
                'title' => 'Jääkuningas',
                'description' => '50 avantokertaa yhteensä',
                'unlocked' => $metrics['all_time_visits'] >= 50,
            ],
            [
                'id' => 'arctic_hero',
                'title' => 'Arktinen sankari',
                'description' => '100 avantokertaa yhteensä',
                'unlocked' => $metrics['all_time_visits'] >= 100,
            ],
            [
                'id' => 'sub_zero',
                'title' => 'Pakkasen puolella',
                'description' => 'Veden lämpötila 0 °C tai alle',
                'unlocked' => $metrics['has_sub_zero_dip'],
            ],
            [
                'id' => 'ice_block',
                'title' => 'Jääpalas',
                'description' => 'Veden lämpötila −1 °C tai alempi',
                'unlocked' => $metrics['coldest_ever'] !== null && $metrics['coldest_ever'] <= -1,
            ],
            [
                'id' => 'minute_man',
                'title' => 'Minuuttimestari',
                'description' => 'Yksi uinti kestää vähintään minuutin',
                'unlocked' => $metrics['longest_ever'] >= 60,
            ],
            [
                'id' => 'three_minute',
                'title' => 'Kolmen minuutin seikkailu',
                'description' => 'Yksi uinti kestää vähintään 3 minuuttia',
                'unlocked' => $metrics['longest_ever'] >= 180,
            ],
            [
                'id' => 'endurance',
                'title' => 'Kestävyusuintija',
                'description' => 'Yksi uinti kestää yli 5 minuuttia',
                'unlocked' => $metrics['longest_ever'] >= 300,
            ],
            [
                'id' => 'marathon',
                'title' => 'Maratonuinti',
                'description' => 'Yksi uinti kestää yli 10 minuuttia',
                'unlocked' => $metrics['longest_ever'] >= 600,
            ],
            [
                'id' => 'week_warrior',
                'title' => 'Viikon putki',
                'description' => 'Peräkkäisiä avantopäiviä vähintään 7',
                'unlocked' => $streaks['best'] >= 7,
            ],
            [
                'id' => 'month_streak',
                'title' => 'Kuukauden putki',
                'description' => 'Peräkkäisiä avantopäiviä vähintään 30',
                'unlocked' => $streaks['best'] >= 30,
            ],
            [
                'id' => 'comeback',
                'title' => 'Paluu kylmään',
                'description' => 'Uinti vähintään 30 päivän tauon jälkeen',
                'unlocked' => $metrics['has_comeback'],
            ],
            [
                'id' => 'weekly_habit',
                'title' => 'Viikkorytmi',
                'description' => '3 avantoa saman kalenteriviikon aikana',
                'unlocked' => $metrics['has_weekly_habit'],
            ],
            [
                'id' => 'mood_boost',
                'title' => 'Fiilipiikki',
                'description' => 'Fiilis paranee vähintään 5 pykälää',
                'unlocked' => $metrics['has_mood_boost'],
            ],
            [
                'id' => 'zen',
                'title' => 'Zen-uinti',
                'description' => 'Fiilis uinti jälkeen vähintään 9',
                'unlocked' => $metrics['has_zen_dip'],
            ],
            [
                'id' => 'swear_storm',
                'title' => 'Sanat lentävät',
                'description' => 'Vähintään 5 kirosanaa yhdessä uintissa',
                'unlocked' => $metrics['max_swear_words'] >= 5,
            ],
            [
                'id' => 'silent_seal',
                'title' => 'Hiljainen hylje',
                'description' => '10 uintia ilman yhtään kirosanaa',
                'unlocked' => $metrics['silent_dip_count'] >= 10,
            ],
            [
                'id' => 'hot_cold',
                'title' => 'Löyly ja jää',
                'description' => '10 avantoa, joissa sauna mukana',
                'unlocked' => $metrics['sauna_count'] >= 10,
            ],
            [
                'id' => 'sauna_regular',
                'title' => 'Saunavaki',
                'description' => '20 saunakertaa yhteensä',
                'unlocked' => $metrics['sauna_count'] >= 20,
            ],
            [
                'id' => 'long_sauna',
                'title' => 'Pitkä löyly',
                'description' => 'Saunan kesto vähintään 20 minuuttia',
                'unlocked' => $metrics['longest_sauna'] >= 20,
            ],
            [
                'id' => 'explorer',
                'title' => 'Rantojen tallaaja',
                'description' => 'Uinti vähintään 3 eri paikassa',
                'unlocked' => $metrics['distinct_locations'] >= 3,
            ],
            [
                'id' => 'home_ground',
                'title' => 'Kotiranta',
                'description' => '15 uintia samassa paikassa',
                'unlocked' => $metrics['max_location_visits'] >= 15,
            ],
            [
                'id' => 'year_round',
                'title' => 'Ympäri vuoden',
                'description' => 'Avannot vähintään 4 eri kuukaudessa',
                'unlocked' => $metrics['distinct_months'] >= 4,
            ],
            [
                'id' => 'new_year',
                'title' => 'Uudenvuodenuinti',
                'description' => 'Avanto uudenvuodenpäivänä',
                'unlocked' => $metrics['has_new_year_dip'],
            ],
            [
                'id' => 'selfie_star',
                'title' => 'Muistokuva',
                'description' => 'Selfie liitetty avantomerkintään',
                'unlocked' => $metrics['has_selfie'],
            ],
            [
                'id' => 'early_bird',
                'title' => 'Aamuhyppääjä',
                'description' => 'Merkintä tehty ennen klo 10',
                'unlocked' => $metrics['has_early_bird'],
            ],
            [
                'id' => 'winter_total',
                'title' => 'Talven tarmo',
                'description' => '10 avantoa marraskuusta maaliskuuhun',
                'unlocked' => $metrics['winter_month_dips'] >= 10,
            ],
            [
                'id' => 'cold_heart',
                'title' => 'Kylmä sydän',
                'description' => '10 tuntia yhteensä kylmässä vedessä',
                'unlocked' => $metrics['total_duration'] >= 36000,
            ],
        ];
    }

    private function achievementMetrics(User $user, array $streaks): array
    {
        $baseQuery = $this->baseQuery($user);
        $records = (clone $baseQuery)->get([
            'date',
            'location',
            'water_temperature',
            'duration_minutes',
            'duration_seconds',
            'swear_words',
            'feeling_before',
            'feeling_after',
            'selfie_path',
            'sauna',
            'sauna_duration',
            'created_at',
        ]);

        $allTimeVisits = $records->count();
        $longestEver = $records->max(fn (Avanto $avanto) => $avanto->total_duration) ?? 0;
        $totalDuration = $records->sum(fn (Avanto $avanto) => $avanto->total_duration);

        $temperatures = $records
            ->pluck('water_temperature')
            ->filter(fn ($value) => $value !== null);

        $coldestEver = $temperatures->isEmpty()
            ? null
            : round((float) $temperatures->min(), 1);

        $saunaRecords = $records->where('sauna', true);
        $longestSauna = (int) ($saunaRecords->max('sauna_duration') ?? 0);

        $locationCounts = $records
            ->filter(fn (Avanto $avanto) => filled($avanto->location))
            ->groupBy('location')
            ->map->count();

        $distinctMonths = $records
            ->map(fn (Avanto $avanto) => Carbon::parse($avanto->date)->format('Y-m'))
            ->unique()
            ->count();

        $winterMonthDips = $records
            ->filter(fn (Avanto $avanto) => in_array(Carbon::parse($avanto->date)->month, [11, 12, 1, 2, 3], true))
            ->count();

        $weeklyCounts = $records
            ->groupBy(fn (Avanto $avanto) => Carbon::parse($avanto->date)->format('o-W'))
            ->map->count();

        $uniqueDates = $records
            ->map(fn (Avanto $avanto) => Carbon::parse($avanto->date)->toDateString())
            ->unique()
            ->sort()
            ->values();

        $hasComeback = false;

        for ($index = 1; $index < $uniqueDates->count(); $index++) {
            $gap = Carbon::parse($uniqueDates[$index - 1])
                ->diffInDays(Carbon::parse($uniqueDates[$index]));

            if ($gap >= 30) {
                $hasComeback = true;
                break;
            }
        }

        return [
            'all_time_visits' => $allTimeVisits,
            'longest_ever' => (int) $longestEver,
            'total_duration' => (int) $totalDuration,
            'coldest_ever' => $coldestEver,
            'has_sub_zero_dip' => $temperatures->contains(fn ($value) => $value <= 0),
            'sauna_count' => $saunaRecords->count(),
            'longest_sauna' => $longestSauna,
            'distinct_locations' => $locationCounts->count(),
            'max_location_visits' => (int) ($locationCounts->max() ?? 0),
            'distinct_months' => $distinctMonths,
            'winter_month_dips' => $winterMonthDips,
            'has_weekly_habit' => $weeklyCounts->contains(fn (int $count) => $count >= 3),
            'has_comeback' => $hasComeback,
            'has_mood_boost' => $records->contains(function (Avanto $avanto) {
                return $avanto->feeling_before !== null
                    && $avanto->feeling_after !== null
                    && ($avanto->feeling_after - $avanto->feeling_before) >= 5;
            }),
            'has_zen_dip' => $records->contains(fn (Avanto $avanto) => $avanto->feeling_after !== null && $avanto->feeling_after >= 9),
            'max_swear_words' => (int) ($records->max('swear_words') ?? 0),
            'silent_dip_count' => $records->filter(fn (Avanto $avanto) => (int) ($avanto->swear_words ?? 0) === 0)->count(),
            'has_new_year_dip' => $records->contains(function (Avanto $avanto) {
                $date = Carbon::parse($avanto->date);

                return $date->month === 1 && $date->day === 1;
            }),
            'has_selfie' => $records->contains(fn (Avanto $avanto) => filled($avanto->selfie_path)),
            'has_early_bird' => $records->contains(function (Avanto $avanto) {
                return $avanto->created_at !== null && $avanto->created_at->hour < 10;
            }),
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
