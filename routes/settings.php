<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\RecoveryCodes;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'recovery-codes.handoff'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
    Route::livewire('settings/password', Password::class)->name('user-password.edit');
    Route::livewire('settings/recovery-codes', RecoveryCodes::class)->name('recovery-codes.edit');
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');
});
