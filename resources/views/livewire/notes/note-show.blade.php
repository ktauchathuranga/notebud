<div
    x-data="{ flash: { show: false, type: 'success', message: '' } }"
    x-on:share-feedback.window="flash = { show: true, type: $event.detail.type, message: $event.detail.message }; setTimeout(() => flash.show = false, 3500)"
>
    <div class="mx-auto w-full max-w-3xl">
        <div x-show="flash.show" x-transition x-cloak class="mb-4 rounded-lg border p-3 text-sm"
             :class="flash.type === 'success'
                ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-900/20 dark:text-green-300'
                : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300'">
            <span x-text="flash.message"></span>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" :href="route('notes.index')" wire:navigate icon="arrow-left" />
                <flux:heading size="xl">{{ $note->title }}</flux:heading>
            </div>
            @can('update', $note)
                <div class="flex items-center gap-2">
                    <flux:button variant="ghost" :href="route('notes.edit', $note)" wire:navigate icon="pencil" size="sm">
                        {{ __('Edit') }}
                    </flux:button>
                    <livewire:shares.share-modal :shareable-type="App\Models\Note::class" :shareable-id="$note->id" />
                </div>
            @endcan
        </div>

        <div class="flex items-center gap-3 mb-6">
            <flux:badge size="sm">{{ $note->user->username }}</flux:badge>
            <flux:text class="text-xs text-zinc-400">{{ __('Updated') }} {{ $note->updated_at->diffForHumans() }}</flux:text>
        </div>

        <div class="prose dark:prose-invert max-w-none rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6"
             x-data="{ html: '' }"
             x-init="html = await parseMarkdown(@js($note->content ?? ''));"
             x-html="html || '<p class=\'text-zinc-400\'>This note is empty.</p>'">
        </div>
    </div>
</div>
