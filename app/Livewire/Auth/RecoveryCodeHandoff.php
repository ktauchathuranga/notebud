<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Save recovery codes')]
class RecoveryCodeHandoff extends Component
{
    public array $codes = [];

    public function mount(): void
    {
        $this->codes = session('recovery_codes_handoff_codes', []);

        if (empty($this->codes)) {
            $this->redirectRoute('notes.index', navigate: true);
        }
    }

    public function copyAndContinue(): void
    {
        $payload = implode(PHP_EOL, $this->codes);
        $this->clearHandoffSession();

        $this->dispatch(
            'recovery-codes-copy-and-continue',
            codes: $payload,
            redirect: route('notes.index'),
        );
    }

    public function downloadAndContinue(): void
    {
        $payload = implode(PHP_EOL, $this->codes);
        $this->clearHandoffSession();

        $this->dispatch(
            'recovery-codes-download-and-continue',
            content: $payload,
            filename: 'notebud-recovery-codes.txt',
            redirect: route('notes.index'),
        );
    }

    private function clearHandoffSession(): void
    {
        session()->forget('recovery_codes_handoff_required');
        session()->forget('recovery_codes_handoff_codes');
    }
}
