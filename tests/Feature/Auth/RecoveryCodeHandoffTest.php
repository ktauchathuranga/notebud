<?php

use App\Livewire\Auth\RecoveryCodeHandoff;
use App\Models\User;

test('recovery code handoff screen is shown when required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'recovery_codes_handoff_required' => true,
            'recovery_codes_handoff_codes' => ['ABCD-EFGH-IJKL-MNOP'],
        ])
        ->get(route('recovery-codes.handoff'))
        ->assertOk()
        ->assertSee('ABCD-EFGH-IJKL-MNOP');
});

test('auth routes redirect to handoff when recovery code onboarding is pending', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            'recovery_codes_handoff_required' => true,
            'recovery_codes_handoff_codes' => ['ABCD-EFGH-IJKL-MNOP'],
        ])
        ->get(route('notes.index'))
        ->assertRedirect(route('recovery-codes.handoff', absolute: false));
});

test('copy handoff action clears pending onboarding session state', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    session()->put('recovery_codes_handoff_required', true);
    session()->put('recovery_codes_handoff_codes', ['ABCD-EFGH-IJKL-MNOP']);

    Livewire\Livewire::test(RecoveryCodeHandoff::class)
        ->call('copyAndContinue')
        ->assertDispatched('recovery-codes-copy-and-continue');

    expect(session()->has('recovery_codes_handoff_required'))->toBeFalse();
    expect(session()->has('recovery_codes_handoff_codes'))->toBeFalse();
});
