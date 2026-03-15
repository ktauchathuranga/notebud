<div>
    <div class="mx-auto w-full max-w-lg">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('files.index')" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Upload File') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6" x-data="{ uploading: false, progress: 0 }">
            <div
                x-data="{ dragging: false }"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'));"
                :class="{ 'border-blue-500 bg-blue-50 dark:bg-blue-900/20': dragging }"
                class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 p-10 transition cursor-pointer"
                x-on:click="$refs.fileInput.click()"
            >
                <flux:icon name="cloud-arrow-up" class="size-12 text-zinc-400" />
                <flux:heading size="sm" class="mt-4">{{ __('Drag & drop your file here') }}</flux:heading>
                <flux:text class="mt-1 text-sm">{{ __('or click to browse') }}</flux:text>
                <flux:text class="mt-2 text-xs text-zinc-400">{{ __('Maximum file size: 10MB') }}</flux:text>
                <flux:text class="mt-1 text-xs text-zinc-400">{{ __('Storage: :used / :limit (:remaining remaining)', ['used' => $usedStorageText, 'limit' => $limitStorageText, 'remaining' => $remainingStorageText]) }}</flux:text>

                <input
                    x-ref="fileInput"
                    type="file"
                    wire:model="file"
                    class="hidden"
                    x-on:livewire-upload-start="uploading = true; progress = 0"
                    x-on:livewire-upload-finish="uploading = false; progress = 100"
                    x-on:livewire-upload-error="uploading = false"
                    x-on:livewire-upload-cancel="uploading = false"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                />
            </div>

            @error('file')
                <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
            @enderror

            @if($file)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                    <flux:icon name="document" class="size-8 text-zinc-400" />
                    <div class="flex-1 min-w-0">
                        <flux:text class="font-medium truncate">{{ $file->getClientOriginalName() }}</flux:text>
                        <flux:text class="text-xs text-zinc-400">{{ number_format($file->getSize() / 1024, 1) }} KB</flux:text>
                    </div>
                </div>
            @endif

            <div x-show="uploading" x-cloak class="space-y-2 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <div class="flex items-center justify-between text-sm">
                    <flux:text>{{ __('Uploading file...') }}</flux:text>
                    <flux:text><span x-text="`${progress}%`"></span></flux:text>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full bg-blue-500 transition-all duration-150" :style="`width: ${progress}%`"></div>
                </div>
            </div>

            <div wire:loading wire:target="file,save" class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Processing upload...') }}
            </div>

            <div class="flex items-center gap-3">
                <flux:button
                    variant="primary"
                    type="submit"
                    :disabled="!$file"
                    wire:loading.attr="disabled"
                    wire:target="file,save"
                >
                    {{ __('Upload') }}
                </flux:button>
                <flux:button variant="ghost" :href="route('files.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</div>
