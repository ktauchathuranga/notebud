<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    public function view(User $user, File $file): bool
    {
        if ($file->user_id === $user->id) {
            return true;
        }

        return $file->shares()
            ->where('shared_with', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    public function delete(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }

    public function share(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }
}
