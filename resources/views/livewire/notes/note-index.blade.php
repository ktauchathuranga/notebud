<x-layouts::app :title="__('My Notes')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl">{{ __('My Notes') }}</flux:heading>
            <div class="flex items-center gap-3">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search notes..." icon="search" class="w-full sm:w-64" />
                <flux:button variant="primary" :href="route('notes.create')" wire:navigate icon="plus">
                    {{ __('New Note') }}
                </flux:button>
            </div>
        </div>

        @if($myNotes->isEmpty() && $sharedNotes->isEmpty() && !$search)
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12">
                <div class="text-center">
                    <flux:icon name="book-open" class="mx-auto size-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('No notes yet') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Create your first note to get started.') }}</flux:text>
                    <flux:button variant="primary" :href="route('notes.create')" wire:navigate icon="plus" class="mt-4">
                        {{ __('Create Note') }}
                    </flux:button>
                </div>
            </div>
        @else
            @if($myNotes->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($myNotes as $note)
                        <div class="group relative flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <flux:link :href="route('notes.show', $note)" wire:navigate class="text-base font-semibold !text-zinc-900 dark:!text-zinc-100 hover:!underline line-clamp-1">
                                    {{ $note->title }}
                                </flux:link>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" class="opacity-0 group-hover:opacity-100" />
                                    <flux:menu>
                                        <flux:menu.item :href="route('notes.edit', $note)" wire:navigate icon="pencil">{{ __('Edit') }}</flux:menu.item>
                                        <flux:menu.item wire:click="deleteNote({{ $note->id }})" wire:confirm="Are you sure you want to delete this note?" icon="trash" variant="danger">{{ __('Delete') }}</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                            <flux:text class="mt-2 line-clamp-3 text-sm">{{ Str::limit(strip_tags($note->content), 150) }}</flux:text>
                            <flux:text class="mt-auto pt-3 text-xs text-zinc-400">{{ $note->updated_at->diffForHumans() }}</flux:text>
                        </div>
                    @endforeach
                </div>
            @elseif($search)
                <flux:text class="text-center py-8">{{ __('No notes match your search.') }}</flux:text>
            @endif

            @if($sharedNotes->isNotEmpty())
                <flux:separator />
                <flux:heading size="lg">{{ __('Shared with me') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($sharedNotes as $note)
                        <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" color="blue">{{ $note->user->username }}</flux:badge>
                            </div>
                            <flux:link :href="route('notes.show', $note)" wire:navigate class="mt-2 text-base font-semibold !text-zinc-900 dark:!text-zinc-100 hover:!underline line-clamp-1">
                                {{ $note->title }}
                            </flux:link>
                            <flux:text class="mt-2 line-clamp-3 text-sm">{{ Str::limit(strip_tags($note->content), 150) }}</flux:text>
                            <flux:text class="mt-auto pt-3 text-xs text-zinc-400">{{ $note->updated_at->diffForHumans() }}</flux:text>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-layouts::app>
