<?php

namespace App\Livewire\Auth;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\RecoveryCodes as RecoveryCodesSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Recover account')]
class RecoverAccount extends Component
{
    use PasswordValidationRules;

    public string $username = '';

    public string $recovery_code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function recover(): void
    {
        $validated = $this->validate([
            'username' => ['required', 'string'],
            'recovery_code' => ['required', 'string'],
            'password' => $this->passwordRules(),
        ]);

        $user = User::query()->where('username', $validated['username'])->first();

        $invalidRecoveryMessage = 'We could not verify that recovery code for this username.';

        if (! $user) {
            throw ValidationException::withMessages([
                'recovery_code' => $invalidRecoveryMessage,
            ]);
        }

        $wasConsumed = RecoveryCodesSupport::consume($user, $validated['recovery_code']);

        if (! $wasConsumed) {
            throw ValidationException::withMessages([
                'recovery_code' => $invalidRecoveryMessage,
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        Auth::login($user);

        $this->redirectRoute('notes.index', navigate: true);
    }
}
