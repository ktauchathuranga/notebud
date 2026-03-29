<?php

namespace App\Livewire\Settings;

use App\Support\RecoveryCodes as RecoveryCodesSupport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Recovery codes')]
class RecoveryCodes extends Component
{
    public array $newCodes = [];

    public int $availableCodeCount = 0;

    public function mount(): void
    {
        $this->newCodes = session('fresh_recovery_codes', []);
        $this->refreshCodeCount();
    }

    public function regenerate(): void
    {
        $this->newCodes = RecoveryCodesSupport::regenerateForUser(Auth::user());
        $this->refreshCodeCount();

        $this->dispatch('recovery-codes-regenerated');
    }

    private function refreshCodeCount(): void
    {
        $this->availableCodeCount = Auth::user()
            ->recoveryCodes()
            ->whereNull('used_at')
            ->count();
    }

    public function render()
    {
        $this->refreshCodeCount();

        return view('livewire.settings.recovery-codes');
    }
}
