<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Recovery codes')" :subheading="__('Use one-time recovery codes to reset your password without email')">
        <div class="mt-6 space-y-6">
            <div class="space-y-2 text-sm text-zinc-700 dark:text-zinc-300">
                <p>{{ __('Available unused codes: :count', ['count' => $availableCodeCount]) }}</p>
                <p>{{ __('Generate a new set now and store them safely. Generating new codes invalidates previous ones immediately.') }}</p>
            </div>

            <flux:button variant="primary" wire:click="regenerate">
                {{ __('Generate new recovery codes') }}
            </flux:button>

            @if (!empty($newCodes))
                <div class="rounded-lg border border-amber-300/60 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-900/20">
                    <flux:heading size="sm">{{ __('Save these codes now') }}</flux:heading>
                    <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">
                        {{ __('These codes are shown only this time. Store them in a password manager or offline backup.') }}
                    </p>

                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($newCodes as $code)
                            <code class="rounded bg-white px-3 py-2 text-sm font-semibold tracking-wide text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-settings.layout>
</section>
