<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk();
});

test('profile username can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire\Livewire::test(App\Livewire\Settings\Profile::class)
        ->set('username', 'newusername')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();
    $user->refresh();
    expect($user->username)->toBe('newusername');
});
