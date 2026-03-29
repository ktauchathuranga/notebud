<?php

use App\Livewire\Auth\RecoverAccount;
use App\Models\RecoveryCode;
use App\Models\User;
use App\Support\RecoveryCodes;
use Illuminate\Support\Facades\Hash;

test('recover account screen can be rendered', function () {
    $this->get('/recover-account')->assertOk();
});

test('user can reset password using a recovery code', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);

    $codes = RecoveryCodes::regenerateForUser($user, 3);
    $allCodeIds = RecoveryCode::query()->where('user_id', $user->id)->pluck('id')->all();

    Livewire\Livewire::test(RecoverAccount::class)
        ->set('username', $user->username)
        ->set('recovery_code', $codes[0])
        ->set('password', 'NewPassword123!')
        ->set('password_confirmation', 'NewPassword123!')
        ->call('recover')
        ->assertRedirect(route('notes.index', absolute: false))
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);

    $user->refresh();

    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
    expect(RecoveryCode::query()->where('user_id', $user->id)->count())->toBe(3);
    expect(RecoveryCode::query()->where('user_id', $user->id)->whereNull('used_at')->count())->toBe(2);

    foreach ($allCodeIds as $codeId) {
        $this->assertDatabaseHas('recovery_codes', ['id' => $codeId]);
    }
});

test('invalid recovery code does not reset password', function () {
    $user = User::factory()->create([
        'password' => 'OldPassword123!',
    ]);

    RecoveryCodes::regenerateForUser($user, 2);

    Livewire\Livewire::test(RecoverAccount::class)
        ->set('username', $user->username)
        ->set('recovery_code', 'WRNG-WRNG-WRNG-WRNG')
        ->set('password', 'NewPassword123!')
        ->set('password_confirmation', 'NewPassword123!')
        ->call('recover')
        ->assertHasErrors('recovery_code');

    $this->assertGuest();
    $user->refresh();

    expect(Hash::check('OldPassword123!', $user->password))->toBeTrue();
});
