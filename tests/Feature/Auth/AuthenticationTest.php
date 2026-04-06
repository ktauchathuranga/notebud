<?php

use App\Models\User;
use Illuminate\Support\Carbon;

test('login screen can be rendered', function () {
    $this->get('/login')->assertStatus(200);
});

test('users can authenticate using username', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('notes.index', absolute: false));
});

test('successful login updates last login timestamp', function () {
    $this->travelTo(Carbon::create(2026, 4, 5, 12, 0, 0));

    try {
        $user = User::factory()->create([
            'last_login_at' => null,
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('notes.index', absolute: false));

        $updatedUser = $user->fresh();

        expect($updatedUser?->last_login_at)->not->toBeNull();
        expect($updatedUser?->last_login_at?->toDateTimeString())->toBe(now()->toDateTimeString());
    } finally {
        $this->travelBack();
    }
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout');

    $this->assertGuest();
});
