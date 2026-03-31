<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\User;

test('user can delete their account with correct password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser');

    expect(User::find($user->id))->toBeNull();
    $this->assertGuest();
});

test('user cannot delete account with wrong password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors('password');

    expect(User::find($user->id))->not->toBeNull();
});
