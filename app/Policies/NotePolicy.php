<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        if ($note->user_id === $user->id) {
            return true;
        }

        return $note->shares()
            ->where('shared_with', $user->id)
            ->where('status', 'accepted')
            ->exists();
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function share(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
