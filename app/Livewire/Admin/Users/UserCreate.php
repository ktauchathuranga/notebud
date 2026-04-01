<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Create User')]
class UserCreate extends Component
{
    public string $username = '';

    public string $role = 'user';

    public string $password = '';

    public string $password_confirmation = '';

    public string $storage_quota_mb = '';

    public function save(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', 'in:user,admin'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'storage_quota_mb' => ['nullable', 'numeric', 'min:1', 'max:102400'],
        ]);

        $validated['storage_quota_bytes'] = isset($validated['storage_quota_mb']) && $validated['storage_quota_mb'] !== null && $validated['storage_quota_mb'] !== ''
            ? (int) round(((float) $validated['storage_quota_mb']) * 1024 * 1024)
            : null;

        unset($validated['storage_quota_mb']);

        User::create($validated);

        session()->flash('status', 'User created successfully.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.user-create');
    }
}
