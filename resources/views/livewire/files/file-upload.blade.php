<div>
    <div class="mx-auto w-full max-w-lg">
        <div class="mb-6 flex items-center gap-3">
            <flux:button variant="ghost" :href="route('files.index')" wire:navigate icon="arrow-left" />
            <flux:heading size="xl">{{ __('Upload File') }}</flux:heading>
        </div>

        <form wire:submit="save" class="space-y-6">
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

                <input x-ref="fileInput" type="file" wire:model="file" class="hidden" />
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

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit" :disabled="!$file">{{ __('Upload') }}</flux:button>
                <flux:button variant="ghost" :href="route('files.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</div>
