<?php

namespace App\Policies;

use App\Models\Avanto;
use App\Models\User;

class AvantoPolicy
{
    public function view(User $user, Avanto $avanto): bool
    {
        return $user->id === $avanto->user_id;
    }

    public function update(User $user, Avanto $avanto): bool
    {
        return $user->id === $avanto->user_id;
    }

    public function delete(User $user, Avanto $avanto): bool
    {
        return $user->id === $avanto->user_id;
    }
}
