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

    public array $selectedUserIds = [];

    public bool $selectAll = false;

    public string $bulkQuotaMb = '';

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

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedUserIds = User::query()
                ->when($this->search, fn ($query) => $query->where('username', 'like', '%'.$this->search.'%'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            return;
        }

        $this->selectedUserIds = [];
    }

    public function applyQuotaToSelected(): void
    {
        $validated = $this->validate([
            'bulkQuotaMb' => ['required', 'numeric', 'min:1', 'max:102400'],
        ]);

        if (empty($this->selectedUserIds)) {
            $this->addError('bulkQuotaMb', 'Select at least one user.');

            return;
        }

        $quotaBytes = (int) round(((float) $validated['bulkQuotaMb']) * 1024 * 1024);

        User::query()->whereIn('id', $this->selectedUserIds)->update([
            'storage_quota_bytes' => $quotaBytes,
        ]);

        session()->flash('status', 'Storage quota updated for selected users.');
    }

    public function applyQuotaToAllUsers(): void
    {
        $validated = $this->validate([
            'bulkQuotaMb' => ['required', 'numeric', 'min:1', 'max:102400'],
        ]);

        $quotaBytes = (int) round(((float) $validated['bulkQuotaMb']) * 1024 * 1024);

        User::query()->update([
            'storage_quota_bytes' => $quotaBytes,
        ]);

        $this->selectedUserIds = [];
        $this->selectAll = false;

        session()->flash('status', 'Storage quota updated for all users.');
    }

    public function resetQuotaForSelected(): void
    {
        if (empty($this->selectedUserIds)) {
            $this->addError('bulkQuotaMb', 'Select at least one user.');

            return;
        }

        User::query()->whereIn('id', $this->selectedUserIds)->update([
            'storage_quota_bytes' => null,
        ]);

        session()->flash('status', 'Selected users now use the global default quota.');
    }

    public function resetQuotaForAllUsers(): void
    {
        User::query()->update([
            'storage_quota_bytes' => null,
        ]);

        $this->selectedUserIds = [];
        $this->selectAll = false;

        session()->flash('status', 'All users now use the global default quota.');
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
