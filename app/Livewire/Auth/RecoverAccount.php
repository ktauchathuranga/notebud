<?php

namespace App\Livewire\Auth;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\RecoveryCodes as RecoveryCodesSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        $username = $validated['username'];
        $user = Cache::tags(['user_'.$username])->remember(
            'recovery_user',
            now()->addHour(),
            function () use ($username) {
                return User::query()->where('username', $username)->first();
            }
        );

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

        Cache::tags(['user_'.$username])->flush();

        Auth::login($user);

        $this->redirectRoute('notes.index', navigate: true);
    }
}
