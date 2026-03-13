<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
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

    public function save(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', 'in:user,admin'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        User::create($validated);

        session()->flash('status', 'User created successfully.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.users.user-create');
    }
}
