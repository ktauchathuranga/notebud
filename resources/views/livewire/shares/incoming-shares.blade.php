<x-layouts::app :title="__('Shared with me')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <flux:heading size="xl">{{ __('Shared with me') }}</flux:heading>

        @if($pendingShares->isNotEmpty())
            <div>
                <flux:heading size="lg" class="mb-4">{{ __('Pending Requests') }}</flux:heading>
                <div class="space-y-3">
                    @foreach($pendingShares as $share)
                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="flex size-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                                    @if($share->shareable_type === 'App\Models\Note')
                                        <flux:icon name="book-open" class="size-5" />
                                    @else
                                        <flux:icon name="document" class="size-5" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <flux:text class="font-medium truncate">
                                        @if($share->shareable_type === 'App\Models\Note')
                                            {{ $share->shareable->title ?? 'Deleted note' }}
                                        @else
                                            {{ $share->shareable->original_name ?? 'Deleted file' }}
                                        @endif
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-500">
                                        {{ __('From') }} <span class="font-medium">{{ $share->sharer->username }}</span>
                                        @if($share->message)
                                            &mdash; "{{ $share->message }}"
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-4">
                                <flux:button variant="primary" size="sm" wire:click="accept({{ $share->id }})">{{ __('Accept') }}</flux:button>
                                <flux:button variant="ghost" size="sm" wire:click="reject({{ $share->id }})">{{ __('Reject') }}</flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($acceptedShares->isNotEmpty())
            @if($pendingShares->isNotEmpty())
                <flux:separator />
            @endif
            <div>
                <flux:heading size="lg" class="mb-4">{{ __('Accepted') }}</flux:heading>
                <div class="space-y-3">
                    @foreach($acceptedShares as $share)
                        <div class="flex items-center justify-between rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="flex size-10 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300">
                                    @if($share->shareable_type === 'App\Models\Note')
                                        <flux:icon name="book-open" class="size-5" />
                                    @else
                                        <flux:icon name="document" class="size-5" />
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <flux:text class="font-medium truncate">
                                        @if($share->shareable_type === 'App\Models\Note')
                                            {{ $share->shareable->title ?? 'Deleted note' }}
                                        @else
                                            {{ $share->shareable->original_name ?? 'Deleted file' }}
                                        @endif
                                    </flux:text>
                                    <flux:text class="text-sm text-zinc-500">{{ __('From') }} <span class="font-medium">{{ $share->sharer->username }}</span></flux:text>
                                </div>
                            </div>
                            <div class="shrink-0 ml-4">
                                @if($share->shareable_type === 'App\Models\Note')
                                    <flux:button variant="ghost" size="sm" :href="route('notes.show', $share->shareable_id)" wire:navigate icon="eye">{{ __('View') }}</flux:button>
                                @else
                                    <flux:button variant="ghost" size="sm" :href="route('files.download', $share->shareable_id)" icon="download">{{ __('Download') }}</flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($pendingShares->isEmpty() && $acceptedShares->isEmpty())
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12">
                <div class="text-center">
                    <flux:icon name="share-2" class="mx-auto size-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('Nothing shared with you yet') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('When someone shares a note or file with you, it will appear here.') }}</flux:text>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
