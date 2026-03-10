<div>
    <flux:button variant="ghost" size="sm" wire:click="open" icon="share-2" />

    <flux:modal wire:model="showModal" name="share-modal" class="w-full max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Share') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Enter the username of the person you want to share with.') }}</flux:text>
            </div>

            <form wire:submit="share" class="space-y-4">
                <flux:input wire:model="username" :label="__('Username')" type="text" required placeholder="Enter username..." autofocus />
                <flux:input wire:model="message" :label="__('Message (optional)')" type="text" placeholder="Add a message..." />

                <div class="flex items-center justify-end gap-3">
                    <flux:button variant="ghost" wire:click="close">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Share') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
