<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Edit User')]
class UserEdit extends Component
{
    public User $user;

    public string $username = '';

    public string $role = 'user';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->username = $user->username;
        $this->role = $user->role;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($this->user->id)],
            'role' => ['required', 'in:user,admin'],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
        ]);

        if (Auth::id() === $this->user->id && $validated['role'] !== $this->user->role) {
            $this->addError('role', 'You cannot change your own role.');

            return;
        }

        $this->user->username = $validated['username'];
        $this->user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $this->user->password = $validated['password'];
        }

        $this->user->save();

        session()->flash('status', 'User updated successfully.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.user-edit');
    }
}
