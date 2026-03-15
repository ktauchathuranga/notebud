<div
    x-data="{ flash: { show: false, type: 'success', message: '' } }"
    x-on:share-feedback.window="flash = { show: true, type: $event.detail.type, message: $event.detail.message }; setTimeout(() => flash.show = false, 3500)"
>
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div x-show="flash.show" x-transition x-cloak class="rounded-lg border p-3 text-sm"
             :class="flash.type === 'success'
                ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-900/60 dark:bg-green-900/20 dark:text-green-300'
                : 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-900/20 dark:text-red-300'">
            <span x-text="flash.message"></span>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl">{{ __('My Files') }}</flux:heading>
            <div class="flex items-center gap-3">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search files..." icon="magnifying-glass" class="w-full sm:w-64" />
                <flux:button variant="primary" :href="route('files.upload')" wire:navigate icon="arrow-up-tray">
                    {{ __('Upload') }}
                </flux:button>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                <flux:text class="font-medium">{{ __('Storage usage') }}</flux:text>
                <flux:text class="text-zinc-500">{{ $storageUsageText }}</flux:text>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div
                    class="h-full transition-all duration-300 {{ $storageUsagePercent >= 95 ? 'bg-red-500' : ($storageUsagePercent >= 80 ? 'bg-amber-500' : 'bg-blue-500') }}"
                    style="width: {{ $storageUsagePercent }}%"
                ></div>
            </div>
            <flux:text class="mt-2 text-xs text-zinc-500">
                {{ __('Remaining: :remaining', ['remaining' => \App\Support\StorageQuota::formatBytes($remainingStorageBytes)]) }}
            </flux:text>
        </div>

        @if($myFiles->isEmpty() && $sharedFiles->isEmpty() && !$search)
            <div class="flex flex-1 items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 p-12">
                <div class="text-center">
                    <flux:icon name="folder" class="mx-auto size-12 text-zinc-400" />
                    <flux:heading size="lg" class="mt-4">{{ __('No files yet') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Upload your first file to get started.') }}</flux:text>
                    <flux:button variant="primary" :href="route('files.upload')" wire:navigate icon="arrow-up-tray" class="mt-4">
                        {{ __('Upload File') }}
                    </flux:button>
                </div>
            </div>
        @else
            @if($myFiles->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                                <th class="px-4 py-3 font-medium hidden sm:table-cell">{{ __('Size') }}</th>
                                <th class="px-4 py-3 font-medium hidden md:table-cell">{{ __('Uploaded') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($myFiles as $file)
                                <tr class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="document" class="size-5 text-zinc-400" />
                                            <span class="truncate max-w-[200px]">{{ $file->original_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 hidden sm:table-cell">{{ $file->sizeForHumans() }}</td>
                                    <td class="px-4 py-3 text-zinc-500 hidden md:table-cell">{{ $file->created_at->diffForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <flux:button variant="ghost" size="sm" :href="route('files.download', $file)" icon="arrow-down-tray" />
                                            <livewire:shares.share-modal :shareable-type="App\Models\File::class" :shareable-id="$file->id" :key="'share-file-'.$file->id" />
                                            <flux:button variant="ghost" size="sm" wire:click="deleteFile({{ $file->id }})" wire:confirm="Are you sure you want to delete this file?" icon="trash" class="!text-red-500" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif($search)
                <flux:text class="text-center py-8">{{ __('No files match your search.') }}</flux:text>
            @endif

            @if($sharedFiles->isNotEmpty())
                <flux:separator />
                <flux:heading size="lg">{{ __('Shared with me') }}</flux:heading>
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                                <th class="px-4 py-3 font-medium hidden sm:table-cell">{{ __('Shared by') }}</th>
                                <th class="px-4 py-3 font-medium hidden sm:table-cell">{{ __('Size') }}</th>
                                <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($sharedFiles as $file)
                                <tr class="bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="document" class="size-5 text-zinc-400" />
                                            <span class="truncate max-w-[200px]">{{ $file->original_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 hidden sm:table-cell"><flux:badge size="sm" color="blue">{{ $file->user->username }}</flux:badge></td>
                                    <td class="px-4 py-3 text-zinc-500 hidden sm:table-cell">{{ $file->sizeForHumans() }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <flux:button variant="ghost" size="sm" :href="route('files.download', $file)" icon="arrow-down-tray" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
