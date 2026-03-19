<div class="text-left">
    @if($trigger === 'menu')
        <flux:menu.item wire:click.prevent="open" icon="share">{{ __($label) }}</flux:menu.item>
    @elseif($trigger === 'none')
        <span class="hidden" aria-hidden="true"></span>
    @else
        <flux:button variant="ghost" size="sm" wire:click="open" icon="share" />
    @endif

    <flux:modal wire:model="showModal" :name="$modalName" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Share') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Enter one or more usernames separated by commas.') }}</flux:text>
            </div>

            <form wire:submit="share" class="space-y-4">
                <flux:input wire:model="username" :label="__('Usernames')" type="text" required placeholder="alice, bob, charlie" autofocus />

                @if($recentUsernames->isNotEmpty())
                    <div class="space-y-2">
                        <flux:text class="text-xs text-zinc-500">{{ __('Recently shared with') }}</flux:text>
                        <div class="flex flex-wrap gap-2">
                            @foreach($recentUsernames as $recentUsername)
                                <button
                                    type="button"
                                    wire:click="useRecentUsername('{{ $recentUsername }}')"
                                    class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                >
                                    {{ $recentUsername }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <flux:input wire:model="message" :label="__('Message (optional)')" type="text" placeholder="Add a message..." />

                <div class="flex items-center justify-end gap-3">
                    <flux:button variant="ghost" wire:click="close">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Share') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
