<?php

use App\Livewire\Settings\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/password')
        ->assertOk();
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(Password::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('current password must be correct to update', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(Password::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasErrors('current_password');
});

test('new password must be confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(Password::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password-123')
        ->set('password_confirmation', 'different-password')
        ->call('updatePassword')
        ->assertHasErrors('password');
});
