<?php

use App\Models\RecoveryCode;
use App\Models\User;
use App\Support\RecoveryCodes;

test('recovery code settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/recovery-codes')
        ->assertOk();
});

test('user can regenerate recovery codes from settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(App\Livewire\Settings\RecoveryCodes::class)
        ->call('regenerate')
        ->assertSet('availableCodeCount', 10);

    expect($user->fresh()->recoveryCodes()->whereNull('used_at')->count())->toBe(10);
});

test('available code count reflects consumed recovery codes', function () {
    $user = User::factory()->create();
    RecoveryCodes::regenerateForUser($user, 3);

    RecoveryCode::query()
        ->where('user_id', $user->id)
        ->firstOrFail()
        ->forceFill(['used_at' => now()])
        ->save();

    $this->actingAs($user);

    Livewire\Livewire::test(App\Livewire\Settings\RecoveryCodes::class)
        ->assertSet('availableCodeCount', 2);
});
