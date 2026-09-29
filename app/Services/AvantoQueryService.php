<?php

namespace App\Services;

use App\Models\Avanto;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AvantoQueryService
{
    public function forUser(User $user, array $filters = []): Builder
    {
        $query = Avanto::query()
            ->where('user_id', $user->id)
            ->latest('date')
            ->latest('avanto_id');

        if (! empty($filters['location'])) {
            $query->where('location', 'like', '%'.$filters['location'].'%');
        }

        if (! empty($filters['start_date'])) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('date', '<=', $filters['end_date']);
        }

        if (array_key_exists('sauna', $filters) && $filters['sauna'] !== null) {
            $query->where('sauna', (bool) $filters['sauna']);
        }

        return $query;
    }
}
