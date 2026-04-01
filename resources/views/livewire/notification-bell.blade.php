<div wire:poll.15s>
    {{-- Desktop: styled like a sidebar item --}}
    <div class="hidden lg:block">
        <flux:dropdown position="bottom" align="start">
            <button type="button" class="relative flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-0 h-8 text-start text-sm font-medium text-zinc-500 hover:bg-zinc-800/5 hover:text-zinc-800 dark:text-white/80 dark:hover:bg-white/7 dark:hover:text-white">
                <flux:icon name="bell" class="size-4" />
                <span class="flex-1 truncate">{{ __('Notifications') }}</span>
                @if($unreadCount > 0)
                    <span class="flex size-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>

            <flux:menu class="w-80 max-h-96 overflow-y-auto">
                @include('livewire.partials.notification-menu')
            </flux:menu>
        </flux:dropdown>
    </div>

    {{-- Mobile: icon-only button in header --}}
    <div class="lg:hidden">
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" icon="bell" class="relative">
                @if($unreadCount > 0)
                    <span class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </flux:button>

            <flux:menu class="w-80 max-h-96 overflow-y-auto">
                @include('livewire.partials.notification-menu')
            </flux:menu>
        </flux:dropdown>
    </div>
</div>
