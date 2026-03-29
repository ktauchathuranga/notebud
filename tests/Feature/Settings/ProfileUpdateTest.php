<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk();
});

test('profile username can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire\Livewire::test(Profile::class)
        ->set('username', 'newusername')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();
    $user->refresh();
    expect($user->username)->toBe('newusername');
});

test('profile avatar can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire\Livewire::test(Profile::class)
        ->set('avatar', UploadedFile::fake()->image('avatar.png'))
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($user->avatar_path))->toBeTrue();
});

test('profile avatar can be removed', function () {
    Storage::fake('public');

    Storage::disk('public')->put('avatars/old-avatar.png', 'avatar-content');

    $user = User::factory()->create([
        'avatar_path' => 'avatars/old-avatar.png',
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test(Profile::class)
        ->call('removeAvatar');

    $user->refresh();

    expect($user->avatar_path)->toBeNull();
    expect(Storage::disk('public')->exists('avatars/old-avatar.png'))->toBeFalse();
});
