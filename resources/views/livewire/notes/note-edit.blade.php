<div>
    <div class="mx-auto w-full max-w-3xl">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('notes.show', $note)" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Edit Note') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" :label="__('Title')" type="text" required placeholder="Note title..." autofocus />

            <div>
                <flux:label>{{ __('Content') }} <span class="text-xs text-zinc-400">(Markdown supported)</span></flux:label>
                <div x-data="{ tab: 'write' }" class="mt-1">
                    <div class="flex gap-2 mb-2">
                        <flux:button size="sm" x-on:click="tab = 'write'" ::variant="tab === 'write' ? 'filled' : 'ghost'">{{ __('Write') }}</flux:button>
                        <flux:button size="sm" x-on:click="tab = 'preview'" ::variant="tab === 'preview' ? 'filled' : 'ghost'">{{ __('Preview') }}</flux:button>
                    </div>
                    <div x-show="tab === 'write'">
                        <flux:textarea wire:model="content" rows="15" placeholder="Write your note in markdown..." class="font-mono" />
                    </div>
                    <div x-show="tab === 'preview'" x-cloak>
                        <div class="prose dark:prose-invert max-w-none rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 min-h-[15rem]"
                             x-data="{ html: '' }"
                             x-init="$watch('$wire.content', async (val) => { html = await parseMarkdown(val || ''); }); html = await parseMarkdown($wire.content || '');"
                             x-html="html || '<p class=\'text-zinc-400\'>Nothing to preview...</p>'">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">{{ __('Save Changes') }}</flux:button>
                <flux:button variant="ghost" :href="route('notes.show', $note)" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</div>
