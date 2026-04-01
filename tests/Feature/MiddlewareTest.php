<?php

use App\Models\User;

test('recovery codes handoff redirects when session flag is set', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['recovery_codes_handoff_required' => true])
        ->get('/notes')
        ->assertRedirect(route('recovery-codes.handoff'));
});

test('recovery codes handoff allows access when flag is not set', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/notes')
        ->assertOk();
});

test('recovery codes handoff allows the handoff route itself', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'recovery_codes_handoff_required' => true,
            'recovery_codes_handoff_codes' => ['AAAA-BBBB-CCCC-DDDD'],
        ])
        ->get(route('recovery-codes.handoff'))
        ->assertOk();
});

test('recovery codes handoff allows logout when flag is set', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['recovery_codes_handoff_required' => true])
        ->post('/logout');

    $response->assertRedirect('/');
});

test('unauthenticated users bypass handoff check', function () {
    $this->get('/notes')->assertRedirect('/login');
});
