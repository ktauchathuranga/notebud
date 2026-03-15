<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('recovery-codes.handoff', absolute: false));

    $user = User::query()->where('username', 'testuser')->firstOrFail();
    expect($user->recoveryCodes()->whereNull('used_at')->count())->toBe(10);

    $this->get('/notes')
        ->assertRedirect(route('recovery-codes.handoff', absolute: false));
});

test('registration requires unique username', function () {
    User::factory()->create(['username' => 'taken']);

    $this->post('/register', [
        'username' => 'taken',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('username');
});
