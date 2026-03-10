<?php

namespace App\Policies;

use App\Models\Share;
use App\Models\User;

class SharePolicy
{
    public function respond(User $user, Share $share): bool
    {
        return $share->shared_with === $user->id && $share->status === 'pending';
    }
}
