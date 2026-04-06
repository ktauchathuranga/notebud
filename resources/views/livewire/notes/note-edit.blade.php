<div>
    <div class="mx-auto w-full max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('notes.show', $note)" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Edit Note') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" :label="__('Title')" type="text" required placeholder="Note title..." autofocus />

            @include('livewire.notes.partials.markdown-editor')

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
                <flux:button variant="ghost" :href="route('notes.show', $note)" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</div>
