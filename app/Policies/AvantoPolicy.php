<?php

namespace App\Policies;

use App\Models\Avanto;
use App\Models\User;

class AvantoPolicy
{
    public function view(User $user, Avanto $avanto)
    {
        return $user->id === $avanto->user_id;
    }
}
