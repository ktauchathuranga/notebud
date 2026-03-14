<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Management')]
class UserIndex extends Component
{
    #[Url]
    public string $search = '';

    public function deleteUser(int $userId): void
    {
        $currentUser = Auth::user();

        if ($currentUser->id === $userId) {
            $this->addError('delete', 'You cannot delete your own account from admin management.');

            return;
        }

        User::findOrFail($userId)->delete();

        session()->flash('status', 'User deleted successfully.');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($query) => $query->where('username', 'like', '%'.$this->search.'%'))
            ->latest()
            ->get();

        return view('livewire.admin.users.user-index', [
            'users' => $users,
        ]);
    }
}
